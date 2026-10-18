<?php

namespace Database\Seeders;

use App\Enums\EmployeeRole;
use App\Models\BarangayEmployee;
use Illuminate\Database\Seeder;
use InvalidArgumentException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('barangay.admin_email');
        $password = config('barangay.admin_password');

        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! is_string($password) || trim($password) === '') {
            throw new InvalidArgumentException('Set SEED_ADMIN_EMAIL and SEED_ADMIN_PASSWORD before running AdminSeeder.');
        }

        BarangayEmployee::updateOrCreate(['email' => $email], [
            'first_name' => config('barangay.admin_first_name'),
            'last_name' => config('barangay.admin_last_name'),
            'password' => $password,
            'role' => EmployeeRole::Admin,
            'is_active' => true,
        ]);
    }
}
