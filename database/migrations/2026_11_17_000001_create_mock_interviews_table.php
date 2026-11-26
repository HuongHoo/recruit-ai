<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mock_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_post_id')->constrained()->cascadeOnDelete();
            // Kept when the CV is later deleted, so past interviews stay readable.
            $table->foreignId('cv_id')->nullable()->constrained()->nullOnDelete();
            $table->string('cv_title');
            $table->string('language', 5);
            $table->json('questions');
            $table->json('answers');
            $table->string('status', 20)->default('in_progress');
            $table->unsignedTinyInteger('score')->nullable();
            $table->json('feedback')->nullable();
            $table->string('model', 60);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mock_interviews');
    }
};
