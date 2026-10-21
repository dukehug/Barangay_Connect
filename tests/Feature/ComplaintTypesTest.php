<?php

namespace Tests\Feature;

use App\Filament\Resources\ComplaintTypes\Pages\ManageComplaintTypes;
use App\Models\BarangayEmployee;
use App\Models\Complaint;
use App\Models\ComplaintType;
use App\Models\Resident;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ComplaintTypesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_only_active_configured_types_can_be_submitted(): void
    {
        $active = ComplaintType::factory()->create(['name' => 'Street lights']);
        $inactive = ComplaintType::factory()->create(['name' => 'Disabled category', 'is_active' => false]);
        $resident = Resident::factory()->create();
        $this->actingAs($resident, 'resident')->get(route('resident.complaints'))->assertSee('Street lights')->assertDontSee('Disabled category')->assertSee('<select id="complaint_type"', false);
        foreach ([$inactive->name, 'Unconfigured category'] as $name) {
            $this->post(route('resident.complaints.store'), ['complaint_type' => $name, 'content' => 'An issue'])->assertSessionHasErrors('complaint_type');
        }
        $this->post(route('resident.complaints.store'), ['complaint_type' => $active->name, 'content' => 'A broken street light'])->assertSessionHasNoErrors();
        $complaint = Complaint::where('resident_id', $resident->id)->sole();
        $this->assertSame($active->id, $complaint->complaint_type_id);
        $active->update(['name' => 'Lighting', 'is_active' => false]);
        $this->assertSame('Street lights', $complaint->fresh()->complaint_type);
    }

    public function test_admin_can_manage_types_but_staff_cannot_and_used_types_cannot_be_deleted(): void
    {
        $staff = BarangayEmployee::factory()->create(['role' => 'staff']);
        $this->actingAs($staff, 'employee');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(ManageComplaintTypes::class)->assertForbidden();
        $staff->update(['role' => 'admin']);
        Livewire::test(ManageComplaintTypes::class)->callAction('create', data: ['name' => 'Stray animals', 'sort_order' => 8, 'is_active' => true])->assertHasNoActionErrors();
        $type = ComplaintType::where('name', 'Stray animals')->sole();
        Complaint::factory()->create(['complaint_type' => $type->name, 'complaint_type_id' => $type->id]);
        Livewire::test(ManageComplaintTypes::class)->assertTableActionHidden('delete', $type)
            ->callTableAction('edit', $type, data: ['name' => 'Animal welfare', 'sort_order' => 2, 'is_active' => false])->assertHasNoTableActionErrors();
        $this->assertFalse($type->fresh()->is_active);
    }
}
