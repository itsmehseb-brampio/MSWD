<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $primaryKey = 'announcement_id';

    protected $fillable = ['admin_id', 'title', 'message', 'is_pinned'];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
        ];
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function targets()
    {
        return $this->hasMany(AnnouncementTarget::class, 'announcement_id', 'announcement_id');
    }
}
