<?php

use App\DocumentWorkflow;
use App\Models\ResidentDocumentUpload;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('requests:expire-confirmations', function (): void {
    $count = app(DocumentWorkflow::class)->expire();
    $this->info("Expired {$count} confirmations.");
})->purpose('Release resource holds after resident confirmation deadlines');

Schedule::command('requests:expire-confirmations')->everyMinute()->withoutOverlapping();

Schedule::command('model:prune', ['--model' => [ResidentDocumentUpload::class]])->hourly()->withoutOverlapping();
