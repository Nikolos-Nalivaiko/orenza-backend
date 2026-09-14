<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('object_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('construction_object_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('key', 32)->unique();
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->string('color', 7);
            $table->string('original_name')->nullable();
            $table->timestamp('taken_at')->nullable();

            $table->timestamps();

            $table->index(['construction_object_id', 'taken_at']);
            $table->index(['construction_object_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('object_photos');
    }
};
