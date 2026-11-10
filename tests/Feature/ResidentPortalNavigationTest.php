<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Enums\RequestStatus;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\BarangayEmployee;
use App\Models\Complaint;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Resident;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResidentPortalNavigationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_document_search_matches_type_reference_and_status_without_exposing_other_residents(): void
    {
        $resident = Resident::factory()->create();
        $type = DocumentType::factory()->create(['document_type_name' => 'Residency certificate']);
        $document = DocumentRequest::factory()->create(['resident_id' => $resident->id, 'document_type_id' => $type->id, 'purpose' => 'School enrollment', 'status' => RequestStatus::Ready]);
        DocumentRequest::factory()->create(['document_type_id' => $type->id, 'purpose' => 'Private request', 'status' => RequestStatus::Ready]);
        $this->actingAs($resident, 'resident');

        DB::statement('PRAGMA case_sensitive_like = ON');

        foreach (['Residency', 'rEsIdEnCy', strtolower($document->reference), 'READY FOR PICKUP', 'sChOoL'] as $search) {
            $this->get(route('resident.documents', ['search' => $search]))->assertOk()->assertSee('School enrollment')->assertDontSee('Private request');
        }
        $this->get(route('resident.documents', ['search' => 'unmatched']))->assertOk()->assertSee('No matching requests')->assertDontSee('School enrollment');
    }

    public function test_complaint_search_matches_category_reference_and_status_with_resident_isolation(): void
    {
        $resident = Resident::factory()->create();
        $complaint = Complaint::factory()->create(['resident_id' => $resident->id, 'complaint_type' => 'Sanitation', 'content' => 'Missed collection', 'status' => ComplaintStatus::InProgress]);
        $otherComplaint = Complaint::factory()->create(['complaint_type' => 'Sanitation', 'content' => 'Private complaint', 'status' => ComplaintStatus::InProgress]);
        $this->actingAs($resident, 'resident');

        DB::statement('PRAGMA case_sensitive_like = ON');

        foreach (['Sanitation', 'sAnItAtIoN', strtolower($complaint->reference), 'IN PROGRESS', 'mIsSeD'] as $search) {
            $this->get(route('resident.complaints', ['search' => $search]))->assertOk()->assertSee($complaint->reference)->assertDontSee($otherComplaint->reference);
        }
        $this->get(route('resident.complaints', ['search' => 'unmatched']))->assertOk()->assertSee('No matching complaints');
    }

    public function test_search_pagination_preserves_filters_and_validates_page_size(): void
    {
        $resident = Resident::factory()->create();
        Complaint::factory()->count(3)->create(['resident_id' => $resident->id, 'complaint_type' => 'Noise']);
        $this->actingAs($resident, 'resident');

        $excluded = Complaint::factory()->create(['resident_id' => $resident->id, 'complaint_type' => 'Other', 'content' => 'Noise complaint']);
        $response = $this->get(route('resident.complaints', ['search' => 'Noise', 'category' => 'Noise', 'per_page' => 2]));

        $response->assertOk()->assertSee('Page 1 of 2')->assertSee('search=Noise&amp;category=Noise&amp;per_page=2&amp;page=2', false)->assertDontSee($excluded->reference);
        $this->assertCount(2, $response->viewData('complaints'));
        $this->get(route('resident.complaints', ['per_page' => 999]))->assertSessionHasErrors('per_page');
        $this->get(route('resident.documents', ['search' => ['invalid']]))->assertSessionHasErrors('search');
    }

    public function test_record_search_accepts_200_characters_and_rejects_longer_queries(): void
    {
        $this->actingAs(Resident::factory()->create(), 'resident');

        foreach (['resident.documents', 'resident.complaints'] as $route) {
            $this->get(route($route, ['search' => str_repeat('字', 200)]))
                ->assertOk()->assertSee('maxlength="200"', false);
            $this->getJson(route($route, ['search' => str_repeat('字', 201)]))
                ->assertUnprocessable()->assertJsonValidationErrors('search');
        }
    }

    public function test_document_type_filter_combines_with_search_and_pagination(): void
    {
        $resident = Resident::factory()->create();
        $type = DocumentType::factory()->create(['is_active' => false]);
        $documents = DocumentRequest::factory()->count(3)->create([
            'resident_id' => $resident->id, 'document_type_id' => $type->id,
            'purpose' => 'School enrollment', 'status' => RequestStatus::Pending,
        ]);
        $otherType = DocumentRequest::factory()->create(['resident_id' => $resident->id, 'purpose' => 'School enrollment']);
        $otherSearch = DocumentRequest::factory()->create(['resident_id' => $resident->id, 'document_type_id' => $type->id, 'purpose' => 'Employment']);
        $otherResident = DocumentRequest::factory()->create(['document_type_id' => $type->id, 'purpose' => 'School enrollment']);
        $this->actingAs($resident, 'resident');
        $filters = ['search' => 'sChOoL', 'document_type_id' => $type->id, 'per_page' => 2];

        $response = $this->get(route('resident.documents', $filters))->assertOk()
            ->assertSeeInOrder(['id="recordType"', 'id="pageSize"'], false)
            ->assertDontSee($otherType->reference)->assertDontSee($otherSearch->reference)->assertDontSee($otherResident->reference)
            ->assertSee('document_type_id='.$type->id.'&amp;per_page=2&amp;page=2', false);
        $this->assertSame(3, $response->viewData('documents')->total());
        $this->assertCount(2, $response->viewData('documents'));
        $this->assertTrue($response->viewData('documentTypes')->contains($type));
        $secondPage = $this->get(route('resident.documents', $filters + ['page' => 2]))->assertOk();
        $this->assertCount(1, $secondPage->viewData('documents'));
        $this->assertEqualsCanonicalizing($documents->modelKeys(), array_merge(
            $response->viewData('documents')->getCollection()->modelKeys(),
            $secondPage->viewData('documents')->getCollection()->modelKeys(),
        ));
        $this->getJson(route('resident.documents', ['document_type_id' => 'invalid']))
            ->assertUnprocessable()->assertJsonValidationErrors('document_type_id');
        $this->get(route('resident.documents', ['search' => 'unmatched', 'document_type_id' => $type->id]))
            ->assertOk()->assertSee('No matching requests')->assertSee('Clear filters');
    }

    public function test_complaint_category_filter_supports_historical_categories_and_resident_isolation(): void
    {
        $resident = Resident::factory()->create();
        $complaint = Complaint::factory()->create(['resident_id' => $resident->id, 'complaint_type' => 'Historical category']);
        $otherCategory = Complaint::factory()->create(['resident_id' => $resident->id, 'complaint_type' => 'Other']);
        $otherResident = Complaint::factory()->create(['complaint_type' => 'Historical category']);
        Complaint::factory()->create(['complaint_type' => 'Private category']);
        $this->actingAs($resident, 'resident');

        $response = $this->get(route('resident.complaints', ['category' => 'Historical category']))->assertOk()
            ->assertSeeInOrder(['id="recordType"', 'id="pageSize"'], false)
            ->assertSee($complaint->reference)->assertDontSee($otherCategory->reference)->assertDontSee($otherResident->reference)
            ->assertDontSee('Private category');
        $this->assertSame(1, $response->viewData('complaints')->total());
        $this->assertContains('Historical category', $response->viewData('complaintCategories'));
        $this->get(route('resident.complaints', ['category' => 'Missing category']))
            ->assertOk()->assertSee('No matching complaints')->assertSee('Clear filters');
        $this->getJson(route('resident.complaints', ['category' => ['invalid']]))
            ->assertUnprocessable()->assertJsonValidationErrors('category');
    }

    public function test_document_list_shows_separate_columns_and_the_full_purpose(): void
    {
        $purpose = 'Application for educational assistance and school enrollment for the upcoming academic year.';
        $document = DocumentRequest::factory()->create(['purpose' => $purpose, 'created_at' => '2026-10-12 10:00:00', 'status' => RequestStatus::Ready]);

        $this->actingAs($document->resident, 'resident')->get(route('resident.documents'))->assertOk()
            ->assertSeeInOrder(['Request No.', 'Document Type', 'Date Applied', 'Purpose', 'Status', 'Actions'])
            ->assertSeeInOrder([$document->reference, $document->documentType->document_type_name, 'October 12, 2026', $purpose, 'Ready for pickup'])
            ->assertSee(route('resident.documents.show', $document))
            ->assertSee('Showing 1–1 of 1 entries')->assertSee('Page 1 of 1');
    }

    public function test_complaint_list_reply_date_uses_the_latest_staff_update_only(): void
    {
        $complaint = Complaint::factory()->create(['created_at' => '2026-10-12 10:00:00', 'updated_at' => '2026-10-15 10:00:00', 'status' => ComplaintStatus::Pending]);
        $this->actingAs($complaint->resident, 'resident');

        $this->get(route('resident.complaints'))->assertOk()
            ->assertSeeInOrder(['Complaints No', 'Category', 'Date Submit', 'Date Replyed', 'Status', 'Actions'])
            ->assertSeeInOrder([$complaint->reference, $complaint->complaint_type, 'October 12, 2026', '—'])
            ->assertDontSee('October 15, 2026');

        $staff = BarangayEmployee::factory()->create();
        foreach ([
            ['complaints', $staff->id, '2026-10-13 10:00:00'],
            ['complaints', $staff->id, '2026-10-14 10:00:00'],
            ['residents', $staff->id, '2026-10-16 10:00:00'],
        ] as [$table, $staffId, $date]) {
            AuditLog::create(['table_affected' => $table, 'record_id' => $complaint->id, 'action' => 'updated', 'staff_id' => $staffId])
                ->forceFill(['created_at' => $date])->save();
        }

        $response = $this->get(route('resident.complaints'))->assertOk()
            ->assertSeeInOrder([$complaint->reference, $complaint->complaint_type, 'October 12, 2026', 'October 14, 2026'])
            ->assertDontSee('October 13, 2026')->assertDontSee('October 16, 2026')
            ->assertSee(route('resident.complaints.show', $complaint));
        $this->assertTrue($response->viewData('complaints')->first()->relationLoaded('latestStaffUpdate'));
    }

    public function test_announcement_navigation_only_links_to_published_records_in_display_order(): void
    {
        $this->freezeTime();
        $older = Announcement::factory()->create(['published' => true, 'posted_at' => now()->subDays(3)]);
        $current = Announcement::factory()->create(['published' => true, 'posted_at' => now()->subDays(2)]);
        $newer = Announcement::factory()->create(['published' => true, 'posted_at' => now()->subDay()]);
        $draft = Announcement::factory()->create(['published' => false, 'posted_at' => now()->subHours(12)]);
        $future = Announcement::factory()->create(['published' => true, 'posted_at' => now()->addDay()]);
        $this->actingAs(Resident::factory()->create(), 'resident');

        $this->get(route('resident.announcement', $current))->assertOk()
            ->assertSee(route('resident.announcement', $newer))->assertSee(route('resident.announcement', $older))
            ->assertDontSee(route('resident.announcement', $draft))->assertDontSee(route('resident.announcement', $future));
        $this->get(route('resident.announcement', $newer))->assertOk()->assertViewHas('previousAnnouncement', null);
        $this->get(route('resident.announcement', $older))->assertOk()->assertViewHas('nextAnnouncement', null);
    }

    public function test_record_dialogs_escape_resident_content(): void
    {
        $resident = Resident::factory()->create();
        $content = '<script>alert("resident")</script>';
        $complaint = Complaint::factory()->create(['resident_id' => $resident->id, 'content' => $content]);
        $this->actingAs($resident, 'resident');

        foreach ([route('resident.complaints.show', $complaint), route('resident.activity')] as $url) {
            $this->get($url)->assertOk()->assertSee($content)->assertDontSee($content, false);
        }
    }
}
