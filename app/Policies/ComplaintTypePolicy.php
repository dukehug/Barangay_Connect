<?php

namespace App\Policies;

use App\Models\BarangayEmployee;
use App\Models\Complaint;
use App\Models\ComplaintType;

class ComplaintTypePolicy
{
    public function viewAny(BarangayEmployee $user): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function view(BarangayEmployee $user, ComplaintType $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(BarangayEmployee $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(BarangayEmployee $user, ComplaintType $record): bool
    {
        return $this->viewAny($user);
    }

    public function delete(BarangayEmployee $user, ComplaintType $record): bool
    {
        return $this->viewAny($user) && ! $record->complaints()->exists()
            && ! Complaint::where('complaint_type', $record->name)->exists();
    }

    public function deleteAny(BarangayEmployee $user): bool
    {
        return false;
    }
}
