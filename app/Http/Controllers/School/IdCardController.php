<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\CourseClass;
use App\Models\Student;
use App\Services\TeacherScopeService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IdCardController extends Controller
{
    /**
     * Template designer & filter page.
     */
    public function index(Request $request)
    {
        $schoolId       = app('current_school_id');
        $school         = app('current_school');
        $academicYearId = app()->bound('current_academic_year_id') ? app('current_academic_year_id') : null;

        $classes = CourseClass::where('school_id', $schoolId)
            ->orderBy('order_index')
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('School/Students/IdCards/Index', [
            'school'         => [
                'name'    => $school->name,
                'logo'    => $school->logo ? '/storage/' . $school->logo : null,
                'address' => $school->address,
                'phone'   => $school->phone,
                'email'   => $school->email,
                'board'   => $school->board,
            ],
            'classes'        => $classes,
            'academicYearId' => $academicYearId,
            'filters'        => $request->only(['class_id', 'section_id']),
        ]);
    }

    /**
     * Print page — returns students matching the filter + template settings.
     */
    public function print(Request $request)
    {
        $schoolId       = app('current_school_id');
        $school         = app('current_school');
        $academicYearId = app()->bound('current_academic_year_id') ? app('current_academic_year_id') : null;

        $scope = app(TeacherScopeService::class)->for(auth()->user());

        $query = Student::with([
            'currentAcademicHistory.courseClass',
            'currentAcademicHistory.section',
            'studentParent',
        ])
            ->where('school_id', $schoolId)
            ->where('status', 'active');

        // Teacher scope restriction
        if ($scope->restricted) {
            $query->whereHas('currentAcademicHistory', function ($q) use ($academicYearId, $scope) {
                $q->where('academic_year_id', $academicYearId)
                  ->where('status', 'current')
                  ->whereIn('section_id', $scope->sectionIds);
            });
        }

        if ($request->filled('class_id')) {
            $query->whereHas('currentAcademicHistory', function ($q) use ($request, $academicYearId) {
                $q->where('academic_year_id', $academicYearId)
                  ->where('class_id', $request->class_id);
            });
        }

        if ($request->filled('section_id')) {
            $query->whereHas('currentAcademicHistory', function ($q) use ($request, $academicYearId) {
                $q->where('academic_year_id', $academicYearId)
                  ->where('section_id', $request->section_id);
            });
        }

        // Specific student IDs (optional cherry-pick)
        if ($request->filled('student_ids')) {
            $ids = array_filter(array_map('intval', explode(',', $request->student_ids)));
            if ($ids) {
                $query->whereIn('id', $ids);
            }
        }

        $students = $query
            ->orderByRaw("
                COALESCE((
                    SELECT sah.class_id FROM student_academic_histories sah
                    WHERE sah.student_id = students.id AND sah.academic_year_id = ?
                    AND sah.status = 'current' LIMIT 1
                ), 999999)
            ", [$academicYearId ?? 0])
            ->orderBy('first_name')
            ->get()
            ->map(function ($student) {
                $history = $student->currentAcademicHistory;
                return [
                    'id'           => $student->id,
                    'name'         => $student->name,
                    'first_name'   => $student->first_name,
                    'last_name'    => $student->last_name,
                    'photo_url'    => $student->photo_url,
                    'admission_no' => $student->admission_no,
                    'erp_no'       => $student->erp_no,
                    'roll_no'      => $history?->roll_no ?? $student->roll_no,
                    'dob'          => $student->dob,
                    'blood_group'  => $student->blood_group,
                    'gender'       => $student->gender,
                    'uuid'         => $student->uuid,
                    'class'        => $history?->courseClass?->name,
                    'section'      => $history?->section?->name,
                    'parent_phone' => $student->studentParent?->primary_phone,
                    'father_name'  => $student->studentParent?->father_name,
                ];
            });

        // Template settings (passed back as-is for the print page to use)
        $template = [
            'accent_color'   => $request->input('accent_color', '#1e3a8a'),
            'style'          => $request->input('style', 'classic'),
            'show_photo'     => filter_var($request->input('show_photo', true), FILTER_VALIDATE_BOOLEAN),
            'show_qr'        => filter_var($request->input('show_qr', true), FILTER_VALIDATE_BOOLEAN),
            'show_roll_no'   => filter_var($request->input('show_roll_no', true), FILTER_VALIDATE_BOOLEAN),
            'show_admission' => filter_var($request->input('show_admission', false), FILTER_VALIDATE_BOOLEAN),
            'show_dob'       => filter_var($request->input('show_dob', false), FILTER_VALIDATE_BOOLEAN),
            'show_blood'     => filter_var($request->input('show_blood', true), FILTER_VALIDATE_BOOLEAN),
            'show_parent'    => filter_var($request->input('show_parent', true), FILTER_VALIDATE_BOOLEAN),
            'show_address'   => filter_var($request->input('show_address', false), FILTER_VALIDATE_BOOLEAN),
        ];

        return Inertia::render('School/Students/IdCards/Print', [
            'students' => $students,
            'school'   => [
                'name'    => $school->name,
                'logo'    => $school->logo ? '/storage/' . $school->logo : null,
                'address' => $school->address,
                'phone'   => $school->phone,
                'board'   => $school->board,
            ],
            'template' => $template,
        ]);
    }
}
