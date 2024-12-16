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
        Schema::table('join_offers', function (Blueprint $table) {
            //
            $table->renameColumn('sub-heading', 'sub_heading');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('join_offers', function (Blueprint $table) {
            //
            $table->renameColumn( 'sub_heading','sub-heading');
        });
    }
};
