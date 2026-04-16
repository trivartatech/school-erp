<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PtmSession extends Model
{
    protected $fillable = [
        'school_id', 'title', 'date', 'start_time', 'end_time',
        'slot_duration_minutes', 'description', 'status',
    ];

    protected $casts = ['date' => 'date'];

    public function school()   { return $this->belongsTo(School::class); }
    public function slots()    { return $this->hasMany(PtmSlot::class, 'session_id'); }
    public function bookings() { return $this->hasManyThrough(PtmBooking::class, PtmSlot::class, 'session_id', 'slot_id'); }
}
