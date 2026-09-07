<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReliefDistributionDocument extends Model
{
    protected $primaryKey = 'document_id';

    protected $fillable = ['report_id', 'file_path'];

    public $timestamps = false;
}
