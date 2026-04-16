<?php

namespace App\Services;

use App\Models\ClassSubject;
use App\Models\CourseClass;
use App\Models\Section;
use App\Models\Staff;
use Illuminate\Support\Facades\Cache;

/**
 * TeacherScopeService
 * ─────────────────────────────────────────────────────────────────────────────
 * Resolves a teacher's data scope from incharge assignments.
 *
 * Hierarchy (higher overrides lower):
 *   1. course_classes.incharge_staff_id   → Class teacher: ALL sections + ALL subjects
 *   2. sections.incharge_staff_id         → Section teacher: ALL subjects in that section
 *   3. class_subjects.incharge_staff_id   → Subject teacher: ONLY that subject in that section
 *
 * Key scope properties:
 *   ->restricted        bool        — true for teachers (false = admin, see everything)
 *   ->subjectRestricted bool        — true ONLY if teacher is purely a subject incharge
 *                                     with no class/section incharge role
 *   ->staffId           int|null
 *   ->classIds          Collection  — class IDs the teacher has ANY access to
 *   ->sectionIds        Collection  — section IDs the teacher can access
 *   ->subjectIds        Collection  — ALL subject IDs they teach (across all sections)
 *   ->classSubjects     Collection  — raw ClassSubject records (for detailed mapping)
 *   ->allowedMap        array       — [class_id][section_id] = 'ALL' | [subject_id, ...]
 *                                     Mirrors the ExamMarkController pattern, reusable everywhere
 */
class TeacherScopeService
{
    public function for(\App\Models\User $user): object
    {
        // Admins, principals, school admins → no restrictions
        if (! $user->isTeacher()) {
            return $this->unrestricted();
        }

        $staff = Staff::where('user_id', $user->id)->first();
        if (! $staff) {
            return $this->emptyScope(); // no staff record = see nothing
        }

        $schoolId = $user->school_id;
        $cacheKey = "teacher_scope_{$staff->id}_{$schoolId}";

        return Cache::remember($cacheKey, 300, function () use ($staff, $schoolId) {
            $allowedMap = [];

            // ── 1. Class incharge → ALL sections + ALL subjects of that class ─────
            $classInchargeIds = CourseClass::where('school_id', $schoolId)
                ->where('incharge_staff_id', $staff->id)
                ->pluck('id');

            if ($classInchargeIds->isNotEmpty()) {
                $classeSections = Section::where('school_id', $schoolId)
                    ->whereIn('course_class_id', $classInchargeIds)
                    ->get();
                foreach ($classeSections as $sec) {
                    $allowedMap[$sec->course_class_id][$sec->id] = 'ALL';
                }
            }

            // ── 2. Section incharge → ALL subjects in their section ───────────────
            $sectionInchargeItems = Section::where('school_id', $schoolId)
                ->where('incharge_staff_id', $staff->id)
                ->get();

            foreach ($sectionInchargeItems as $sec) {
                // Only set to ALL if not already set (class incharge has higher priority)
                if (! isset($allowedMap[$sec->course_class_id][$sec->id])) {
                    $allowedMap[$sec->course_class_id][$sec->id] = 'ALL';
                }
            }

            // ── 3. Subject incharge → specific subject in a class+section ──────────
            $classSubjects = ClassSubject::with(['courseClass', 'section', 'subject'])
                ->where('school_id', $schoolId)
                ->where('incharge_staff_id', $staff->id)
                ->get();

            foreach ($classSubjects as $cs) {
                $classId   = $cs->course_class_id;
                $sectionId = $cs->section_id;

                if (! isset($allowedMap[$classId])) {
                    $allowedMap[$classId] = [];
                }

                if (! isset($allowedMap[$classId][$sectionId])) {
                    $allowedMap[$classId][$sectionId] = [];
                }

                // If already 'ALL' (from class/section incharge) → don't narrow it
                if ($allowedMap[$classId][$sectionId] !== 'ALL') {
                    $allowedMap[$classId][$sectionId][] = $cs->subject_id;
                }
            }

            // ── Derive flattened IDs from allowedMap ──────────────────────────────
            $classIds = collect(array_keys($allowedMap))->unique()->values();

            $sectionIds = collect();
            foreach ($allowedMap as $sections) {
                $sectionIds = $sectionIds->merge(array_keys($sections));
            }
            $sectionIds = $sectionIds->unique()->values();

            $subjectIds = $classSubjects->pluck('subject_id')->unique()->values();

            // Teacher is "subject restricted" only if they have NO class/section incharge role
            // i.e. every entry in allowedMap is an array (not 'ALL')
            $subjectRestricted = $classInchargeIds->isEmpty()
                && $sectionInchargeItems->isEmpty()
                && $classSubjects->isNotEmpty();

            return (object) [
                'restricted'        => true,
                'subjectRestricted' => $subjectRestricted,
                'staffId'           => $staff->id,
                'classIds'          => $classIds,
                'sectionIds'        => $sectionIds,
                'subjectIds'        => $subjectIds,
                'classSubjects'     => $classSubjects,
                'allowedMap'        => $allowedMap,
            ];
        });
    }

