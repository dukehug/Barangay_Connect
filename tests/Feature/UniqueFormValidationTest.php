<?php

namespace Tests\Feature;

use App\Filament\Resources\BarangayEmployees\Pages\CreateBarangayEmployee;
use App\Filament\Resources\DocumentTypes\Pages\CreateDocumentType;
use App\Models\BarangayEmployee;
use App\Models\DocumentType;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UniqueFormValidationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_duplicate_employee_email_is_a_form_validation_error(): void
    {
        $admin = BarangayEmployee::factory()->admin()->create();
        $this->actingAs($admin, 'employee');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(CreateBarangayEmployee::class)->fillForm([
            'first_name' => 'Demo', 'last_name' => 'Staff', 'role' => 'staff',
            'is_active' => true, 'email' => $admin->email, 'password' => 'test-password',
        ])->call('create')->assertHasFormErrors(['email' => 'unique']);
        $this->assertDatabaseCount('barangay_employees', 1);
    }

    public function test_duplicate_document_type_name_is_a_form_validation_error(): void
    {
        $admin = BarangayEmployee::factory()->admin()->create();
        $type = DocumentType::factory()->create();
        $this->actingAs($admin, 'employee');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(CreateDocumentType::class)->fillForm([
            'document_type_name' => $type->document_type_name,
            'is_active' => true, 'is_required_other_documents' => false,
        ])->call('create')->assertHasFormErrors(['document_type_name' => 'unique']);
        $this->assertDatabaseCount('document_types', 1);
    }
}
