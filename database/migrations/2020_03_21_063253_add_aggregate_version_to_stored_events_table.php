<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAggregateVersionToStoredEventsTable extends Migration
{
    public function up()
    {
        Schema::table('stored_events', function (Blueprint $table) {
            $table->unsignedBigInteger('aggregate_version')->nullable();
        });
    }
}
