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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('staff_id')
                ->constrained('barangay_employees')
                ->restrictOnDelete();
            $table->string('action');

            $table->string('table_affected');

            $table->unsignedBigInteger('record_id');

            $table->timestamp('created_at')->useCurrent();

            $table->index([
                'table_affected',
                'record_id',
            ]);

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
