<?php

namespace App\Http\Controllers\School\Houses;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\HouseStudent;
use App\Models\Student;
use App\Models\StudentAcademicHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class HouseController extends Controller
{
    public function index()
    {
        $schoolId      = app('current_school_id');
        $academicYearId = app()->bound('current_academic_year_id') ? app('current_academic_year_id') : null;

        $houses = House::where('school_id', $schoolId)
            ->with(['incharge:id,name', 'captain:id,first_name,last_name'])
            ->withCount(['houseStudents as student_count' => function ($q) use ($academicYearId) {
                if ($academicYearId) {
                    $q->where('academic_year_id', $academicYearId);
                }
            }])
            ->get()
            ->map(function ($house) use ($academicYearId) {
                $house->total_points = $house->totalPoints($academicYearId);
                return $house;
            });

        $staff = User::where('school_id', $schoolId)
            ->whereNotIn('user_type', ['student', 'parent'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $students = Student::where('school_id', $schoolId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'admission_no']);

        return Inertia::render('School/Houses/Index', [
            'houses'   => $houses,
            'staff'    => $staff,
            'students' => $students,
        ]);
    }

    public function store(Request $request)
    {
        $schoolId = app('current_school_id');

        $validated = $request->validate([
            'name'               => 'required|string|max:100',
            'color'              => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'incharge_staff_id'  => ['nullable', Rule::exists('users', 'id')->where('school_id', $schoolId)],
            'captain_student_id' => ['nullable', Rule::exists('students', 'id')->where('school_id', $schoolId)],
        ]);

        $validated['school_id'] = $schoolId;
        House::create($validated);

        return back()->with('success', 'House created successfully.');
    }

    public function show(House $house)
    {
        abort_if($house->school_id !== app('current_school_id'), 403);

        $schoolId       = app('current_school_id');
        $academicYearId = app()->bound('current_academic_year_id') ? app('current_academic_year_id') : null;

        $house->load(['incharge:id,name', 'captain:id,first_name,last_name']);

        $houseStudents = HouseStudent::where('house_id', $house->id)
            ->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId))
            ->with([
                'student:id,first_name,last_name,admission_no,gender,photo',
                'student.currentAcademicHistory.courseClass:id,name',
                'student.currentAcademicHistory.section:id,name',
            ])
            ->get();

        $points = $house->points()
            ->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId))
            ->with('awardedBy:id,name')
            ->latest()
            ->get();

        // Students not yet in any house for current year — for assignment modal
        $assignedStudentIds = HouseStudent::where('school_id', $schoolId)
            ->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId))
            ->pluck('student_id');

        $unassignedStudents = Student::where('school_id', $schoolId)
            ->where('status', 'active')
            ->whereNotIn('id', $assignedStudentIds)
            ->with(['currentAcademicHistory.courseClass:id,name', 'currentAcademicHistory.section:id,name'])
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'admission_no', 'gender']);

        return Inertia::render('School/Houses/Show', [
            'house'              => $house,
            'houseStudents'      => $houseStudents,
            'points'             => $points,
            'total_points'       => $house->totalPoints($academicYearId),
            'unassignedStudents' => $unassignedStudents,
        ]);
    }

    public function update(Request $request, House $house)
    {
        abort_if($house->school_id !== app('current_school_id'), 403);

        $schoolId = app('current_school_id');

        $validated = $request->validate([
            'name'               => 'required|string|max:100',
            'color'              => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'incharge_staff_id'  => ['nullable', Rule::exists('users', 'id')->where('school_id', $schoolId)],
            'captain_student_id' => ['nullable', Rule::exists('students', 'id')->where('school_id', $schoolId)],
        ]);

        $house->update($validated);

        return back()->with('success', 'House updated successfully.');
    }

    public function destroy(House $house)
    {
        abort_if($house->school_id !== app('current_school_id'), 403);

        $house->delete();

        return back()->with('success', 'House deleted.');
    }
}
