<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alumni extends Model
{
    protected $table = 'alumni';

    protected $fillable = [
        'school_id', 'student_id', 'academic_year_id',
        'final_class', 'passout_year', 'final_percentage', 'final_grade',
        'current_occupation', 'current_employer', 'current_city', 'current_state',
        'personal_email', 'personal_phone', 'linkedin_url',
        'achievements', 'notes', 'graduated_on', 'graduated_by',
    ];

    protected $casts = [
        'final_percentage' => 'decimal:2',
        'graduated_on'     => 'date',
    ];

    public function student()      { return $this->belongsTo(Student::class); }
    public function school()       { return $this->belongsTo(School::class); }
    public function academicYear() { return $this->belongsTo(AcademicYear::class); }
    public function graduatedBy()  { return $this->belongsTo(User::class, 'graduated_by'); }
}
