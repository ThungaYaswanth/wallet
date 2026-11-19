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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('account_holder_name');
            $table->text('account_number'); // Encrypted string
            $table->string('account_number_last4', 4);
            $table->string('account_number_hash', 64);
            $table->string('ifsc_code', 11);
            $table->string('bank_name')->nullable();
            $table->string('branch_name')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->string('verification_status', 30)->default('pending'); // pending, valid, invalid, failed
            $table->string('verification_reference_id')->nullable();
            $table->string('name_at_bank')->nullable();
            $table->string('cashfree_beneficiary_id')->nullable()->unique();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'account_number_hash']);
            $table->index(['user_id', 'is_verified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
