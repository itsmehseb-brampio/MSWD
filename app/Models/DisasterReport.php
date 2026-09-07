<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisasterReport extends Model
{
    protected $primaryKey = 'report_id';

    protected $fillable = [
        'barangay_id', 'format_no', 'title', 'disaster_type', 'household_head', 'family_members',
        'full_address', 'housing_type', 'damage_extent', 'description', 'captain_name',
        'secretary_name', 'pic1', 'pic2', 'pic3', 'pic4', 'b2b_id', 'status', 'decline_reason',
    ];

    protected function casts(): array
    {
        return [
            'family_members' => 'integer',
        ];
    }

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id', 'barangay_id');
    }

    public function scopeSearch($query, $search)
    {
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('format_no', 'like', "%{$search}%")
                    ->orWhere('household_head', 'like', "%{$search}%")
                    ->orWhere('disaster_type', 'like', "%{$search}%")
                    ->orWhereHas('barangay', fn ($b) => $b->where('barangay_name', 'like', "%{$search}%"));
            });
        }
        return $query;
    }
}
