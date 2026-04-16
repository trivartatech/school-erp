<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\ExamSchedule;
use App\Models\ExamMark;
use App\Models\Section;
use App\Models\Student;
use App\Services\TeacherScopeService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExamMarkSummaryController extends Controller
{
    public function index()
    {
        $schoolId = app('current_school_id');
        $scope    = app(TeacherScopeService::class)->for(auth()->user());

        $query = ExamSchedule::with(['examType', 'courseClass', 'sections'])
            ->where('school_id', $schoolId)
            ->where('academic_year_id', app('current_academic_year_id'))
            ->where('status', 'published');

        // Restrict teachers to schedules for classes they are incharge of
        if ($scope->restricted && $scope->classIds->isNotEmpty()) {
            $query->whereIn('course_class_id', $scope->classIds);
        }

        return Inertia::render('School/Examinations/MarkSummary/Index', [
            'schedules' => $query->latest()->get(),
        ]);
    }

    public function data(Request $request)
    {
        $request->validate([
            'exam_schedule_id' => 'required|exists:exam_schedules,id',
            'section_id'       => 'required|exists:sections,id',
        ]);

        $scope             = app(TeacherScopeService::class)->for(auth()->user());
        $allowedSubjectIds = $this->validateAndGetSubjects($scope, (int) $request->exam_schedule_id, (int) $request->section_id);

        return response()->json(
            $this->buildMarkSummaryData(
                (int) $request->exam_schedule_id,
                (int) $request->section_id,
                $allowedSubjectIds
            )
        );
    }

    public function print(Request $request)
    {
        $request->validate([
            'exam_schedule_id' => 'required|exists:exam_schedules,id',
            'section_id'       => 'required|exists:sections,id',
        ]);

        $scope             = app(TeacherScopeService::class)->for(auth()->user());
        $allowedSubjectIds = $this->validateAndGetSubjects($scope, (int) $request->exam_schedule_id, (int) $request->section_id);

        $data = $this->buildMarkSummaryData(
            (int) $request->exam_schedule_id,
            (int) $request->section_id,
            $allowedSubjectIds
        );

        return Inertia::render('School/Examinations/MarkSummary/Print', array_merge($data, [
            'schoolInfo'   => app('current_school'),
            'academicYear' => app('current_academic_year')->name,
        ]));
    }

    /**
     * Validate the teacher has access to the given schedule + section,
     * and return the subject IDs they may see (null = all, array = specific ones).
     */
    private function validateAndGetSubjects(object $scope, int $scheduleId, int $sectionId): ?array
    {
        if (! $scope->restricted) {
            return null; // admin: no filter
        }

        $schedule = ExamSchedule::where('school_id', app('current_school_id'))->findOrFail($scheduleId);

        abort_unless(
            $scope->classIds->contains($schedule->course_class_id),
            403,
            'You do not have access to this class.'
        );
        abort_unless(
            $scope->sectionIds->contains($sectionId),
            403,
            'You do not have access to this section.'
        );

        // Returns null (all subjects) or a specific array of subject IDs
        return app(TeacherScopeService::class)->allowedSubjectsForSection(
            $scope,
            $schedule->course_class_id,
            $sectionId
        );
    }

    private function buildMarkSummaryData(int $scheduleId, int $sectionId, ?array $allowedSubjectIds = null): array
    {
        $schoolId       = app('current_school_id');
        $academicYearId = app('current_academic_year_id');

        // Step 1 — Load schedule with full subject/assessment/markConfig tree
        $schedule = ExamSchedule::with([
            'examType', 'courseClass',
            'scheduleSubjects' => fn($q) => $q->where('is_enabled', true)
                ->where('is_co_scholastic', false)
                ->with(['subject', 'examAssessment.items', 'markConfigs']),
        ])
        ->where('school_id', $schoolId)
        ->findOrFail($scheduleId);

        $section = Section::where('school_id', $schoolId)->findOrFail($sectionId);

        // Step 2 — Load students sorted by roll_no
        $students = Student::with(['academicHistories' => fn($q) =>
                $q->where('academic_year_id', $academicYearId)
                  ->where('section_id', $sectionId)
            ])
            ->where('school_id', $schoolId)
            ->whereHas('academicHistories', fn($q) =>
                $q->where('academic_year_id', $academicYearId)
                  ->where('class_id', $schedule->course_class_id)
                  ->where('section_id', $sectionId)
            )
            ->where('status', 'active')
            ->get()
            ->sortBy(function ($s) {
                $hist = $s->academicHistories->first();
                return [is_null($hist?->roll_no) ? PHP_INT_MAX : (int)$hist->roll_no, $s->first_name];
            })
            ->values();

        $students->each(function ($s) {
            $s->roll_no = $s->academicHistories->first()?->roll_no;
        });

        // Step 3 — Filter to subjects the teacher is allowed to see, then build columns
        $subjects = $schedule->scheduleSubjects
            ->filter(fn($ss) => $ss->examAssessment)
            ->when($allowedSubjectIds !== null, fn ($c) =>
                $c->filter(fn ($ss) => in_array($ss->subject_id, $allowedSubjectIds))
            )
            ->values();

        $columns = [];
        foreach ($subjects as $ss) {
            $sortedItems = $ss->examAssessment->items->sortBy('sort_order');
            foreach ($sortedItems as $item) {
                $cfg = $ss->markConfigs->firstWhere('exam_assessment_item_id', $item->id);
                $columns[] = [
                    'ss_id'         => $ss->id,
                    'subject_id'    => $ss->subject_id,
                    'subject_name'  => $ss->subject->name ?? '',
                    'item_id'       => $item->id,
                    'item_name'     => $item->name,
                    'item_code'     => $item->code,
                    'max_marks'     => $cfg ? (float)$cfg->max_marks     : (float)($item->max_marks  ?? 0),
                    'passing_marks' => $cfg ? (float)$cfg->passing_marks : 0,
                ];
            }
        }

        // Step 4 — Single bulk query for all marks
        $scheduleSubjectIds = $subjects->pluck('id');
        $studentIds         = $students->pluck('id');

        $marksRaw = ExamMark::whereIn('student_id', $studentIds)
            ->whereIn('exam_schedule_subject_id', $scheduleSubjectIds)
            ->get();

        // Build nested map: marksMap[student_id][ss_id][item_id] = ExamMark
        $marksMap = [];
        foreach ($marksRaw as $m) {
            $marksMap[$m->student_id][$m->exam_schedule_subject_id][$m->exam_assessment_item_id] = $m;
        }

        // Step 5 — Build student rows (cells[] parallel to $columns)
        $rows = [];
        foreach ($students as $student) {
            $cells = [];
            foreach ($columns as $col) {
                $mark = $marksMap[$student->id][$col['ss_id']][$col['item_id']] ?? null;
                $cells[] = [
                    'obtained'  => ($mark && $mark->is_absent) ? null : ($mark ? (float)$mark->marks_obtained : null),
                    'is_absent' => $mark?->is_absent ?? false,
                    'entered'   => $mark !== null,
                ];
            }
            $rows[] = [
                'id'           => $student->id,
                'name'         => $student->first_name . ' ' . $student->last_name,
                'roll_no'      => $student->roll_no,
                'admission_no' => $student->admission_no,
                'cells'        => $cells,
            ];
        }

        // Step 6 — Per-column statistics
        $colStats = [];
        foreach ($columns as $ci => $col) {
            $values      = [];
            $absentCount = 0;
            $passCount   = 0;
            $failCount   = 0;

            foreach ($rows as $row) {
                $cell = $row['cells'][$ci];
                if ($cell['is_absent']) {
                    $absentCount++;
                } elseif ($cell['entered'] && $cell['obtained'] !== null) {
                    $v = $cell['obtained'];
                    $values[] = $v;
                    $threshold = $col['passing_marks'] > 0
                        ? $col['passing_marks']
                        : $col['max_marks'] * 0.33;
                    if ($v >= $threshold) $passCount++;
                    else                  $failCount++;
                }
            }

            $colStats[] = [
                'highest'      => count($values) ? max($values) : null,
                'lowest'       => count($values) ? min($values) : null,
                'average'      => count($values) ? round(array_sum($values) / count($values), 1) : null,
                'absent_count' => $absentCount,
                'pass_count'   => $passCount,
                'fail_count'   => $failCount,
            ];
        }

        return [
            'schedule'      => [
                'id'         => $schedule->id,
                'name'       => $schedule->examType->name ?? '',
                'class_name' => $schedule->courseClass->name ?? '',
            ],
            'section'       => ['id' => $section->id, 'name' => $section->name],
            'columns'       => $columns,
            'rows'          => $rows,
            'col_stats'     => $colStats,
            'partial_view'  => $allowedSubjectIds !== null, // true when subject-restricted teacher
        ];
    }
}
