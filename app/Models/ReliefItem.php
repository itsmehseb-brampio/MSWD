<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReliefItem extends Model
{
    protected $primaryKey = 'item_id';

    protected $fillable = ['schedule_id', 'item_name', 'quantity', 'unit'];

    public $timestamps = false;
}
