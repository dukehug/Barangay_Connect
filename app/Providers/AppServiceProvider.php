<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\BarangayEmployee;
use App\Models\Complaint;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Resident;
use App\Observers\AuditObserver;
use BladeUI\Icons\Factory as IconFactory;
use Filament\Actions\View\ActionsIconAlias;
use Filament\Support\Facades\FilamentIcon;
use Filament\View\PanelsIconAlias;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->callAfterResolving(IconFactory::class, function (IconFactory $factory): void {
            $factory->add('lucide', [
                'path' => resource_path('icons/lucide'),
                'prefix' => 'lucide',
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentIcon::register([
            PanelsIconAlias::PAGES_DASHBOARD_NAVIGATION_ITEM => 'lucide-layout-dashboard',
            ActionsIconAlias::EDIT_ACTION => 'lucide-pencil',
            ActionsIconAlias::VIEW_ACTION => 'lucide-eye',
            ActionsIconAlias::DELETE_ACTION => 'lucide-trash-2',
            ActionsIconAlias::DELETE_ACTION_MODAL => 'lucide-trash-2',
        ]);

        foreach ([Announcement::class, DocumentRequest::class, Complaint::class, DocumentType::class, BarangayEmployee::class, Resident::class] as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}
