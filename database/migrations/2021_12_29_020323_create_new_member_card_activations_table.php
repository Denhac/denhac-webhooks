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
        Schema::create('new_member_card_activations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->integer('woo_customer_id');
            $table->integer('card_number');
            $table->string('state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('new_member_card_activations');
    }
};
