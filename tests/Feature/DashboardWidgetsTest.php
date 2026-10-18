<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Filament\Resources\Complaints\ComplaintResource;
use App\Filament\Resources\DocumentRequests\DocumentRequestResource;
use App\Filament\Resources\DocumentRequests\Pages\EditDocumentRequest;
use App\Filament\Resources\Residents\ResidentResource;
use App\Filament\Widgets\DashboardStats;
use App\Filament\Widgets\LatestPendingRequests;
use App\Filament\Widgets\RequestsChart;
use App\Models\Announcement;
use App\Models\BarangayEmployee;
use App\Models\Complaint;
use App\Models\DocumentRequest;
use Carbon\Carbon;
use Database\Seeders\WorkflowDemoSeeder;
use Filament\Facades\Filament;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class DashboardWidgetsTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function prepareDemo(): void
    {
        Storage::fake('public');
        $this->seed(WorkflowDemoSeeder::class);
        $this->actingAs(BarangayEmployee::firstOrFail(), 'employee');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_cards_count_open_work_and_link_to_the_correct_lists(): void
    {
        $this->prepareDemo();
        Announcement::create(['staff_id' => Filament::auth()->id(), 'title' => 'Published', 'content' => 'Demo', 'category' => 'General', 'published' => true]);
        Announcement::create(['staff_id' => Filament::auth()->id(), 'title' => 'Draft', 'content' => 'Demo', 'category' => 'General', 'published' => false]);
        $widget = Livewire::test(DashboardStats::class)->assertSuccessful();
        $stats = (new ReflectionMethod(DashboardStats::class, 'getStats'))->invoke($widget->instance());
        $this->assertSame([2, 3, 1, 2], array_map(fn ($stat) => $stat->getValue(), $stats));
        $this->assertSame('primary', $stats[0]->getColor());
        $this->assertSame('danger', $stats[1]->getColor());
        $this->assertSame([
            DocumentRequestResource::getUrl('index'), ComplaintResource::getUrl('index'),
            AnnouncementResource::getUrl('index'), ResidentResource::getUrl('index'),
        ], array_map(fn ($stat) => $stat->getUrl(), $stats));
        $this->assertStringNotContainsString('wire:poll', $widget->html());
    }

    public function test_zero_backlog_uses_gray_color(): void
    {
        $this->prepareDemo();
        DocumentRequest::query()->update(['status' => 'released']);
        Complaint::query()->update(['status' => 'resolved']);
        $widget = Livewire::test(DashboardStats::class);
        $stats = (new ReflectionMethod(DashboardStats::class, 'getStats'))->invoke($widget->instance());
        $this->assertSame(0, $stats[0]->getValue());
        $this->assertSame(0, $stats[1]->getValue());
        $this->assertSame('gray', $stats[0]->getColor());
        $this->assertSame('gray', $stats[1]->getColor());
    }

    public function test_chart_keeps_six_months_including_empty_months_at_month_end(): void
    {
        $this->travelTo(Carbon::parse('2026-03-31 12:00:00'));
        $this->prepareDemo();
        $dates = ['2025-09-30 23:59:59', '2025-10-01 00:00:00', '2025-12-31 23:59:59', '2026-01-01 00:00:00', '2026-03-31 10:00:00', '2026-04-01 00:00:00'];
        foreach (DocumentRequest::orderBy('id')->get() as $index => $record) {
            $record->forceFill(['created_at' => $dates[$index]])->save();
        }
        $widget = Livewire::test(RequestsChart::class)->assertSuccessful();
        $data = (new ReflectionMethod(RequestsChart::class, 'getData'))->invoke($widget->instance());
        $this->assertSame(['Oct 2025', 'Nov 2025', 'Dec 2025', 'Jan 2026', 'Feb 2026', 'Mar 2026'], $data['labels']);
        $this->assertSame([1, 0, 1, 1, 0, 1], $data['datasets'][0]['data']);
        $this->assertStringNotContainsString('wire:poll', $widget->html());
    }

    public function test_latest_table_limits_orders_and_links_pending_requests(): void
    {
        $this->prepareDemo();
        $records = DocumentRequest::orderBy('id')->get();
        foreach ($records as $index => $record) {
            $record->forceFill(['status' => RequestStatus::Pending, 'created_at' => now()->subDays(6 - $index)])->save();
        }
        $processed = $records->last()->replicate();
        $processed->status = RequestStatus::Processing;
        $processed->save();
        $expected = $records->reverse()->take(5)->values();
        $widget = Livewire::test(LatestPendingRequests::class)->assertSuccessful()
            ->assertCanSeeTableRecords($expected, inOrder: true)
            ->assertCanNotSeeTableRecords([$records->first(), $processed]);
        $this->assertCount(5, $widget->instance()->getTableRecords());
        $this->assertFalse($widget->instance()->getTable()->isPaginated());
        foreach ($expected as $record) {
            $this->assertSame(DocumentRequestResource::getUrl('edit', ['record' => $record]), $widget->instance()->getTable()->getRecordUrl($record));
        }
        $this->assertStringNotContainsString('wire:poll', $widget->html());
    }

    public function test_processing_removes_pending_row_and_decreases_stats_on_return(): void
    {
        $this->prepareDemo();
        $record = DocumentRequest::where('status', RequestStatus::Pending)->firstOrFail();
        Livewire::test(EditDocumentRequest::class, ['record' => $record->id])
            ->fillForm(['status' => 'processing'])->call('save')->assertHasNoFormErrors();
        $widget = Livewire::test(DashboardStats::class);
        $stats = (new ReflectionMethod(DashboardStats::class, 'getStats'))->invoke($widget->instance());
        $this->assertSame(1, $stats[0]->getValue());
        Livewire::test(LatestPendingRequests::class)->assertCanNotSeeTableRecords([$record]);
    }

    public function test_dashboard_discovers_custom_widgets_in_order_without_default_widgets(): void
    {
        $this->prepareDemo();
        $widgets = Filament::getPanel('admin')->getWidgets();
        $this->assertNotContains(AccountWidget::class, $widgets);
        $this->assertNotContains(FilamentInfoWidget::class, $widgets);
        $this->assertSame([DashboardStats::class, LatestPendingRequests::class, RequestsChart::class], array_values($widgets));
        $this->get('/admin')->assertOk();
    }
}
