<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationPreferencesController extends Controller
{
    public function show()
    {
        $user     = auth()->user();
        $schoolId = app('current_school_id');

        $preferences = NotificationPreference::forUser($user->id, $schoolId);

        return Inertia::render('School/Settings/NotificationPreferences', [
            'preferences' => $preferences,
            'eventTypes'  => NotificationPreference::EVENT_TYPES,
            'channels'    => NotificationPreference::CHANNELS,
        ]);
    }

    public function update(Request $request)
    {
        $user     = auth()->user();
        $schoolId = app('current_school_id');

        $validated = $request->validate([
            'preferences'                  => 'required|array',
            'preferences.*'                => 'array',
            'preferences.*.*'              => 'boolean',
        ]);

        foreach ($validated['preferences'] as $eventType => $channels) {
            if (!array_key_exists($eventType, NotificationPreference::EVENT_TYPES)) continue;

            foreach ($channels as $channel => $enabled) {
                if (!in_array($channel, NotificationPreference::CHANNELS)) continue;

                NotificationPreference::updateOrCreate(
                    ['user_id' => $user->id, 'event_type' => $eventType, 'channel' => $channel],
                    ['school_id' => $schoolId, 'enabled' => (bool) $enabled]
                );
            }
        }

        return back()->with('success', 'Notification preferences saved.');
    }
}
