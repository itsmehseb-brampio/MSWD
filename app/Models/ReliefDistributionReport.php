<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReliefDistributionReport extends Model
{
    protected $primaryKey = 'report_id';

    protected $fillable = ['schedule_id', 'barangay_id', 'confirmed_by', 'narrative', 'received_at'];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
        ];
    }

    public function documents()
    {
        return $this->hasMany(ReliefDistributionDocument::class, 'report_id', 'report_id');
    }

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id', 'barangay_id');
    }
}
