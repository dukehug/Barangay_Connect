<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\BarangayEmployee;

class AuditLogPolicy
{
    public function viewAny(BarangayEmployee $user): bool
    {
        return $user->isAdmin();
    }

    public function view(BarangayEmployee $user, AuditLog $record): bool
    {
        return $user->isAdmin();
    }

    public function create(BarangayEmployee $user): bool
    {
        return false;
    }

    public function update(BarangayEmployee $user, AuditLog $record): bool
    {
        return false;
    }

    public function delete(BarangayEmployee $user, AuditLog $record): bool
    {
        return false;
    }

    public function deleteAny(BarangayEmployee $user): bool
    {
        return false;
    }
}
