<?php

namespace Tests\Feature;

use App\Models\BarangayEmployee;
use App\Models\DocumentType;
use App\Models\Resident;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ModelPersistenceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_accounts_store_hashed_passwords_and_hide_credentials(): void
    {
        $staff = $this->createStaff();
        $resident = $this->createResident();

        foreach ([$staff, $resident] as $account) {
            $account->remember_token = 'secret-token';
            $account->save();
            $account->refresh();

            $this->assertInstanceOf(Authenticatable::class, $account);
            $this->assertTrue(Hash::check('test-password', $account->getAuthPassword()));
            $this->assertArrayNotHasKey('password', $account->toArray());
            $this->assertArrayNotHasKey('remember_token', $account->toArray());
        }

        $this->assertSame('staff', $staff->role);
        $resident->email_verified_at = '2026-10-18 12:30:00';
        $resident->save();
        $resident->refresh();
        $this->assertSame('2000-01-02', $resident->birth_date->format('Y-m-d'));
        $this->assertSame('2026-10-18 12:30:00', $resident->email_verified_at->format('Y-m-d H:i:s'));
    }

    public function test_records_persist_casts_and_resolve_their_owners_in_both_directions(): void
    {
        $staff = $this->createStaff();
        $resident = $this->createResident();
        $type = DocumentType::create([
            'document_type_name' => 'Clearance',
            'document_type_fee' => '12.50',
            'is_active' => 0,
            'is_required_other_documents' => 1,
        ]);
        $announcement = $staff->announcements()->create([
            'title' => 'Office hours', 'content' => 'Open on Monday',
            'category' => 'General', 'published' => 1, 'posted_at' => '2026-10-18 12:30:00',
        ]);
        $complaint = $resident->complaints()->create([
            'staff_id' => $staff->id, 'complaint_type' => 'Noise',
            'content' => 'Loud music', 'attachment_url' => 'complaints/photo.jpg',
        ]);
        $request = $resident->documentRequests()->create([
            'staff_id' => $staff->id, 'document_type_id' => $type->id, 'purpose' => 'Employment',
        ]);
        $log = $staff->auditLogs()->create([
            'action' => 'created', 'table_affected' => 'complaints', 'record_id' => $complaint->id,
        ]);

        $this->assertTrue($announcement->fresh()->staff->is($staff));
        $this->assertTrue($staff->fresh()->announcements->sole()->is($announcement));
        $this->assertTrue($announcement->fresh()->published);
        $this->assertSame('2026-10-18 12:30:00', $announcement->fresh()->posted_at->format('Y-m-d H:i:s'));

        $this->assertSame('Loud music', $complaint->fresh()->content);
        $this->assertSame('complaints/photo.jpg', $complaint->fresh()->attachment_url);
        $this->assertTrue($complaint->fresh()->resident->is($resident));
        $this->assertTrue($complaint->fresh()->staff->is($staff));
        $this->assertTrue($resident->fresh()->complaints->sole()->is($complaint));
        $this->assertTrue($staff->fresh()->complaints->sole()->is($complaint));

        $this->assertTrue($request->fresh()->resident->is($resident));
        $this->assertTrue($request->fresh()->staff->is($staff));
        $this->assertTrue($request->fresh()->documentType->is($type));
        $this->assertTrue($resident->fresh()->documentRequests->sole()->is($request));
        $this->assertTrue($staff->fresh()->documentRequests->sole()->is($request));
        $this->assertTrue($type->fresh()->documentRequests->sole()->is($request));
        $this->assertSame('12.50', $type->fresh()->document_type_fee);
        $this->assertFalse($type->fresh()->is_active);
        $this->assertTrue($type->fresh()->is_required_other_documents);

        $this->assertTrue($log->fresh()->staff->is($staff));
        $this->assertTrue($staff->fresh()->auditLogs->sole()->is($log));
        $log->update(['action' => 'reviewed']);
        $this->assertSame('reviewed', $log->fresh()->action);
        $this->assertNotNull($log->fresh()->created_at);

        $complaint->update(['staff_id' => null]);
        $request->update(['staff_id' => null]);
        $this->assertNull($complaint->fresh()->staff);
        $this->assertNull($request->fresh()->staff);
    }

    private function createStaff(): BarangayEmployee
    {
        return BarangayEmployee::create([
            'first_name' => 'Ana', 'last_name' => 'Cruz',
            'email' => 'staff@example.test', 'password' => 'test-password', 'role' => 'staff',
        ]);
    }

    private function createResident(): Resident
    {
        return Resident::create([
            'first_name' => 'Juan', 'last_name' => 'Santos',
            'email' => 'resident@example.test', 'password' => 'test-password',
            'gender' => 'Male', 'birth_date' => '2000-01-02', 'resident_address' => '123 Main Street',
        ]);
    }
}
