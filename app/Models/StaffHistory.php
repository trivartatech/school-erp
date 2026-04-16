<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffHistory extends Model
{
    protected $table = 'staff_history';

    protected $fillable = [
        'school_id', 'staff_id', 'event_type',
        'from_designation_id', 'to_designation_id',
        'from_department_id', 'to_department_id',
        'from_salary', 'to_salary',
        'effective_date', 'order_no', 'remarks', 'recorded_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'from_salary'    => 'decimal:2',
        'to_salary'      => 'decimal:2',
    ];

    public function staff()           { return $this->belongsTo(Staff::class); }
    public function fromDesignation() { return $this->belongsTo(Designation::class, 'from_designation_id'); }
    public function toDesignation()   { return $this->belongsTo(Designation::class, 'to_designation_id'); }
    public function fromDepartment()  { return $this->belongsTo(Department::class, 'from_department_id'); }
    public function toDepartment()    { return $this->belongsTo(Department::class, 'to_department_id'); }
    public function recordedBy()      { return $this->belongsTo(User::class, 'recorded_by'); }
}
