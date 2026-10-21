<?php

namespace App\Policies;

use App\Models\BarangayEmployee;
use App\Models\ResourceItem;

class ResourceItemPolicy
{
    public function viewAny(BarangayEmployee $user): bool
    {
        return $user->is_active;
    }

    public function view(BarangayEmployee $user, ResourceItem $record): bool
    {
        return $user->is_active;
    }

    public function create(BarangayEmployee $user): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function update(BarangayEmployee $user, ResourceItem $record): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function delete(BarangayEmployee $user, ResourceItem $record): bool
    {
        return false;
    }
}