    /**
     * Apply scope to a query builder.
     * Call this from any controller to filter by teacher's assigned sections.
     * For subject filtering, use ->applySubjectScope() separately.
     */
    public function applySectionScope($query, object $scope, string $sectionColumn = 'section_id'): void
    {
        if ($scope->restricted) {
            $query->whereIn($sectionColumn, $scope->sectionIds->isEmpty() ? [-1] : $scope->sectionIds);
        }
    }

    /**
     * Returns subject IDs a teacher can see for a specific section.
     * Returns null if they can see ALL subjects (no restriction).
     * Returns [-1] (no subjects) if they have no access to the section.
     */
    public function allowedSubjectsForSection(object $scope, int $classId, int $sectionId): ?array
    {
        if (! $scope->restricted) {
            return null; // admin: no filter
        }

        if (! isset($scope->allowedMap[$classId][$sectionId])) {
            return [-1]; // no access at all
        }

        $entry = $scope->allowedMap[$classId][$sectionId];
        return $entry === 'ALL' ? null : $entry;
    }

    /**
     * Apply combined section + subject scope for subject-sensitive queries
     * (assignments, diary entries, mark summaries, etc.).
     *
     * Reads allowedMap directly — handles ALL three incharge scenarios and mixed
     * combinations without relying on the coarse subjectRestricted flag:
     *
     *   class incharge of 1A + English teacher of 2A →
     *     WHERE (section_id = 1A)
     *        OR (section_id = 2A AND subject_id = english_id)
     *
     * This replaces the old two-step approach (whereIn sectionIds + applySubjectScope).
     * Call this instead of applySectionScope() for any query that also has a subject column.
     */
    public function applySubjectScope(
        $query,
        object $scope,
        string $sectionColumn = 'section_id',
        string $subjectColumn = 'subject_id'
    ): void {
        if (! $scope->restricted) {
            return; // admin: no filter
        }

        if (empty($scope->allowedMap)) {
            $query->whereRaw('1 = 0'); // no incharge roles at all → see nothing
            return;
        }

        $query->where(function ($q) use ($scope, $sectionColumn, $subjectColumn) {
            foreach ($scope->allowedMap as $sections) {
                foreach ($sections as $sectionId => $subjects) {
                    if ($subjects === 'ALL') {
                        // class or section incharge → unrestricted for this section
                        $q->orWhere($sectionColumn, $sectionId);
                    } elseif (is_array($subjects) && ! empty($subjects)) {
                        // subject incharge → only those subjects in this section
                        $q->orWhere(fn ($sq) =>
                            $sq->where($sectionColumn, $sectionId)
                               ->whereIn($subjectColumn, $subjects)
                        );
                    }
                }
            }
        });
    }

    /**
     * Apply class + subject scope for models without a section column (e.g. SyllabusTopic).
     *
     * Per class: if any section has 'ALL' access the teacher sees all subjects in that
     * class; otherwise only the union of their specific subject IDs for that class.
     *
     *   class incharge of 1A + English teacher of 2A →
     *     WHERE (class_id = class1)
     *        OR (class_id = class2 AND subject_id = english_id)
     */
    public function applyClassSubjectScope(
        $query,
        object $scope,
        string $classColumn   = 'class_id',
        string $subjectColumn = 'subject_id'
    ): void {
        if (! $scope->restricted) {
            return;
        }

        if (empty($scope->allowedMap)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->where(function ($q) use ($scope, $classColumn, $subjectColumn) {
            foreach ($scope->allowedMap as $classId => $sections) {
                $hasAll     = false;
                $subjectIds = collect();
                foreach ($sections as $subjects) {
                    if ($subjects === 'ALL') {
                        $hasAll = true;
                        break;
                    }
                    if (is_array($subjects)) {
                        $subjectIds = $subjectIds->merge($subjects);
                    }
                }

                if ($hasAll) {
                    // class/section incharge → all subjects visible for this class
                    $q->orWhere($classColumn, $classId);
                } else {
                    $ids = $subjectIds->unique()->values()->all();
                    if (! empty($ids)) {
                        $q->orWhere(fn ($sq) =>
                            $sq->where($classColumn, $classId)->whereIn($subjectColumn, $ids)
                        );
                    }
                }
            }
        });
    }

    /** Clear cached scope (call after any incharge assignment change) */
    public function clearCache(int $staffId, int $schoolId): void
    {
        Cache::forget("teacher_scope_{$staffId}_{$schoolId}");
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function unrestricted(): object
    {
        return (object) [
            'restricted'        => false,
            'subjectRestricted' => false,
            'staffId'           => null,
            'classIds'          => collect(),
            'sectionIds'        => collect(),
            'subjectIds'        => collect(),
            'classSubjects'     => collect(),
            'allowedMap'        => [],
        ];
    }

    private function emptyScope(): object
    {
        return (object) [
            'restricted'        => true,
            'subjectRestricted' => false,
            'staffId'           => null,
            'classIds'          => collect(),
            'sectionIds'        => collect([-1]),
            'subjectIds'        => collect(),
            'classSubjects'     => collect(),
            'allowedMap'        => [],
        ];
    }
}
