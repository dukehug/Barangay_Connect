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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')
                  ->constrained('residents')
                  ->restrictOnDelete();
            
            $table->foreignId('staff_id')
                  ->nullable()
                  ->constrained('barangay_employees')
                  ->nullOnDelete();
            
            $table->string('complaint_type');
            $table->text('content');
            $table->string('status')->default('pending');

            $table->string('attachment_url')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
