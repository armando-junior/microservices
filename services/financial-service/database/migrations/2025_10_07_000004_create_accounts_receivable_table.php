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
        Schema::create('accounts_receivable', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id'); // ID do cliente do Sales Service
            $table->uuid('category_id');
            $table->string('description');
            $table->bigInteger('amount_cents')->unsigned(); // Valor em centavos
            $table->date('issue_date');
            $table->date('due_date');
            $table->enum('status', ['pending', 'received', 'overdue', 'cancelled'])->default('pending');
            $table->timestamp('received_at')->nullable();
            $table->text('receiving_notes')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('restrict');

            $table->index('customer_id');
            $table->index('category_id');
            $table->index('status');
            $table->index('due_date');
            $table->index(['status', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts_receivable');
    }
};


