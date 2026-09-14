<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('service_workers')
            ->whereNotIn('employee_id', DB::table('employees')->select('id'))
            ->delete();

        Schema::table('service_workers', function (Blueprint $table): void {
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_workers', function (Blueprint $table): void {
            $table->dropForeign(['employee_id']);
        });
    }
};
