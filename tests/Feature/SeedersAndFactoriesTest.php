<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Enums\EmployeeRole;
use App\Enums\RequestStatus;
use App\Models\Announcement;
use App\Models\BarangayEmployee;
use App\Models\Complaint;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Resident;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\WorkflowDemoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class SeedersAndFactoriesTest extends TestCase
{
    public function test_missing_admin_configuration_fails_before_creating_an_account(): void
    {
        config(['barangay.admin_email' => null, 'barangay.admin_password' => null]);
        $this->expectException(InvalidArgumentException::class);
        $this->seed(AdminSeeder::class);
    }

    use LazilyRefreshDatabase;

    public function test_required_seeders_are_repeatable_and_hash_the_admin_password(): void
    {
        config(['barangay.admin_email' => 'seed-admin@example.test', 'barangay.admin_password' => 'unique-test-password']);
        $this->seed([AdminSeeder::class, DocumentTypeSeeder::class]);
        $this->seed([AdminSeeder::class, DocumentTypeSeeder::class]);
        $this->assertDatabaseCount('barangay_employees', 1);
        $this->assertDatabaseCount('document_types', 1);
        $admin = BarangayEmployee::firstOrFail();
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->is_active);
        $this->assertTrue(Hash::check('unique-test-password', $admin->password));
        $this->assertDatabaseHas('document_types', ['document_type_name' => 'Barangay Clearance', 'document_type_fee' => 50, 'processing_days' => 5, 'is_required_other_documents' => false]);
    }

    public function test_demo_data_spans_six_months_and_reuses_existing_related_records(): void
    {
        Storage::fake('public');
        $this->seed(DocumentTypeSeeder::class);
        $this->seed(DemoDataSeeder::class);
        $this->seed(DemoDataSeeder::class);
        $this->assertDatabaseCount('barangay_employees', 2);
        $this->assertDatabaseCount('residents', 15);
        $this->assertDatabaseCount('document_types', 1);
        $this->assertDatabaseCount('document_requests', 30);
        $this->assertDatabaseCount('complaints', 10);
        $this->assertDatabaseCount('announcements', 6);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertCount(6, DocumentRequest::all()->groupBy(fn (DocumentRequest $record): string => $record->created_at->format('Y-m')));
        $this->assertSame(3, Announcement::where('published', true)->count());
        $this->assertFalse(BarangayEmployee::where('email', 'demo-inactive@example.test')->firstOrFail()->is_active);
        Storage::disk('public')->assertExists('demo/seed-evidence.txt');
    }

    #[TestWith([DemoDataSeeder::class])]
    #[TestWith([WorkflowDemoSeeder::class])]
    public function test_demo_seeders_refuse_production_even_when_called_directly(string $seeder): void
    {
        $this->app->instance('env', 'production');
        $this->expectException(LogicException::class);
        app($seeder)->run();
    }

    public function test_production_database_seeder_only_creates_required_records(): void
    {
        $this->app->instance('env', 'production');
        config(['barangay.admin_email' => 'seed-admin@example.test', 'barangay.admin_password' => 'unique-test-password']);
        app(DatabaseSeeder::class)->setContainer(app())->run();
        $this->assertDatabaseCount('barangay_employees', 1);
        $this->assertDatabaseCount('residents', 0);
        $this->assertDatabaseCount('document_requests', 0);
        $this->assertDatabaseCount('complaints', 0);
        $this->assertDatabaseCount('announcements', 0);
    }

    public function test_factories_enforce_status_assignment_and_publication_rules(): void
    {
        $staff = BarangayEmployee::factory()->create();
        $resident = Resident::factory()->create();
        $type = DocumentType::factory()->create();
        foreach (RequestStatus::cases() as $status) {
            $record = DocumentRequest::factory()->create(['resident_id' => $resident->id, 'document_type_id' => $type->id, 'status' => $status]);
            $this->assertSame($status, $record->status);
            $this->assertSame($status === RequestStatus::Pending ? null : $staff->id, $record->staff_id);
        }
        foreach (ComplaintStatus::cases() as $status) {
            $record = Complaint::factory()->create(['resident_id' => $resident->id, 'status' => $status->value]);
            $this->assertSame($status, $record->status);
            $this->assertSame($status === ComplaintStatus::Pending ? null : $staff->id, $record->staff_id);
        }
        $this->assertNull(Announcement::factory()->create(['published' => false])->posted_at);
        $this->assertNotNull(Announcement::factory()->create(['published' => true])->posted_at);
        $this->assertSame(EmployeeRole::Admin, BarangayEmployee::factory()->admin()->create()->role);
        $this->assertFalse(BarangayEmployee::factory()->inactive()->create()->is_active);
        $this->assertTrue(Hash::check('password', $staff->password));
        Resident::factory()->count(15)->create();
        $this->assertSame(16, Resident::query()->distinct()->count('contact_number'));
    }
}
