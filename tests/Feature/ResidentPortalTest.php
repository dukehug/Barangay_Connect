<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Models\Announcement;
use App\Models\BarangayEmployee;
use App\Models\Complaint;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Resident;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResidentPortalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_and_employees_cannot_access_resident_records(): void
    {
        foreach (['dashboard', 'documents', 'complaints', 'activity', 'profile', 'announcements'] as $page) {
            $this->get('/'.$page)->assertRedirect('/');
        }
        $this->post('/documents', [])->assertRedirect('/');
        $this->actingAs(BarangayEmployee::factory()->create(), 'employee')->get('/documents')->assertRedirect('/');
    }

    public function test_registration_creates_a_resident_and_logs_in_using_resident_guard(): void
    {
        $data = Resident::factory()->make()->only(['first_name', 'last_name', 'email', 'gender', 'resident_address', 'marital_status', 'blood_type']);
        $this->post('/register', [...$data, 'birth_date' => '1995-01-01', 'password' => 'secure-password', 'password_confirmation' => 'secure-password', 'email_verified_at' => now(), 'id' => 12345])
            ->assertRedirect('/dashboard');
        $resident = Resident::where('email', $data['email'])->firstOrFail();
        $this->assertTrue(Hash::check('secure-password', $resident->password));
        $this->assertNull($resident->email_verified_at);
        $this->assertNotEquals(12345, $resident->id);
        $this->assertAuthenticatedAs($resident, 'resident');
        $this->assertGuest('employee');
    }

    public function test_login_rejects_bad_credentials_and_logs_out(): void
    {
        $resident = Resident::factory()->create();
        $this->post('/login', ['email' => $resident->email, 'password' => 'incorrect'])->assertSessionHasErrors('email');
        $this->assertGuest('resident');
        $this->post('/login', ['email' => $resident->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($resident, 'resident');
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest('resident');
    }

    public function test_all_pages_render_and_only_published_announcements_are_visible(): void
    {
        $resident = Resident::factory()->create();
        $published = Announcement::factory()->create(['published' => true, 'posted_at' => now()->subDay()]);
        $draft = Announcement::factory()->create(['published' => false]);
        $future = Announcement::factory()->create(['published' => true, 'posted_at' => now()->addDay()]);
        $this->get('/')->assertOk();
        $this->get('/register')->assertOk();
        $this->actingAs($resident, 'resident');
        foreach (['dashboard', 'documents', 'complaints', 'activity', 'profile', 'announcements'] as $page) {
            $this->get('/'.$page)->assertOk();
        }
        $this->get('/announcements')->assertSee($published->title)->assertDontSee($draft->title)->assertDontSee($future->title);
        $this->get('/announcements/'.$published->id)->assertOk()->assertSee($published->content);
        $this->get('/announcements/'.$draft->id)->assertNotFound();
        $this->get('/announcements/'.$future->id)->assertNotFound();
    }

    public function test_document_submission_uses_logged_in_resident_and_backend_status_is_visible(): void
    {
        $resident = Resident::factory()->create();
        Storage::fake('local');
        $other = Resident::factory()->create();
        $type = DocumentType::factory()->create(['is_active' => true, 'category' => 'certification']);
        $this->actingAs($resident, 'resident')->post('/documents', [
            'document_type_id' => $type->id, 'purpose' => 'Employment clearance',
            'files' => ['photo_id' => UploadedFile::fake()->image('id.jpg')],
            'resident_id' => $other->id, 'staff_id' => 999, 'status' => 'released',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $record = DocumentRequest::sole();
        $this->assertEquals($resident->id, $record->resident_id);
        $this->assertSame(RequestStatus::Pending, $record->status);
        $this->assertNull($record->staff_id);
        $record->update(['status' => RequestStatus::Ready]);
        $this->get('/documents')->assertSee('Ready for pickup')->assertSee('Employment clearance');
        $this->get('/activity')->assertSee('Employment clearance');
        $this->actingAs($other, 'resident')->get('/documents')->assertDontSee('Employment clearance');
        $this->get('/activity')->assertDontSee('Employment clearance');
        $type->update(['is_active' => false]);
        $this->post('/documents', ['document_type_id' => $type->id, 'purpose' => 'Test'])->assertSessionHasErrors('document_type_id');
        $this->assertDatabaseCount('document_requests', 1);
    }

    public function test_complaints_and_attachments_are_private_and_persisted(): void
    {
        Storage::fake('local');
        $resident = Resident::factory()->create();
        $other = Resident::factory()->create();
        $this->actingAs($resident, 'resident')->post('/complaints', [
            'complaint_type' => 'Sanitation', 'content' => 'Waste collection missed',
            'attachment' => UploadedFile::fake()->image('photo.jpg'),
            'resident_id' => $other->id, 'status' => 'resolved',
        ])->assertRedirect('/complaints');
        $record = Complaint::sole();
        $this->assertEquals($resident->id, $record->resident_id);
        $this->assertSame('pending', $record->status->value);
        Storage::disk('local')->assertExists($record->attachment_url);
        $this->get('/complaints/'.$record->id.'/attachment')->assertOk();
        $record->update(['status' => 'resolved']);
        $this->get('/complaints')->assertSee('Resolved')->assertSee($record->reference);
        $this->actingAs($other, 'resident')->get('/complaints')->assertDontSee($record->reference);
        $this->get('/complaints/'.$record->id.'/attachment')->assertNotFound();
    }

    public function test_existing_backend_attachments_remain_accessible_only_to_the_owner(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('public')->put('legacy.jpg', 'existing attachment');
        $resident = Resident::factory()->create();
        $complaint = Complaint::factory()->create(['resident_id' => $resident->id, 'attachment_url' => 'legacy.jpg']);
        $this->actingAs($resident, 'resident')->get('/complaints/'.$complaint->id.'/attachment')->assertOk();
        $this->actingAs(Resident::factory()->create(), 'resident')->get('/complaints/'.$complaint->id.'/attachment')->assertNotFound();
    }

    public function test_validation_rejects_invalid_registration_and_uploads(): void
    {
        $this->post('/register', ['email' => 'invalid', 'password' => 'short'])->assertSessionHasErrors(['email', 'password', 'first_name']);
        $this->assertDatabaseCount('residents', 0);
        $this->actingAs(Resident::factory()->create(), 'resident')->post('/complaints', [
            'complaint_type' => 'Noise', 'content' => 'Loud music', 'attachment' => UploadedFile::fake()->create('script.php', 1, 'application/x-php'),
        ])->assertSessionHasErrors('attachment');
        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_profile_changes_and_photo_persist_without_updating_other_residents(): void
    {
        Storage::fake('local');
        $resident = Resident::factory()->create();
        $other = Resident::factory()->create();
        $data = $resident->only(['first_name', 'last_name', 'email', 'gender', 'resident_address', 'marital_status', 'blood_type', 'contact_number']);
        $this->actingAs($resident, 'resident')->patch('/profile', [
            ...$data, 'first_name' => 'Updated', 'birth_date' => '1990-01-01',
            'photo' => UploadedFile::fake()->image('portrait.png'), 'id' => $other->id,
        ])->assertRedirect('/profile');
        $this->assertSame('Updated', $resident->fresh()->first_name);
        $this->assertNotSame('Updated', $other->fresh()->first_name);
        Storage::disk('local')->assertExists($resident->fresh()->profile_image_url);
        $this->get('/profile/photo')->assertOk();
        $this->patch('/profile', [...$data, 'birth_date' => '1990-01-01', 'email' => $other->email])->assertSessionHasErrors('email');
    }
}
