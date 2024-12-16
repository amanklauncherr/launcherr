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
        Schema::create('dot_mit_source_cities', function (Blueprint $table) {
            $table->id();      
            $table->string("City_ID");
            $table->string("City_Name");
            $table->string("State_ID");
            $table->string("State_Name");
            $table->string("LocationType");
            $table->string("Latitude");            
            $table->string("Longitude");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dot_mit_source_cities');
    }
};
