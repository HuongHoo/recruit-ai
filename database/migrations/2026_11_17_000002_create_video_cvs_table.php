<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One Video CV per candidate: profile card + attached CV + a private share link.
        Schema::create('video_cvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name', 100)->nullable();
            $table->string('position', 150)->nullable();
            $table->text('intro')->nullable();
            $table->string('public_email')->nullable();
            $table->string('photo_path')->nullable();
            $table->foreignId('cv_id')->nullable()->constrained()->nullOnDelete();
            $table->string('share_token', 40)->nullable()->unique();
            $table->timestamp('shared_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();
        });

        // One recorded answer (video clip) per interview question.
        Schema::create('video_cv_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_cv_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->string('question', 300);
            $table->string('file_path')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->unsignedSmallInteger('duration_seconds')->nullable();
            $table->string('source', 10)->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();

            $table->unique(['video_cv_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_cv_answers');
        Schema::dropIfExists('video_cvs');
    }
};
