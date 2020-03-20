<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardUpdateRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
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
    public function down()
    {
        Schema::dropIfExists('card_update_requests');
    }
}
