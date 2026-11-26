<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // The CV stays linked while it exists; the title is kept so history survives a deleted CV.
            $table->foreignId('cv_id')->nullable()->constrained()->nullOnDelete();
            $table->string('cv_title');
            $table->text('cover_letter')->nullable();
            $table->string('status', 15)->default('submitted');
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();

            $table->unique(['job_post_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
