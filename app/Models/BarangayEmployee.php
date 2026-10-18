<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class BarangayEmployee extends Authenticatable implements FilamentUser, HasName
{
    protected $fillable = [
        'first_name',
        'last_name',
        'middle_name',
        'email',
        'password',
        'contact_number',
        'role',
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
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'staff'], true);
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
