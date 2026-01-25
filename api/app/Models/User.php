<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

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
        'signature_path',
        'signature_updated_at',
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
            'role' => 'string',
        ];
    }

    // Role check methods
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isInspector(): bool
    {
        return $this->role === 'inspector';
    }

    public function isMaintenance(): bool
    {
        return $this->role === 'maintenance';
    }

    public function isManagement(): bool
    {
        return $this->role === 'management';
    }

    public function isYardOperator(): bool
    {
        return $this->role === 'yard_operator';
    }

    // Relationships
    public function inspectionLogs()
    {
        return $this->hasMany(\App\Models\InspectionLog::class, 'inspector_id');
    }

    public function maintenanceJobs()
    {
        return $this->hasMany(\App\Models\MaintenanceJob::class, 'assigned_to');
    }
}

