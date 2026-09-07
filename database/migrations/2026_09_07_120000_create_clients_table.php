<?php

declare(strict_types=1);

use App\Enums\ClientType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32)->default(ClientType::Person->value);
            $table->string('name');
            $table->string('contact')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('discount', 5, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'name']);
            $table->index(['workspace_id', 'type']);
            $table->index(['workspace_id', 'created_at']);
            $table->index(['workspace_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
