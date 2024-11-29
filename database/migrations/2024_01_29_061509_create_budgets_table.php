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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('quickbooks_class_id');

            $table->string('name');
            $table->string('type');
            $table->boolean('active');

            $table->float('allocated_amount')->default(0);
            $table->float('currently_used')->default(0);

            $table->string('owner_type');
            $table->string('owner_id');

            $table->longText('notes')->default('');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
