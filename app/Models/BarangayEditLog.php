<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangayEditLog extends Model
{
    protected $table = 'barangay_edit_log';

    protected $fillable = ['barangay_id', 'field_name', 'edited_at'];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
        ];
    }
}
