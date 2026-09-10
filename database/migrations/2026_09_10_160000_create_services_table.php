<?php

declare(strict_types=1);

use App\Enums\ServiceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('construction_object_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit', 16);
            $table->decimal('planned_volume', 12, 3)->default(0);
            $table->decimal('actual_volume', 12, 3)->nullable();
            $table->decimal('client_price', 14, 2)->nullable();
            $table->string('status', 32)->default(ServiceStatus::Planned->value);

            $table->timestamps();

            $table->index(['construction_object_id', 'status']);
        });

        Schema::create('service_workers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('employee_id')->index();
            $table->decimal('volume', 12, 3)->default(0);
            $table->decimal('rate', 14, 2)->default(0);

            $table->timestamps();

            $table->unique(['service_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_workers');
        Schema::dropIfExists('services');
    }
};
