<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::table('complaints', function (Blueprint $table): void {
            $table->foreignId('complaint_type_id')->nullable()->constrained()->restrictOnDelete();
        });
        $defaults = ['Noise', 'Sanitation', 'Public Safety', 'Roads and Infrastructure', 'Barangay Services', 'Other'];
        $names = array_unique([...$defaults, ...DB::table('complaints')->distinct()->pluck('complaint_type')->all()]);
        foreach ($names as $index => $name) {
            $id = DB::table('complaint_types')->insertGetId(['name' => $name, 'is_active' => in_array($name, $defaults, true), 'sort_order' => $index + 1, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('complaints')->where('complaint_type', $name)->update(['complaint_type_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('complaints', fn (Blueprint $table) => $table->dropConstrainedForeignId('complaint_type_id'));
        Schema::dropIfExists('complaint_types');
    }
};
