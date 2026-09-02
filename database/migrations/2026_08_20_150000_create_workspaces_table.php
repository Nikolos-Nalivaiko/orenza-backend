<?php

declare(strict_types=1);

use App\Enums\WorkspaceType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 32)->default(WorkspaceType::Personal->value);
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('owner_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('owner_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};
