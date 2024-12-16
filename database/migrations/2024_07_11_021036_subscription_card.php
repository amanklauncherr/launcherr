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
        //

        Schema::create('subscription_cards', function (Blueprint $table) {
            $table->id();
            $table->string('card_no');
            $table->string('title');
            $table->string('price');
            $table->string('price_2')->nullable();
            $table->json('features');
            $table->string('buttonLabel');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
