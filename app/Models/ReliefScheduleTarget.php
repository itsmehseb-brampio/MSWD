<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReliefScheduleTarget extends Model
{
    protected $fillable = ['schedule_id', 'barangay_id'];

    public $timestamps = false;
}
