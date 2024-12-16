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
        Schema::create('subscription_details', function (Blueprint $table) {
            $table->id();
            $table->string('sub_type');
            $table->string('sub_price');
            $table->string('sub_detail');
            $table->integer('sub_days');
            $table->boolean('find_gigs')->default(0);
            $table->boolean('book_travel')->default(0);
            $table->boolean('book_adventure')->default(0);
            $table->boolean('booking_fee')->default(0);
            $table->boolean('coupon_voucher')->default(0);
            $table->boolean('p_itinerary')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_details');
    }
};
