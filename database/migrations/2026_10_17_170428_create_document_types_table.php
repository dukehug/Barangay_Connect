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
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();


            $table->string('document_type_name', 100)->unique();
            $table->string('document_type_description', 255)->nullable();
            $table->decimal('document_type_fee', 20, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_required_other_documents')->default(false);
            $table->unsignedBigInteger('processing_days')->nullable();            


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
