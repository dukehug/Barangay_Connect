<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('residents', function (Blueprint $table) {
            $table->id();

            $table->string('email')->uniqidue();
            $table->string('email_verified_at')->nullable();
            $table->string('password');

            $table->string('first_name')->required();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->required();
            $table->string('gender')->required();
            $table->date('birth_date')->required();

            $table->string('marital_status')->default('Single');
            $table->string('blood_type')->nullable();


            $table->string('contact_number')->nullable();
            $table->string('resident_address')->required();

            //user profile image_url
            $table->string('profile_image_url')->nullable();

            $table->rememberToken();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('residents');
    }
};
