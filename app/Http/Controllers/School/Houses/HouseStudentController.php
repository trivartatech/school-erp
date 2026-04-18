<?php

namespace App\Http\Controllers\School\Houses;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\HouseStudent;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HouseStudentController extends Controller
{
    public function store(Request $request, House $house)
    {
        abort_if($house->school_id !== app('current_school_id'), 403);

        $schoolId       = app('current_school_id');
        $academicYearId = app()->bound('current_academic_year_id') ? app('current_academic_year_id') : null;

        abort_if(!$academicYearId, 422, 'No active academic year found.');

        $validated = $request->validate([
            'student_ids'   => 'required|array|min:1',
            'student_ids.*' => ['required', Rule::exists('students', 'id')->where('school_id', $schoolId)],
        ]);

        $now = now();
        foreach ($validated['student_ids'] as $studentId) {
            HouseStudent::updateOrCreate(
                ['student_id' => $studentId, 'academic_year_id' => $academicYearId],
                [
                    'school_id'   => $schoolId,
                    'house_id'    => $house->id,
                    'assigned_by' => auth()->id(),
                    'updated_at'  => $now,
                ]
            );
        }

        return back()->with('success', count($validated['student_ids']) . ' student(s) assigned to ' . $house->name . '.');
    }

    public function destroy(House $house, Student $student)
    {
        abort_if($house->school_id !== app('current_school_id'), 403);

        $academicYearId = app()->bound('current_academic_year_id') ? app('current_academic_year_id') : null;

        HouseStudent::where('house_id', $house->id)
            ->where('student_id', $student->id)
            ->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId))
            ->delete();

        return back()->with('success', 'Student removed from house.');
    }
}
