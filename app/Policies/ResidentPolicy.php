<?php

namespace App\Policies;

use App\Models\BarangayEmployee;
use App\Models\Resident;

class ResidentPolicy
{
    public function viewAny(BarangayEmployee $user): bool
    {
        return true;
    }

    public function view(BarangayEmployee $user, Resident $record): bool
    {
        return true;
    }

    public function create(BarangayEmployee $user): bool
    {
        return false;
    }

    public function update(BarangayEmployee $user, Resident $record): bool
    {
        return false;
    }

    public function delete(BarangayEmployee $user, Resident $record): bool
    {
        return $user->isAdmin() && ! $record->documentRequests()->exists() && ! $record->complaints()->exists();
    }

    public function deleteAny(BarangayEmployee $user): bool
    {
        return false;
    }
}
