<?php

namespace App\Policies;

use App\Models\BarangayEmployee;
use App\Models\Complaint;

class ComplaintPolicy
{
    public function viewAny(BarangayEmployee $user): bool
    {
        return true;
    }

    public function view(BarangayEmployee $user, Complaint $record): bool
    {
        return true;
    }

    public function create(BarangayEmployee $user): bool
    {
        return false;
    }

    public function update(BarangayEmployee $user, Complaint $record): bool
    {
        return true;
    }

    public function delete(BarangayEmployee $user, Complaint $record): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(BarangayEmployee $user): bool
    {
        return false;
    }
}
