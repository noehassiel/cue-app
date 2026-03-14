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
        Schema::create('recurring_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id');
            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            $table->string('concept');
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 15, 4);
            $table->char('currency', 3);
            $table->string('category', 100)->nullable();
            $table->enum('frequency', ['weekly', 'biweekly', 'monthly', 'custom']);
            $table->tinyInteger('frequency_day')->nullable();
            $table->date('next_date');
            $table->smallInteger('generate_ahead_days')->default(90);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_templates');
    }
};
