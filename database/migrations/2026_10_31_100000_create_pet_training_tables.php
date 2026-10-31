<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Training Categories (Basic Commands, Walking, Tricks, Behavior)
        Schema::create('training_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subtitle')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('order_column')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Training Lessons
        Schema::create('training_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('training_categories')->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('tag')->default('TRAINING Short'); // e.g. TRAINING Short, BEHAVIOR, TRICK, WALK
            $table->string('species')->default('dog'); // dog, cat, all
            $table->string('difficulty')->default('beginner'); // beginner, intermediate, advanced, master
            $table->unsignedInteger('duration_minutes')->default(10);
            $table->string('impact')->default('Low impact'); // Low impact, Medium impact, High impact
            $table->unsignedInteger('reward_tp')->default(2); // Training Points (TP)
            $table->unsignedInteger('reward_xp')->default(20); // Pet XP
            $table->string('summary')->nullable(); // Short confidence-builder note
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('video_url')->nullable();
            $table->boolean('is_plus_only')->default(false); // Rivo+ or Pro exclusive
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('order_column')->default(0);
            $table->timestamps();

            $table->index(['species', 'difficulty']);
            $table->index(['is_active', 'order_column']);
        });

        // 3. Step-by-Step Lesson Instructions / Visual cues
        Schema::create('training_lesson_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('training_lessons')->onDelete('cascade');
            $table->unsignedInteger('step_number');
            $table->string('title');
            $table->text('instruction');
            $table->text('tip')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();

            $table->index(['lesson_id', 'step_number']);
        });

        // 4. Pet Training Completion Progress
        Schema::create('pet_training_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->constrained('pets')->onDelete('cascade');
            $table->foreignId('lesson_id')->constrained('training_lessons')->onDelete('cascade');
            $table->string('status')->default('completed'); // in_progress, completed
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('tp_earned')->default(0);
            $table->unsignedInteger('xp_earned')->default(0);
            $table->timestamps();

            $table->unique(['pet_id', 'lesson_id']);
        });

        // 5. Pet Training Bookmarks
        Schema::create('pet_training_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('pet_id')->constrained('pets')->onDelete('cascade');
            $table->foreignId('lesson_id')->constrained('training_lessons')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['pet_id', 'lesson_id']);
        });

        // 6. Pet Training Gamification Stats (Total TP, Rank, Streak)
        Schema::create('pet_training_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->unique()->constrained('pets')->onDelete('cascade');
            $table->unsignedInteger('total_tp')->default(0);
            $table->string('current_rank')->default('Bronze I');
            $table->unsignedInteger('current_streak_days')->default(0);
            $table->unsignedInteger('longest_streak_days')->default(0);
            $table->date('last_training_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pet_training_stats');
        Schema::dropIfExists('pet_training_bookmarks');
        Schema::dropIfExists('pet_training_progress');
        Schema::dropIfExists('training_lesson_steps');
        Schema::dropIfExists('training_lessons');
        Schema::dropIfExists('training_categories');
    }
};

