<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PtmSlot extends Model
{
    protected $fillable = ['session_id', 'staff_id', 'slot_time', 'is_booked'];
    protected $casts    = ['is_booked' => 'boolean'];

    public function session()  { return $this->belongsTo(PtmSession::class, 'session_id'); }
    public function staff()    { return $this->belongsTo(Staff::class); }
    public function booking()  { return $this->hasOne(PtmBooking::class, 'slot_id'); }
}
