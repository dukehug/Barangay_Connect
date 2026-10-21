<?php

namespace App\Policies;

use App\Models\BarangayEmployee;
use App\Models\DocumentRequest;

class DocumentRequestPolicy
{
    public function viewAny(BarangayEmployee $user): bool
    {
        return true;
    }

    public function view(BarangayEmployee $user, DocumentRequest $record): bool
    {
        return true;
    }

    public function create(BarangayEmployee $user): bool
    {
        return false;
    }

    public function update(BarangayEmployee $user, DocumentRequest $record): bool
    {
        return true;
    }

    public function delete(BarangayEmployee $user, DocumentRequest $record): bool
    {
        return false;
    }

    public function deleteAny(BarangayEmployee $user): bool
    {
        return false;
    }
}
