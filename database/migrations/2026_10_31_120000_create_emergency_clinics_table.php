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
        Schema::create('emergency_clinics', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('phone_number');
            $table->string('emergency_hotline')->nullable();
            $table->string('email')->nullable();
            $table->string('street_address');
            $table->string('city');
            $table->string('state');
            $table->string('zip_code');
            $table->decimal('latitude', 10, 7)->index();
            $table->decimal('longitude', 10, 7)->index();
            $table->boolean('is_open_24_7')->default(true);
            $table->string('open_hours_text')->default('Open 24/7 • Emergency Ready');
            $table->decimal('rating', 2, 1)->default(4.8);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->json('services')->nullable();
            $table->string('photo')->nullable();
            $table->string('website')->nullable();
            $table->boolean('is_verified')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_clinics');
    }
};
