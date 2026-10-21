<?php

namespace App\Policies;

use App\Models\BarangayEmployee;
use App\Models\RequestAttachment;

class RequestAttachmentPolicy
{
    public function viewAny(BarangayEmployee $user): bool
    {
        return $user->is_active;
    }

    public function view(BarangayEmployee $user, RequestAttachment $record): bool
    {
        return $user->is_active;
    }

    public function create(BarangayEmployee $user): bool
    {
        return $user->is_active;
    }

    public function update(BarangayEmployee $user, RequestAttachment $record): bool
    {
        return $user->is_active && $record->documentRequest->isEditable();
    }

    public function delete(BarangayEmployee $user, RequestAttachment $record): bool
    {
        return $this->update($user, $record);
    }

    public function reorder(BarangayEmployee $user): bool
    {
        return $user->is_active;
    }
}
