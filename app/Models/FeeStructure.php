<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FeeStructure extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id', 'academic_year_id', 'class_id', 'fee_head_id',
        'term', 'amount', 'late_fee_per_day', 'due_date',
        'is_optional', 'student_type', 'gender',
        'effective_from', 'effective_to', 'superseded_by', 'change_reason',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'late_fee_per_day' => 'decimal:2',
        'due_date'         => 'date',
        'is_optional'      => 'boolean',
        'effective_from'   => 'date',
        'effective_to'     => 'date',
    ];

    public function feeHead()    { return $this->belongsTo(FeeHead::class); }
    public function courseClass(){ return $this->belongsTo(CourseClass::class, 'class_id'); }
    public function academicYear(){ return $this->belongsTo(AcademicYear::class); }
    public function supersededBy(){ return $this->belongsTo(FeeStructure::class, 'superseded_by'); }

    // All historical versions for this combination
    public static function historyFor(int $schoolId, int $academicYearId, int $classId, int $feeHeadId, string $term)
    {
        return static::withTrashed()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('class_id', $classId)
            ->where('fee_head_id', $feeHeadId)
            ->where('term', $term)
            ->orderByDesc('effective_from')
            ->get();
    }
}
