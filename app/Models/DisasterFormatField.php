<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisasterFormatField extends Model
{
    protected $fillable = ['field_label', 'field_name', 'field_type', 'field_options', 'is_required', 'field_order'];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'field_order' => 'integer',
        ];
    }
}
