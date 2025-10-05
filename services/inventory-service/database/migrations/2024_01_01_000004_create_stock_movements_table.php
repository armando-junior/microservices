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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('stock_id');
            $table->enum('type', ['increase', 'decrease', 'adjustment']);
            $table->integer('quantity');
            $table->integer('previous_quantity');
            $table->integer('new_quantity');
            $table->string('reason', 255);
            $table->string('reference_id', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('stock_id')->references('id')->on('stocks')->onDelete('cascade');
            
            $table->index('stock_id');
            $table->index('type');
            $table->index('reference_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};

