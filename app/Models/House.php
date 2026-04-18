<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class House extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'name',
        'color',
        'emblem',
        'incharge_staff_id',
        'captain_student_id',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function incharge()
    {
        return $this->belongsTo(User::class, 'incharge_staff_id');
    }

    public function captain()
    {
        return $this->belongsTo(Student::class, 'captain_student_id');
    }

    public function houseStudents()
    {
        return $this->hasMany(HouseStudent::class);
    }

    public function points()
    {
        return $this->hasMany(HousePoint::class);
    }

    public function totalPoints(?int $academicYearId = null): int
    {
        $query = $this->points();
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }
        return (int) $query->sum('points');
    }
}
