<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReliefSchedule extends Model
{
    protected $primaryKey = 'schedule_id';

    protected $fillable = [
        'title', 'description', 'distribution_date', 'distribution_time', 'location', 'status',
    ];

    protected function casts(): array
    {
        return [
            'distribution_date' => 'date',
        ];
    }

    public function items()
    {
        return $this->hasMany(ReliefItem::class, 'schedule_id', 'schedule_id');
    }

    public function targets()
    {
        return $this->hasMany(ReliefScheduleTarget::class, 'schedule_id', 'schedule_id');
    }
}
