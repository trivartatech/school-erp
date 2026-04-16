<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PtmBooking extends Model
{
    protected $fillable = [
        'slot_id', 'student_id', 'parent_user_id', 'status', 'meeting_notes',
    ];

    public function slot()       { return $this->belongsTo(PtmSlot::class, 'slot_id'); }
    public function student()    { return $this->belongsTo(Student::class); }
    public function parentUser() { return $this->belongsTo(User::class, 'parent_user_id'); }
}
