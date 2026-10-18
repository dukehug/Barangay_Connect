<?php

namespace Database\Seeders;

use App\Enums\ComplaintStatus;
use App\Enums\RequestStatus;
use App\Models\Announcement;
use App\Models\BarangayEmployee;
use App\Models\DocumentType;
use App\Models\Resident;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('DemoDataSeeder is restricted to local and testing environments.');
        }

        Storage::disk('public')->put('demo/seed-evidence.txt', "DEMO ONLY: Fictional complaint evidence.\n");

        DB::transaction(function (): void {
            $staff = BarangayEmployee::firstOrCreate(['email' => 'demo-staff@example.test'],
                BarangayEmployee::factory()->make(['email' => 'demo-staff@example.test', 'first_name' => 'Demo', 'last_name' => 'Staff'])->getAttributes());
            BarangayEmployee::firstOrCreate(['email' => 'demo-inactive@example.test'],
                BarangayEmployee::factory()->inactive()->make(['email' => 'demo-inactive@example.test', 'first_name' => 'Demo', 'last_name' => 'Inactive'])->getAttributes());

            if (! DocumentType::exists()) {
                $this->call(DocumentTypeSeeder::class);
            }
            $types = DocumentType::all();
            $residents = collect();
            for ($i = 1; $i <= 15; $i++) {
                $residents->push(Resident::firstOrCreate(['email' => "demo-resident-{$i}@example.test"],
                    Resident::factory()->make(['email' => "demo-resident-{$i}@example.test"])->getAttributes()));
            }

            for ($i = 0; $i < 30; $i++) {
                $status = RequestStatus::cases()[$i % count(RequestStatus::cases())];
                $request = $residents[$i % 15]->documentRequests()->firstOrCreate(['purpose' => 'DEMO seeded request '.($i + 1)], [
                    'document_type_id' => $types[$i % $types->count()]->id,
                    'status' => $status, 'staff_id' => $status === RequestStatus::Pending ? null : $staff->id,
                ]);
                if ($request->wasRecentlyCreated) {
                    $request->forceFill(['created_at' => now()->startOfMonth()->subMonths(intdiv($i, 5))])->save();
                }
            }

            for ($i = 0; $i < 10; $i++) {
                $status = ComplaintStatus::cases()[$i % count(ComplaintStatus::cases())];
                $residents[$i]->complaints()->firstOrCreate(['content' => 'DEMO seeded complaint '.($i + 1)], [
                    'complaint_type' => $i % 2 === 0 ? 'Noise' : 'Sanitation',
                    'status' => $status, 'staff_id' => $status === ComplaintStatus::Pending ? null : $staff->id,
                    'attachment_url' => $i === 0 ? 'demo/seed-evidence.txt' : null,
                ]);
            }

            for ($i = 1; $i <= 6; $i++) {
                Announcement::firstOrCreate(['title' => 'DEMO seeded announcement '.$i],
                    Announcement::factory()->make(['title' => 'DEMO seeded announcement '.$i, 'staff_id' => $staff->id, 'published' => $i % 2 === 0])->getAttributes());
            }
        });
    }
}
