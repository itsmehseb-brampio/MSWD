<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMessage extends Model
{
    protected $primaryKey = 'gm_id';

    protected $fillable = ['sender_type', 'sender_id', 'message'];
}
