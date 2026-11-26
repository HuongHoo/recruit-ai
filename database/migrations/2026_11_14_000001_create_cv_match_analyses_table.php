<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cv_match_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cv_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->string('headline');
            $table->text('summary');
            $table->json('criteria');
            $table->json('requirements');
            $table->json('strengths')->nullable();
            $table->json('suggestions')->nullable();
            // Hash of the CV + JD content analysed; a mismatch means the result is outdated.
            $table->string('source_hash', 64);
            $table->string('model', 60);
            $table->timestamps();

            $table->unique(['cv_id', 'job_post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_match_analyses');
    }
};
