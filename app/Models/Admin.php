<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = ['name', 'email', 'username', 'password', 'invite_token', 'invite_expires_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'invite_expires_at' => 'datetime',
        ];
    }

    public function hasPerm(string $permission): bool
    {
        $this->loadMissing('permissions');
        return $this->permissions->contains('name', $permission);
    }

    public function hasPendingInvite(): bool
    {
        return $this->username === null
            && $this->invite_token !== null
            && $this->invite_expires_at !== null
            && $this->invite_expires_at->isFuture();
    }
}