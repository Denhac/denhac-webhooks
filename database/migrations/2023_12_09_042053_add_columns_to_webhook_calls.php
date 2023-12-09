<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('webhook_calls', function (Blueprint $table) {
            $table->string('url')->nullable();
            $table->json('headers')->nullable();
            $table->json('payload')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('webhook_calls', function (Blueprint $table) {
            $table->dropColumn('url');
            $table->dropColumn('headers');
            $table->text('payload')->change();
        });
    }
};
