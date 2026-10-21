<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table): void {
            $table->string('category')->default('certification');
        });
        DB::table('document_types')->where('document_type_name', 'like', '%Clearance%')->update(['category' => 'clearance']);
        foreach (['Barangay Clearance' => 'clearance', 'Barangay Certification' => 'certification', 'Request for Use / Closure' => 'use_closure'] as $name => $category) {
            DB::table('document_types')->where('document_type_name', $name)->update(['category' => $category]);
            if (! DB::table('document_types')->where('document_type_name', $name)->exists()) {
                DB::table('document_types')->insert(['document_type_name' => $name, 'category' => $category, 'document_type_fee' => 0, 'is_active' => true, 'is_required_other_documents' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        Schema::table('document_requests', function (Blueprint $table): void {
            $table->string('category')->nullable();
            $table->string('application_kind')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamp('confirmation_expires_at')->nullable()->index();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rbi_verified_at')->nullable();
            $table->string('official_receipt')->nullable()->unique();
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->timestamp('paid_at')->nullable();
        });
        foreach (DB::table('document_types')->get(['id', 'category']) as $type) {
            DB::table('document_requests')->where('document_type_id', $type->id)->update(['category' => $type->category]);
        }
        Schema::create('workflow_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('confirmation_hours')->default(48);
            $table->timestamps();
        });
        DB::table('workflow_settings')->insert(['id' => 1, 'confirmation_hours' => 48, 'created_at' => now(), 'updated_at' => now()]);
        Schema::create('resource_items', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('kind');
            $table->unsignedInteger('quantity')->default(1);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::create('request_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_item_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('requested_quantity');
            $table->unsignedInteger('approved_quantity')->default(0);
            $table->unsignedInteger('issued_quantity')->default(0);
            $table->unsignedInteger('returned_quantity')->default(0);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamps();
            $table->unique(['document_request_id', 'resource_item_id']);
            $table->index(['resource_item_id', 'starts_at', 'ends_at']);
        });
        Schema::create('request_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_request_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->string('path');
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('request_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('barangay_employees')->nullOnDelete();
            $table->string('action');
            $table->text('note')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['request_events', 'request_attachments', 'request_reservations', 'resource_items', 'workflow_settings'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('document_requests', function (Blueprint $table): void {
            $table->dropUnique(['official_receipt']);
            $table->dropColumn(['category', 'application_kind', 'review_note', 'confirmation_expires_at', 'accepted_at', 'rbi_verified_at', 'official_receipt', 'amount_paid', 'paid_at']);
        });
        Schema::table('document_types', fn (Blueprint $table) => $table->dropColumn('category'));
    }
};
