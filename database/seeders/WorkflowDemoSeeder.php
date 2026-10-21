<?php

namespace Database\Seeders;

use App\Enums\ComplaintStatus;
use App\Enums\RequestStatus;
use App\Models\BarangayEmployee;
use App\Models\DocumentType;
use App\Models\Resident;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WorkflowDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \LogicException('WorkflowDemoSeeder is restricted to local and testing environments.');
        }

        Storage::disk('public')->put('demo/complaint-evidence.txt', "DEMO ONLY - Fictional complaint evidence.\nNoise reported near the community hall at 10 PM.\n");

        DB::transaction(function (): void {
            $staff = BarangayEmployee::firstOrCreate(['email' => 'workflow-demo-staff@example.test'], [
                'first_name' => 'Demo', 'last_name' => 'Handler',
                'password' => Str::random(40), 'role' => 'staff',
            ]);
            $ana = Resident::firstOrCreate(['email' => 'workflow-demo-ana@example.test'], [
                'first_name' => 'Ana', 'last_name' => 'DemoSantos', 'gender' => 'Female',
                'birth_date' => '1995-03-12', 'resident_address' => 'Demo address 101',
                'password' => Str::random(40),
            ]);
            $juan = Resident::firstOrCreate(['email' => 'workflow-demo-juan@example.test'], [
                'first_name' => 'Juan', 'last_name' => 'DemoCruz', 'gender' => 'Male',
                'birth_date' => '1990-08-22', 'resident_address' => 'Demo address 202',
                'password' => Str::random(40),
            ]);
            $type = DocumentType::firstOrCreate(['document_type_name' => 'DEMO Barangay Certification'], [
                'category' => 'certification',
                'document_type_description' => 'Fictional document type for workflow acceptance.',
                'document_type_fee' => '50.00', 'is_active' => true,
                'is_required_other_documents' => false, 'processing_days' => 3,
            ]);

            foreach ([RequestStatus::Pending, RequestStatus::Processing, RequestStatus::Ready, RequestStatus::Released, RequestStatus::Rejected] as $status) {
                $ana->documentRequests()->firstOrCreate(['purpose' => 'DEMO request: '.$status->value], [
                    'document_type_id' => $type->id, 'status' => $status,
                    'staff_id' => $status === RequestStatus::Pending ? null : $staff->id,
                ]);
            }
            $juan->documentRequests()->firstOrCreate(['purpose' => 'DEMO request: second pending'], [
                'document_type_id' => $type->id, 'status' => RequestStatus::Pending, 'staff_id' => null,
            ]);

            foreach (ComplaintStatus::cases() as $status) {
                $ana->complaints()->firstOrCreate(['content' => 'DEMO complaint: '.$status->value], [
                    'complaint_type' => 'Noise', 'status' => $status,
                    'staff_id' => $status === ComplaintStatus::Pending ? null : $staff->id,
                    'attachment_url' => 'demo/complaint-evidence.txt',
                ]);
            }
            $juan->complaints()->firstOrCreate(['content' => 'DEMO complaint: second pending'], [
                'complaint_type' => 'Sanitation', 'status' => ComplaintStatus::Pending,
                'staff_id' => null, 'attachment_url' => null,
            ]);
        });
    }
}
