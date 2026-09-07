<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangayContact extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'barangay_id';

    public $timestamps = false;

    protected $fillable = [
        'barangay_id', 'barangay_hall_phone', 'barangay_chairman_phone', 'barangay_secretary_phone',
        'barangay_tanod_phone', 'police_hotline', 'fire_hotline',
    ];
}
