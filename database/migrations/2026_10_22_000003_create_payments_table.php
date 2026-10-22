<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            // Our reference (ULID), created before the gateway is called; webhooks are matched by it.
            $table->ulid('reference')->unique();
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('KWD');
            // Gateway key from config('payment.gateways'), e.g. "credit_card".
            $table->string('method', 50);
            $table->string('status', 20)->default('pending');
            // The gateway's own id for the charge, when it is known.
            $table->string('gateway_reference')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
