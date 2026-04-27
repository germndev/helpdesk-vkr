<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    
    use HasFactory, Notifiable;

    
    protected $fillable = [
        'first_name',
        'last_name',
        'login',
        'name',
        'email',
        'password',
        'role',
        'avatar_path',
    ];

    
    protected $hidden = [
        'password',
        'remember_token',
    ];

    
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getDisplayNameAttribute(): string
    {
        $fullName = trim(($this->last_name ?? '').' '.($this->first_name ?? ''));

        return $fullName !== '' ? $fullName : ($this->name ?? $this->login);
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar_path) {
            return '/storage/'.ltrim($this->avatar_path, '/');
        }

        return '/assets/icons/user.svg';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSupport(): bool
    {
        return $this->role === 'support';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function ticketMessages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }
}
