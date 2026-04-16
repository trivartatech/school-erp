<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\PtmBooking;
use App\Models\PtmSession;
use App\Models\PtmSlot;
use App\Models\Staff;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PtmController extends Controller
{
    // ── Admin: session list ───────────────────────────────────────────
    public function index()
    {
        $schoolId = app('current_school_id');
        $sessions = PtmSession::where('school_id', $schoolId)
            ->withCount(['slots', 'bookings'])
            ->latest('date')
            ->get();

        return Inertia::render('School/PTM/Index', ['sessions' => $sessions]);
    }

    // ── Admin: create session ─────────────────────────────────────────
    public function store(Request $request)
    {
        $schoolId  = app('current_school_id');
        $validated = $request->validate([
            'title'                  => 'required|string|max:255',
            'date'                   => 'required|date',
            'start_time'             => 'required|date_format:H:i',
            'end_time'               => 'required|date_format:H:i|after:start_time',
            'slot_duration_minutes'  => 'required|integer|min:5|max:60',
            'description'            => 'nullable|string',
            'staff_ids'              => 'nullable|array',
            'staff_ids.*'            => 'exists:staff,id',
        ]);

        DB::transaction(function () use ($validated, $schoolId) {
            $session = PtmSession::create([
                'school_id'             => $schoolId,
                'title'                 => $validated['title'],
                'date'                  => $validated['date'],
                'start_time'            => $validated['start_time'],
                'end_time'              => $validated['end_time'],
                'slot_duration_minutes' => $validated['slot_duration_minutes'],
                'description'           => $validated['description'] ?? null,
                'status'                => 'draft',
            ]);

            // Auto-generate time slots for each selected teacher (optional at creation time)
            if (!empty($validated['staff_ids'])) {
                $start    = Carbon::parse($validated['date'] . ' ' . $validated['start_time']);
                $end      = Carbon::parse($validated['date'] . ' ' . $validated['end_time']);
                $duration = $validated['slot_duration_minutes'];

                foreach ($validated['staff_ids'] as $staffId) {
                    $cursor = $start->copy();
                    while ($cursor->lt($end)) {
                        PtmSlot::create([
                            'session_id' => $session->id,
                            'staff_id'   => $staffId,
                            'slot_time'  => $cursor->format('H:i:s'),
                        ]);
                        $cursor->addMinutes($duration);
                    }
                }
            }
        });

        return back()->with('success', 'PTM session created.');
    }

    // ── Admin: update status ──────────────────────────────────────────
    public function updateStatus(Request $request, PtmSession $ptmSession)
    {
        abort_if($ptmSession->school_id !== app('current_school_id'), 403);
        $request->validate(['status' => 'required|in:draft,open,closed']);
        $ptmSession->update(['status' => $request->status]);
        return back()->with('success', 'Session status updated.');
    }

    // ── Admin: view bookings for a session ────────────────────────────
    public function sessionDetail(PtmSession $ptmSession)
    {
        abort_if($ptmSession->school_id !== app('current_school_id'), 403);
        $ptmSession->load(['slots.staff.user', 'slots.booking.student', 'slots.booking.parentUser']);

        return Inertia::render('School/PTM/SessionDetail', ['session' => $ptmSession]);
    }

    // ── Teacher/Admin: add meeting notes ─────────────────────────────
    public function addNotes(Request $request, PtmBooking $ptmBooking)
    {
        // Only the slot's teacher or school management
        $schoolId = app('current_school_id');
        abort_if($ptmBooking->slot->session->school_id !== $schoolId, 403);

        $validated = $request->validate([
            'meeting_notes' => 'required|string',
            'status'        => 'required|in:completed,no_show',
        ]);

        $ptmBooking->update($validated);
        return back()->with('success', 'Meeting notes saved.');
    }

    // ── Parent/Student: book a slot ───────────────────────────────────
    public function book(Request $request, PtmSlot $ptmSlot)
    {
        $user = auth()->user();
        abort_if($ptmSlot->is_booked, 422, 'This slot is already booked.');
        abort_if($ptmSlot->session->status !== 'open', 422, 'Bookings are not open for this session.');
        abort_if($ptmSlot->session->school_id !== app('current_school_id'), 403);

        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
        ]);

        DB::transaction(function () use ($validated, $ptmSlot, $user) {
            PtmBooking::create([
                'slot_id'       => $ptmSlot->id,
                'student_id'    => $validated['student_id'],
                'parent_user_id'=> $user->id,
                'status'        => 'booked',
            ]);

            $ptmSlot->update(['is_booked' => true]);
        });

        return back()->with('success', 'Slot booked successfully.');
    }

    // ── Parent/Student: cancel booking ───────────────────────────────
    public function cancelBooking(PtmBooking $ptmBooking)
    {
        $user = auth()->user();
        abort_if($ptmBooking->parent_user_id !== $user->id, 403);
        abort_if($ptmBooking->status !== 'booked', 422, 'Cannot cancel a completed or already-cancelled booking.');

        DB::transaction(function () use ($ptmBooking) {
            $ptmBooking->slot->update(['is_booked' => false]);
            $ptmBooking->update(['status' => 'cancelled']);
        });

        return back()->with('success', 'Booking cancelled.');
    }

    // ── Parent/Student: available sessions + slots ───────────────────
    public function parentView()
    {
        $schoolId = app('current_school_id');
        $user     = auth()->user();

        $sessions = PtmSession::where('school_id', $schoolId)
            ->where('status', 'open')
            ->where('date', '>=', now()->toDateString())
            ->with(['slots' => function ($q) {
                $q->with('staff.user', 'booking');
            }])
            ->get();

        // Parent's students
        $parent   = \App\Models\StudentParent::where('user_id', $user->id)->first();
        $students = $parent ? $parent->students()->get(['id', 'first_name', 'last_name']) : collect();

        return Inertia::render('School/PTM/ParentView', compact('sessions', 'students'));
    }
}
