<?php

namespace App\Policies;

use App\Models\BarangayEmployee;

class BarangayEmployeePolicy
{
    public function viewAny(BarangayEmployee $user): bool
    {
        return $user->isAdmin();
    }

    public function view(BarangayEmployee $user, BarangayEmployee $record): bool
    {
        return $user->isAdmin();
    }

    public function create(BarangayEmployee $user): bool
    {
        return $user->isAdmin();
    }

    public function update(BarangayEmployee $user, BarangayEmployee $record): bool
    {
        return $user->isAdmin();
    }

    public function delete(BarangayEmployee $user, BarangayEmployee $record): bool
    {
        return false;
    }

    public function deleteAny(BarangayEmployee $user): bool
    {
        return false;
    }
}
