<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Barangay extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'barangay_id';

    protected $fillable = ['barangay_name', 'address', 'password', 'last_active', 'disaster_open'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'disaster_open' => 'boolean',
            'last_active' => 'datetime',
        ];
    }

    public function detail()
    {
        return $this->hasOne(BarangayDetail::class, 'barangay_id', 'barangay_id');
    }

    public function contacts()
    {
        return $this->hasOne(BarangayContact::class, 'barangay_id', 'barangay_id');
    }

    public function reports()
    {
        return $this->hasMany(DisasterReport::class, 'barangay_id', 'barangay_id');
    }
}
