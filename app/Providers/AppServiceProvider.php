<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\BarangayEmployee;
use App\Models\Complaint;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Resident;
use App\Observers\AuditObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([Announcement::class, DocumentRequest::class, Complaint::class, DocumentType::class, BarangayEmployee::class, Resident::class] as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}
