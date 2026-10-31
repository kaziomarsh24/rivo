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
        Schema::create('pet_vaccinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('vaccine_type')->default('core'); // core, non_core, lifestyle
            $table->date('administered_at');
            $table->date('due_at');
            $table->string('clinic_name')->nullable();
            $table->string('veterinarian_name')->nullable();
            $table->string('batch_number')->nullable();
            $table->string('certificate_file')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['pet_id', 'due_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pet_vaccinations');
    }
};

