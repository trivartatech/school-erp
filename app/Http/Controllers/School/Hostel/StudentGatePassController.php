<?php

namespace App\Http\Controllers\School\Hostel;

use App\Http\Controllers\Controller;
use App\Models\HostelLeaveRequest;
use App\Models\HostelStudent;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentGatePassController extends Controller
{
    /**
     * Resolve the student record for the logged-in user.
     * Works for both student users and parent users.
     */
    private function resolveStudent(int $schoolId): ?Student
    {
        $user = auth()->user();

        // Direct student user
        $student = Student::where('user_id', $user->id)
            ->where('school_id', $schoolId)
            ->first();

        return $student;
    }

    // ── Student: view own gate pass history ─────────────────────────────
    public function index()
    {
        $schoolId = app('current_school_id');
        $student  = $this->resolveStudent($schoolId);

        if (!$student) {
            return Inertia::render('School/Hostel/GatePasses/StudentPortal', [
                'passes'       => [],
                'hostelActive' => false,
                'student'      => null,
            ]);
        }

        // Check if student is in hostel
        $hostelAllocation = HostelStudent::where('student_id', $student->id)
            ->where('school_id', $schoolId)
            ->where('status', 'Active')
            ->whereNull('vacate_date')
            ->first();

        $passes = HostelLeaveRequest::where('student_id', $student->id)
            ->where('school_id', $schoolId)
            ->with('approver:id,name,first_name,last_name')
            ->latest('from_date')
            ->get();

        return Inertia::render('School/Hostel/GatePasses/StudentPortal', [
            'passes'       => $passes,
            'hostelActive' => !!$hostelAllocation,
            'student'      => $student->only('id', 'first_name', 'last_name', 'admission_no'),
        ]);
    }

    // ── Student: submit a new gate pass request ──────────────────────────
    public function store(Request $request)
    {
        $schoolId = app('current_school_id');
        $student  = $this->resolveStudent($schoolId);

        if (!$student) {
            return back()->withErrors(['general' => 'Student record not found for your account.']);
        }

        // Must be active hostel student
        $hostelActive = HostelStudent::where('student_id', $student->id)
            ->where('school_id', $schoolId)
            ->where('status', 'Active')
            ->whereNull('vacate_date')
            ->exists();

        abort_if(!$hostelActive, 403, 'You must be an active hostel student to request gate passes.');

        $validated = $request->validate([
            'leave_type'       => 'required|in:Day Out,Night Out,Home Time,Emergency',
            'from_date'        => 'required|date|after_or_equal:today',
            'to_date'          => 'required|date|after_or_equal:from_date',
            'reason'           => 'required|string|max:500',
            'destination'      => 'nullable|string|max:300',
            'escort_name'      => 'nullable|string|max:255',
            'escort_relation'  => 'nullable|string|max:100',
            'escort_phone'     => 'nullable|string|max:20',
            'parent_name'      => 'nullable|string|max:255',
        ]);

        HostelLeaveRequest::create(array_merge($validated, [
            'school_id'       => $schoolId,
            'student_id'      => $student->id,
            'status'          => 'Pending',
            'parent_approval' => 'Pending',
        ]));

        return back()->with('success', 'Gate pass request submitted. Please wait for warden approval.');
    }

    // ── Student: cancel a pending request ────────────────────────────────
    public function cancel(HostelLeaveRequest $gatePass)
    {
        $schoolId = app('current_school_id');
        $student  = $this->resolveStudent($schoolId);

        abort_if(!$student, 403);
        abort_if($gatePass->student_id !== $student->id, 403);
        abort_if($gatePass->school_id  !== $schoolId, 403);
        abort_if($gatePass->status !== 'Pending', 422, 'Only pending requests can be cancelled.');

        $gatePass->update(['status' => 'Rejected', 'late_reason' => 'Cancelled by student']);

        return back()->with('success', 'Request cancelled.');
    }
}
