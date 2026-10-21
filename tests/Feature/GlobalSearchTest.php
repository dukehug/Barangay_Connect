<?php

namespace Tests\Feature;

use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Filament\Resources\BarangayEmployees\BarangayEmployeeResource;
use App\Filament\Resources\DocumentTypes\DocumentTypeResource;
use App\Filament\Resources\Residents\ResidentResource;
use App\Models\Announcement;
use App\Models\BarangayEmployee;
use App\Models\DocumentType;
use App\Models\Resident;
use Filament\Facades\Filament;
use Filament\Livewire\GlobalSearch;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith([Announcement::class, AnnouncementResource::class, 'title'])]
    #[TestWith([BarangayEmployee::class, BarangayEmployeeResource::class, 'first_name'])]
    #[TestWith([DocumentType::class, DocumentTypeResource::class, 'document_type_name'])]
    #[TestWith([Resident::class, ResidentResource::class, 'first_name'])]
    public function test_global_search_returns_matching_records_with_working_links(string $model, string $resource, string $attribute): void
    {
        $this->actingAs(BarangayEmployee::factory()->create(['role' => 'admin', 'is_active' => true]), 'employee');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $record = $model::factory()->create([$attribute => 'SearchableExample']);
        $model::factory()->create([$attribute => 'UnrelatedExample']);

        Livewire::test(GlobalSearch::class)->set('search', 'SearchableExample')
            ->assertSuccessful()->assertSee('SearchableExample')->assertDontSee('UnrelatedExample')
            ->assertSee($resource::getGlobalSearchResultUrl($record), false);
        $results = $resource::getGlobalSearchResults('SearchableExample');
        $this->assertCount(1, $results);
        $this->assertSame('SearchableExample', $results->first()->title);
    }

    #[TestWith(['admin'])]
    #[TestWith(['staff'])]
    public function test_global_search_handles_arbitrary_terms_for_each_employee_role(string $role): void
    {
        $this->actingAs(BarangayEmployee::factory()->create(['role' => $role, 'is_active' => true]), 'employee');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $search = Livewire::test(GlobalSearch::class);
        foreach (['a', 'doc', '123', '%', "O'Brien", '不存在', ''] as $term) {
            $search->set('search', $term)->assertSuccessful();
        }
    }
}
