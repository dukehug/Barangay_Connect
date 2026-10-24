<?php

namespace Tests\Feature;

use App\Enums\ComplaintStatus;
use App\Filament\Resources\Complaints\Pages\EditComplaint;
use App\Models\BarangayEmployee;
use App\Models\Complaint;
use App\Models\ComplaintType;
use App\Models\Resident;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ComplaintLocationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_resident_can_submit_coordinates_and_only_the_owner_can_view_them(): void
    {
        $resident = Resident::factory()->create();
        $type = ComplaintType::factory()->create();
        $this->actingAs($resident, 'resident')->post(route('resident.complaints.store'), [
            'complaint_type' => $type->name,
            'content' => 'A streetlight needs repair.',
            'latitude' => '14.4297000',
            'longitude' => '120.9367000',
        ])->assertSessionHasNoErrors()->assertRedirect(route('resident.complaints'));

        $complaint = Complaint::sole();
        $this->assertSame('14.4297000', $complaint->latitude);
        $this->assertSame('120.9367000', $complaint->longitude);
        $this->assertSame('https://www.google.com/maps/search/?api=1&query=14.4297000%2C120.9367000', $complaint->googleMapsUrl());
        $this->get(route('resident.complaints.show', $complaint))->assertOk()->assertSee($complaint->googleMapsUrl());
        $this->actingAs(Resident::factory()->create(), 'resident')->get(route('resident.complaints.show', $complaint))->assertNotFound();
    }

    public function test_location_is_optional(): void
    {
        $type = ComplaintType::factory()->create();
        $this->actingAs(Resident::factory()->create(), 'resident')->post(route('resident.complaints.store'), [
            'complaint_type' => $type->name, 'content' => 'Missed collection.', 'latitude' => '', 'longitude' => '',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $complaint = Complaint::sole();
        $this->assertNull($complaint->latitude);
        $this->assertNull($complaint->longitude);
        $this->assertNull($complaint->googleMapsUrl());
    }

    public function test_validation_errors_preserve_the_selected_coordinates(): void
    {
        $type = ComplaintType::factory()->create();
        $this->actingAs(Resident::factory()->create(), 'resident')->from(route('resident.complaints'))
            ->post(route('resident.complaints.store'), [
                'complaint_type' => $type->name, 'content' => '', 'latitude' => '14.4297', 'longitude' => '120.9367',
            ])->assertSessionHasErrors('content')->assertRedirect(route('resident.complaints'));
        $this->get(route('resident.complaints'))->assertOk()
            ->assertSee('value="14.4297"', false)->assertSee('value="120.9367"', false);
        $this->assertDatabaseCount('complaints', 0);
    }

    /** @param array<string, mixed> $coordinates */
    #[DataProvider('invalidCoordinates')]
    public function test_invalid_or_incomplete_coordinates_are_rejected(array $coordinates, string $field): void
    {
        $type = ComplaintType::factory()->create();
        $this->actingAs(Resident::factory()->create(), 'resident')->from(route('resident.complaints'))
            ->post(route('resident.complaints.store'), [
                'complaint_type' => $type->name, 'content' => 'Please inspect this location.', ...$coordinates,
            ])->assertSessionHasErrors($field)->assertRedirect(route('resident.complaints'));
        $this->assertDatabaseCount('complaints', 0);
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidCoordinates(): array
    {
        return [
            'latitude too high' => [['latitude' => 90.1, 'longitude' => 120], 'latitude'],
            'latitude too low' => [['latitude' => -90.1, 'longitude' => 120], 'latitude'],
            'longitude too high' => [['latitude' => 14, 'longitude' => 180.1], 'longitude'],
            'longitude too low' => [['latitude' => 14, 'longitude' => -180.1], 'longitude'],
            'missing latitude' => [['longitude' => 0], 'latitude'],
            'missing longitude' => [['latitude' => 0], 'longitude'],
            'non-numeric latitude' => [['latitude' => 'not a number', 'longitude' => 120], 'latitude'],
            'array longitude' => [['latitude' => 14, 'longitude' => [120]], 'longitude'],
        ];
    }

    public function test_zero_coordinates_are_valid_and_admin_review_preserves_the_location(): void
    {
        $complaint = Complaint::factory()->create(['status' => ComplaintStatus::Pending, 'latitude' => 0, 'longitude' => 0]);
        $staff = BarangayEmployee::factory()->create(['role' => 'staff', 'is_active' => true]);
        $this->actingAs($staff, 'employee');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(EditComplaint::class, ['record' => $complaint->id])->assertSee('Location')
            ->assertSee('Open in Google Maps')->assertSee($complaint->googleMapsUrl())
            ->fillForm(['status' => 'in_progress', 'review_note' => 'We are investigating.'])
            ->call('save')->assertHasNoFormErrors();

        $this->assertSame('0.0000000', $complaint->fresh()->latitude);
        $this->assertSame('0.0000000', $complaint->fresh()->longitude);
        $this->assertSame(ComplaintStatus::InProgress, $complaint->fresh()->status);
    }

    public function test_existing_complaints_show_no_location_in_admin(): void
    {
        $complaint = Complaint::factory()->create(['status' => ComplaintStatus::Pending]);
        $this->actingAs(BarangayEmployee::factory()->create(['role' => 'staff', 'is_active' => true]), 'employee');
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(EditComplaint::class, ['record' => $complaint->id])
            ->assertSee('No location provided')->assertDontSee('Open in Google Maps');
    }
}
