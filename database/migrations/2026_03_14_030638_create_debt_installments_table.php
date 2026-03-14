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
        Schema::create('debt_installments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('debt_id');
            $table->foreign('debt_id')->references('id')->on('debts')->cascadeOnDelete();
            $table->smallInteger('installment_number');
            $table->decimal('amount', 15, 4);
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->uuid('transaction_id')->nullable();
            $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();
            $table->timestamps();

            $table->index(['debt_id', 'due_date']);
            $table->index(['debt_id', 'paid_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debt_installments');
    }
};
