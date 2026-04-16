<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = ['user_id', 'school_id', 'event_type', 'channel', 'enabled'];

    protected $casts = ['enabled' => 'boolean'];

    // Available event types and their human labels
    public const EVENT_TYPES = [
        'fee_payment'        => 'Fee Payment Receipt',
        'fee_due_reminder'   => 'Fee Due Reminder',
        'attendance_marked'  => 'Attendance Marked',
        'leave_approved'     => 'Leave Approved',
        'leave_rejected'     => 'Leave Rejected',
        'exam_result'        => 'Exam Result Published',
        'announcement'       => 'Announcement / Notice',
        'gate_pass_approved' => 'Gate Pass Approved',
        'gate_pass_rejected' => 'Gate Pass Rejected',
        'timetable_update'   => 'Timetable Changed',
        'holiday_notice'     => 'Holiday Notice',
        'ptm_booking'        => 'PTM Slot Booked',
        'library_due'        => 'Library Book Due',
    ];

    public const CHANNELS = ['push', 'email', 'sms', 'whatsapp'];

    /**
     * Check if a user has a specific channel enabled for an event type.
     * Defaults to TRUE if no preference row exists (opt-in by default).
     */
    public static function isEnabled(int $userId, string $eventType, string $channel): bool
    {
        $pref = static::where('user_id', $userId)
            ->where('event_type', $eventType)
            ->where('channel', $channel)
            ->first();

        return $pref ? $pref->enabled : true;
    }

    /**
     * Get all preferences for a user as nested array:
     * ['fee_payment' => ['push' => true, 'email' => true, ...], ...]
     */
    public static function forUser(int $userId, int $schoolId): array
    {
        $prefs = static::where('user_id', $userId)
            ->where('school_id', $schoolId)
            ->get()
            ->groupBy('event_type');

        $result = [];
        foreach (array_keys(static::EVENT_TYPES) as $event) {
            $result[$event] = [];
            foreach (static::CHANNELS as $channel) {
                $pref = $prefs->get($event)?->firstWhere('channel', $channel);
                $result[$event][$channel] = $pref ? $pref->enabled : true;
            }
        }
        return $result;
    }
}
