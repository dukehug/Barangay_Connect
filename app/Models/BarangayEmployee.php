<?php

namespace App\Models;

use App\Enums\EmployeeRole;
use Database\Factories\BarangayEmployeeFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class BarangayEmployee extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<BarangayEmployeeFactory> */
    use HasFactory;

    protected $attributes = [
        'is_active' => true,
    ];

    protected $fillable = [
        'first_name',
        'last_name',
        'middle_name',
        'email',
        'password',
        'contact_number',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => EmployeeRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && in_array($this->role, [EmployeeRole::Admin, EmployeeRole::Staff], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === EmployeeRole::Admin;
    }

    public function getFilamentName(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'staff_id');
    }

    public function documentRequests(): HasMany
    {
        return $this->hasMany(DocumentRequest::class, 'staff_id');
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'staff_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'staff_id');
    }
}
