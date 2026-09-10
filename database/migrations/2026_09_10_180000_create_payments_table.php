<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('construction_objects', function (Blueprint $table): void {
            $table->decimal('discount_percent', 5, 2)->nullable()->after('status');
            $table->decimal('discount_amount', 14, 2)->nullable()->after('discount_percent');
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('construction_object_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('status', 32)->default(PaymentStatus::Pending->value);
            $table->date('paid_at')->nullable();
            $table->boolean('client_visible')->default(false);

            $table->timestamps();

            $table->index(['construction_object_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');

        Schema::table('construction_objects', function (Blueprint $table): void {
            $table->dropColumn(['discount_percent', 'discount_amount']);
        });
    }
};
