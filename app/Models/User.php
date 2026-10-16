<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    // Points to lodisv2_db central database
    protected $connection = 'central_auth';
    protected $table = 'users';

    protected $fillable = [
        'employee_id',
        'name',
        'email',
        'password',
        'password_changed_at',
        'role',
        'team_id', 
        'is_active',
        'email_verified_at',
        'failed_login_attempts',
        'locked_until',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'locked_until' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed', // CRITICAL: Must match LODIS password casting
        ];
    }

    // Ito yung kulang na hinahanap ng UserController mo kaya nag-crash
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}