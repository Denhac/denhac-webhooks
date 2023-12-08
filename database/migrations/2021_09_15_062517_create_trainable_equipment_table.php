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
        Schema::create('trainable_equipment', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->integer('user_plan_id');
            $table->string('user_slack_id')->nullable();
            $table->string('user_email')->nullable();
            $table->integer('trainer_plan_id');
            $table->string('trainer_slack_id')->nullable();
            $table->string('trainer_email')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainable_equipment');
    }
};
