<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\BarangayEmployee;

class AnnouncementPolicy
{
    public function viewAny(BarangayEmployee $user): bool
    {
        return true;
    }

    public function view(BarangayEmployee $user, Announcement $record): bool
    {
        return true;
    }

    public function create(BarangayEmployee $user): bool
    {
        return true;
    }

    public function update(BarangayEmployee $user, Announcement $record): bool
    {
        return true;
    }

    public function delete(BarangayEmployee $user, Announcement $record): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(BarangayEmployee $user): bool
    {
        return false;
    }
}
