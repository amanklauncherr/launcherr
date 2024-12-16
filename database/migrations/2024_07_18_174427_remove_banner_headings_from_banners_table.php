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
            //
            $table->dropColumn(['Banner_heading', 'Banner_sub_heading']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banner_news', function (Blueprint $table) {
            //
            $table->string('Banner_heading', 255)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable(false);
            $table->string('Banner_sub_heading', 255)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable()->default(null);
        });
    }
};
