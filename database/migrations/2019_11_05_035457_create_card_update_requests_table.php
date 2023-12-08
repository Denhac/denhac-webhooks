<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('card_update_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();

            $table->string('type');
            $table->string('customer_id');
            $table->string('card');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('card_update_requests');
    }
};
