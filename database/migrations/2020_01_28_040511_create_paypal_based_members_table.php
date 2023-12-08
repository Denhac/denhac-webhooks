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
        Schema::create('paypal_based_members', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();

            $table->string('paypal_id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->boolean('active')->default(true);
            $table->string('card')->nullable();
            $table->string('slack_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paypal_based_members');
    }
};
