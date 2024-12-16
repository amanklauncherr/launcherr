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
        Schema::create('travel_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('BookingType') ;
            $table->string('BookingRef')->unique()->nullable();
            $table->json('PnrDetails')->nullable();
            $table->json('PAXTicketDetails')->nullable();
            $table->json('TravelDetails')->nullable();
            $table->string('Status')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('BookingRef');
            $table->index('Status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_histories');
    }
};
