<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementTarget extends Model
{
    protected $fillable = ['announcement_id', 'barangay_id'];

    public $timestamps = false;

    public function barangay()
    {
        return $this->belongsTo(Barangay::class, 'barangay_id', 'barangay_id');
    }
}
