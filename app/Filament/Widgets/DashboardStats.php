<?php

namespace App\Filament\Widgets;

use App\Enums\ComplaintStatus;
use App\Enums\RequestStatus;
use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Filament\Resources\Complaints\ComplaintResource;
use App\Filament\Resources\DocumentRequests\DocumentRequestResource;
use App\Filament\Resources\Residents\ResidentResource;
use App\Models\Announcement;
use App\Models\Complaint;
use App\Models\DocumentRequest;
use App\Models\Resident;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $pendingRequests = DocumentRequest::where('status', RequestStatus::Pending)->count();
        $openComplaints = Complaint::whereIn('status', [ComplaintStatus::Pending, ComplaintStatus::InProgress])->count();

        return [
            Stat::make('Pending document requests', $pendingRequests)
                ->description('Pending Document Requests')
                ->descriptionIcon('heroicon-o-document-text')
                ->color($pendingRequests > 0 ? 'primary' : 'gray')
                ->url(DocumentRequestResource::getUrl('index')),
            Stat::make('Open complaints', $openComplaints)
                ->description('Pending and in-progress complaints')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color($openComplaints > 0 ? 'danger' : 'gray')
                ->url(ComplaintResource::getUrl('index')),
            Stat::make('Published announcements', Announcement::where('published', true)->count())
                ->description('Total Published Announcements')
                ->descriptionIcon('heroicon-o-megaphone')
                ->color('success')
                ->url(AnnouncementResource::getUrl('index')),
            Stat::make('Registered residents', Resident::count())
                ->description('Total number of residents')
                ->descriptionIcon('heroicon-o-users')
                ->color('warning')
                ->url(ResidentResource::getUrl('index')),
        ];
    }
}
