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
        Schema::table('banner_news', function (Blueprint $table) {
            $table->string('Banner_sub_heading')->nullable()->after('Banner_heading'); // Add after an existing column
            $table->string('Banner_button_text')->nullable()->after('Banner_sub_heading');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banner_news', function (Blueprint $table) {
            //
        });
    }
};
