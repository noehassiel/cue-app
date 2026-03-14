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
        Schema::create('fund_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('fund_id');
            $table->foreign('fund_id')->references('id')->on('funds')->cascadeOnDelete();
            $table->uuid('transaction_id')->nullable();
            $table->foreign('transaction_id')->references('id')->on('transactions')->nullOnDelete();
            $table->enum('type', ['deposit', 'withdrawal']);
            $table->decimal('amount', 15, 4);
            $table->string('note', 255)->nullable();
            $table->date('movement_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_movements');
    }
};
