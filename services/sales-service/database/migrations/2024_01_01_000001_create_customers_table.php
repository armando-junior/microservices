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
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 200);
            $table->string('email', 255)->unique();
            $table->string('phone', 20);
            $table->string('document', 20)->unique();
            
            // Address fields
            $table->string('address_street', 255)->nullable();
            $table->string('address_number', 20)->nullable();
            $table->string('address_complement', 100)->nullable();
            $table->string('address_city', 100)->nullable();
            $table->string('address_state', 2)->nullable();
            $table->string('address_zip_code', 10)->nullable();
            
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            
            // Indexes
            $table->index('email');
            $table->index('document');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
