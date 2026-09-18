<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_EVALUATOR = 'evaluator';

    protected $fillable = [
        'name',
        'email',
        'password',
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

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function isEvaluator(): bool
    {
        return $this->hasRole(self::ROLE_EVALUATOR);
    }

    /**
     * Evaluations this user has submitted (as an evaluator).
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function scopeEvaluators($query)
    {
        return $query->role(self::ROLE_EVALUATOR)->orderBy('name');
    }
}
