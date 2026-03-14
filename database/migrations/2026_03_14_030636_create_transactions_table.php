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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id')->index();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            $table->uuid('recurring_template_id')->nullable();
            $table->foreign('recurring_template_id')->references('id')->on('recurring_templates')->nullOnDelete();
            $table->enum('type', ['income', 'expense']);
            $table->string('concept');
            $table->decimal('amount', 15, 4);
            $table->char('currency', 3);
            $table->decimal('exchange_rate', 15, 6)->nullable();
            $table->string('category', 100)->nullable();
            $table->date('projected_date');
            $table->timestamp('confirmed_at')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['workspace_id', 'projected_date']);
            $table->index(['workspace_id', 'confirmed_at']);
            $table->index(['workspace_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
