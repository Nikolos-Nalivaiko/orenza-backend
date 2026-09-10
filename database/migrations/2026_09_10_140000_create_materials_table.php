<?php

declare(strict_types=1);

use App\Enums\MaterialBuyer;
use App\Enums\MaterialStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('construction_object_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('unit', 16);
            $table->decimal('quantity', 12, 3)->default(0);
            $table->string('buyer', 32)->default(MaterialBuyer::Contractor->value);

            $table->decimal('cost_price', 14, 2)->nullable();
            $table->decimal('client_price', 14, 2)->nullable();

            $table->string('status', 32)->default(MaterialStatus::Needed->value);
            $table->boolean('approved_by_client')->default(false);

            $table->timestamps();

            $table->index(['construction_object_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
