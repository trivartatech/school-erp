<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisciplinaryRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id', 'student_id', 'reported_by', 'reviewed_by',
        'incident_date', 'category', 'severity', 'description',
        'action_taken', 'status', 'consequence', 'consequence_from', 'consequence_to',
        'parent_notified', 'parent_notified_at', 'student_statement', 'notes',
    ];

    protected $casts = [
        'incident_date'     => 'date',
        'consequence_from'  => 'date',
        'consequence_to'    => 'date',
        'parent_notified'   => 'boolean',
        'parent_notified_at'=> 'date',
    ];

    public function school()     { return $this->belongsTo(School::class); }
    public function student()    { return $this->belongsTo(Student::class); }
    public function reportedBy() { return $this->belongsTo(User::class, 'reported_by'); }
    public function reviewedBy() { return $this->belongsTo(User::class, 'reviewed_by'); }
}
