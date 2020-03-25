<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAggregateVersionToStoredEventsTable extends Migration
{
    public function up()
    {
        Schema::table('stored_events', function (Blueprint $table) {
            $table->unsignedBigInteger('aggregate_version')->nullable();
        });
    }
}
