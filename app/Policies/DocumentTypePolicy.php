<?php

namespace App\Policies;

use App\Models\BarangayEmployee;
use App\Models\DocumentType;

class DocumentTypePolicy
{
    public function viewAny(BarangayEmployee $user): bool
    {
        return true;
    }

    public function view(BarangayEmployee $user, DocumentType $record): bool
    {
        return true;
    }

    public function create(BarangayEmployee $user): bool
    {
        return true;
    }

    public function update(BarangayEmployee $user, DocumentType $record): bool
    {
        return true;
    }

    public function delete(BarangayEmployee $user, DocumentType $record): bool
    {
        return $user->isAdmin() && ! $record->documentRequests()->exists();
    }

    public function deleteAny(BarangayEmployee $user): bool
    {
        return false;
    }
}
