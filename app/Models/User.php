<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'branch_name',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ── Role Helpers ────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Role badge CSS class for Tailwind
     */
    public function getRoleBadgeAttribute(): string
    {
        return match($this->role) {
            'admin'   => 'bg-purple-100 text-purple-700',
            'manager' => 'bg-blue-100 text-blue-700',
            'agent'   => 'bg-emerald-100 text-emerald-700',
            default   => 'bg-slate-100 text-slate-600',
        };
    }

    // ── Relationships ───────────────────────────────────────────────────

    public function loanApplications(): HasMany
    {
        return $this->hasMany(LoanApplication::class, 'agent_id');
    }

    public function assignedRecoveryCases(): HasMany
    {
        return $this->hasMany(RecoveryCase::class, 'assigned_officer_id');
    }
}
