<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The guard name for roles and permissions
     */
    protected $guard_name = 'api';

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'last_login_at',
        'deactivated_at',
        'deactivation_reason',
        'registration_status',
        'registration_date',
        'admin_notes',
        'approved_at',
        'rejected_at',
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
            'registration_date' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Get user's permissions as array
     */
    public function getPermissionsArray(): array
    {
        return $this->getAllPermissions()->pluck('name')->toArray();
    }

    /**
     * Get user's roles as array
     */
    public function getRolesArray(): array
    {
        return $this->getRoleNames()->toArray();
    }

    /**
     * Check if user account is active
     */
    public function isActive(): bool
    {
        return $this->is_active ?? true;
    }

    /**
     * Check if user account is deactivated
     */
    public function isDeactivated(): bool
    {
        return !($this->is_active ?? true);
    }

    /**
     * Get deactivation reason
     */
    public function getDeactivationReason(): ?string
    {
        return $this->deactivation_reason;
    }

    /**
     * Get deactivation date
     */
    public function getDeactivatedAt(): ?string
    {
        return $this->deactivated_at;
    }

    /**
     * Get last login date
     */
    public function getLastLoginAt(): ?string
    {
        return $this->last_login_at;
    }
}
