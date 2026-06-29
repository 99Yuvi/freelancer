<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->restrictOnDelete();
            $table->foreignId('milestone_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('freelancer_id')->constrained('users')->restrictOnDelete();
            $table->string('razorpay_order_id', 60)->unique();
            $table->string('razorpay_payment_id', 60)->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 12, 2);
            $table->decimal('net_amount', 12, 2);
            $table->char('currency', 3)->default('INR');
            $table->string('invoice_path', 500)->nullable();
            $table->enum('status', ['pending', 'captured', 'failed', 'refunded'])->default('pending');
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
            $table->index('contract_id');
            $table->index('freelancer_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
