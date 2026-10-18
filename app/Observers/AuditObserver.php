<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\BarangayEmployee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditObserver
{
    public function created(Model $model): void
    {
        $this->record($model, 'created');
    }

    public function updated(Model $model): void
    {
        $changes = array_diff(array_keys($model->getChanges()), ['updated_at']);

        if ($changes === [] || ($model instanceof BarangayEmployee && array_diff($changes, ['remember_token']) === [])) {
            return;
        }

        $this->record($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted');
    }

    private function record(Model $model, string $action): void
    {
        $staffId = Auth::guard('employee')->id();

        if ($staffId === null) {
            return;
        }

        AuditLog::create([
            'staff_id' => $staffId,
            'action' => $action,
            'table_affected' => $model->getTable(),
            'record_id' => $model->getKey(),
        ]);
    }
}
