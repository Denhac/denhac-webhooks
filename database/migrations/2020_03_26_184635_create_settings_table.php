<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Config;

return new class extends Migration
{
    public function __construct()
    {
        if (version_compare(Application::VERSION, '5.0', '>=')) {
            $this->tablename = config('settings.table');
            $this->keyColumn = config('settings.keyColumn');
            $this->valueColumn = config('settings.valueColumn');
        } else {
            $this->tablename = config('anlutro/l4-settings::table');
            $this->keyColumn = config('anlutro/l4-settings::keyColumn');
            $this->valueColumn = config('anlutro/l4-settings::valueColumn');
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->tablename, function (Blueprint $table) {
            $table->increments('id');
            $table->string($this->keyColumn)->index();
            $table->text($this->valueColumn);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop($this->tablename);
    }
};
