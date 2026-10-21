<?php

use App\RequestReference;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_references', function (Blueprint $table): void {
            $table->string('reference', 16)->primary();
        });

        foreach (['document_requests', 'complaints'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('reference', 16)->nullable()->unique();
            });

            DB::table($tableName)->orderBy('id')->each(function (object $record) use ($tableName): void {
                DB::table($tableName)->where('id', $record->id)->update([
                    'reference' => app(RequestReference::class)->generate($record->created_at),
                ]);
            });

            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('reference', 16)->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['document_requests', 'complaints'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropUnique(['reference']);
                $table->dropColumn('reference');
            });
        }
        Schema::dropIfExists('request_references');
    }
};
