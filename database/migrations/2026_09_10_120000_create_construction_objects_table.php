<?php

declare(strict_types=1);

use App\Enums\ObjectStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('construction_objects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('address');
            $table->string('status', 32)->default(ObjectStatus::Planned->value);

            $table->date('started_at')->nullable();
            $table->date('finished_at')->nullable();
            $table->date('actual_started_at')->nullable();
            $table->date('actual_finished_at')->nullable();

            $table->string('cover_path')->nullable();
            $table->string('public_token', 32)->unique();
            $table->timestamp('archived_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'name']);
            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'archived_at']);
            $table->index(['workspace_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('construction_objects');
    }
};
