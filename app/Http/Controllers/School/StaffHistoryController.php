<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Staff;
use App\Models\StaffHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StaffHistoryController extends Controller
{
    // ── View history for a specific staff member ───────────────────────────
    public function show(Staff $staff)
    {
        $schoolId = app('current_school_id');
        abort_if($staff->school_id !== $schoolId, 403);

        $staff->load(['user:id,name,first_name,last_name', 'designation', 'department']);

        $history = StaffHistory::where('staff_id', $staff->id)
            ->with([
                'fromDesignation', 'toDesignation',
                'fromDepartment', 'toDepartment',
                'recordedBy:id,name,first_name,last_name',
            ])
            ->orderByDesc('effective_date')
            ->get();

        $designations = Designation::where('school_id', $schoolId)->get();
        $departments  = Department::where('school_id', $schoolId)->get();

        return Inertia::render('School/Staff/History', compact('staff', 'history', 'designations', 'departments'));
    }

    // ── Record a new event (promotion, transfer, salary revision...) ────────
    public function store(Request $request, Staff $staff)
    {
        $schoolId = app('current_school_id');
        abort_if($staff->school_id !== $schoolId, 403);

        $validated = $request->validate([
            'event_type'          => 'required|in:joining,promotion,transfer,demotion,salary_revision,department_change,designation_change,increment,confirmation,termination,other',
            'from_designation_id' => 'nullable|exists:designations,id',
            'to_designation_id'   => 'nullable|exists:designations,id',
            'from_department_id'  => 'nullable|exists:departments,id',
            'to_department_id'    => 'nullable|exists:departments,id',
            'from_salary'         => 'nullable|numeric|min:0',
            'to_salary'           => 'nullable|numeric|min:0',
            'effective_date'      => 'required|date',
            'order_no'            => 'nullable|string|max:100',
            'remarks'             => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $staff, $schoolId) {
            StaffHistory::create(array_merge($validated, [
                'school_id'   => $schoolId,
                'staff_id'    => $staff->id,
                'recorded_by' => auth()->id(),
            ]));

            // Apply changes to staff record
            $updates = [];
            if ($validated['to_designation_id'] ?? null) {
                $updates['designation_id'] = $validated['to_designation_id'];
            }
            if ($validated['to_department_id'] ?? null) {
                $updates['department_id'] = $validated['to_department_id'];
            }
            if ($validated['to_salary'] ?? null) {
                $updates['basic_salary'] = $validated['to_salary'];
            }
            if ($validated['event_type'] === 'termination') {
                $updates['status'] = 'inactive';
            }
            if (!empty($updates)) {
                $staff->update($updates);
            }
        });

        return back()->with('success', 'Staff history event recorded and staff profile updated.');
    }

    // ── All recent changes across all staff ────────────────────────────────
    public function indexAll(Request $request)
    {
        $schoolId = app('current_school_id');

        $query = StaffHistory::where('staff_history.school_id', $schoolId)
            ->with([
                'staff.user:id,name,first_name,last_name',
                'fromDesignation', 'toDesignation',
                'fromDepartment', 'toDepartment',
            ])
            ->orderByDesc('effective_date')
            ->orderByDesc('id');

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->filled('from_date')) {
            $query->where('effective_date', '>=', $request->from_date);
        }

        $events = $query->paginate(30)->withQueryString();

        return Inertia::render('School/Staff/HistoryLog', compact('events'));
    }
}
