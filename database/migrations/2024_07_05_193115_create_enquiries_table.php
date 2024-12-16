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
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userID');
            $table->unsignedBigInteger('gigID');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('userID')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('gigID')->references('id')->on('job_posting')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['userID']);
            $table->dropForeign(['gigID']);
        });

        Schema::dropIfExists('enquiries');
    }
};
