<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('company_name');
            $table->string('category', 100);
            $table->string('location', 100);
            $table->string('job_type', 20);
            $table->string('experience', 50)->nullable();
            $table->unsignedInteger('salary_min')->nullable();
            $table->unsignedInteger('salary_max')->nullable();
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->text('benefits')->nullable();
            $table->date('deadline')->nullable();
            $table->string('status', 10)->default('open');
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['category', 'location']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_posts');
    }
};
