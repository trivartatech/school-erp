<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'school_id', 'category_id', 'name', 'asset_code', 'brand', 'model_no', 'serial_no',
        'purchase_date', 'purchase_cost', 'supplier', 'warranty_until', 'useful_life_years',
        'condition', 'status', 'notes',
    ];

    protected $casts = [
        'purchase_date'  => 'date',
        'purchase_cost'  => 'decimal:2',
    ];

    public function category()         { return $this->belongsTo(AssetCategory::class, 'category_id'); }
    public function assignments()      { return $this->hasMany(AssetAssignment::class); }
    public function activeAssignment() { return $this->hasOne(AssetAssignment::class)->whereNull('returned_on'); }
    public function maintenanceLogs()  { return $this->hasMany(AssetMaintenance::class); }

    // Current book value (straight-line depreciation)
    public function getCurrentValueAttribute(): float
    {
        if (!$this->purchase_date || !$this->purchase_cost || !$this->useful_life_years) {
            return (float) $this->purchase_cost;
        }
        $yearsOld = now()->diffInYears($this->purchase_date);
        $annualDepreciation = $this->purchase_cost / $this->useful_life_years;
        return max(0, (float) $this->purchase_cost - ($annualDepreciation * $yearsOld));
    }
}
