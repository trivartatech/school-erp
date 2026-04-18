<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HouseStudent extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'house_id',
        'student_id',
        'academic_year_id',
        'assigned_by',
    ];

    public function house()
    {
        return $this->belongsTo(House::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
