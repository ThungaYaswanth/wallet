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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // 'credit', 'debit'
            $table->unsignedBigInteger('amount'); // in paise
            $table->string('reference_id')->unique();
            $table->string('gateway_reference_id')->nullable()->index();
            $table->string('payment_session_id')->nullable();
            $table->string('transaction_type', 40); // 'deposit', 'withdrawal', 'withdrawal_reversal'
            $table->string('status', 20)->default('pending'); // 'pending', 'completed', 'failed'
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['wallet_id', 'type']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
