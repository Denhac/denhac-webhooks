<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTempBansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('temp_bans', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->timestamp('expires_at')->nullable();
            $table->string('user_id');
            $table->string('channel_id');
            $table->text('reason')->nullable();
            $table->string('banned_by_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('temp_bans');
    }
}
