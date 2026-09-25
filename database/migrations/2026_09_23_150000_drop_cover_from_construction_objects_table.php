<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $disk = Storage::disk((string) config('filesystems.media_disk', 'media'));

        DB::table('construction_objects')
            ->whereNotNull('cover')
            ->orderBy('id')
            ->pluck('id')
            ->each(static fn (int $id): bool => $disk->deleteDirectory("objects/{$id}/cover"));

        Schema::table('construction_objects', function (Blueprint $table): void {
            $table->dropColumn('cover');
        });
    }

    public function down(): void
    {
        Schema::table('construction_objects', function (Blueprint $table): void {
            $table->json('cover')->nullable()->after('actual_finished_at');
        });
    }
};
