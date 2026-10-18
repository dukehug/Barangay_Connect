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
        Schema::create('document_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('resident_id')
                  ->constrained('residents')
                  ->restrictOnDelete();

            $table->foreignId('document_type_id')
                  ->constrained('document_types')
                  ->restrictOnDelete();
            


            $table->foreignId('staff_id')
                  ->nullable()
                  ->constrained('barangay_employees')
                  ->nullOnDelete();

            $table->text('purpose');

            $table->string('status')->default('pending');
            
                  


            $table->timestamps(); 
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_requests');
    }
};
