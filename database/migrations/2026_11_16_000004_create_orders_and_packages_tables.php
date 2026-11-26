<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->unsignedBigInteger('total');
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('purchased_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('tier', 20);
            $table->unsignedSmallInteger('days');
            $table->unsignedBigInteger('price');
            $table->foreignId('job_post_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::table('job_posts', function (Blueprint $table) {
            $table->string('promotion_tier', 20)->nullable();
            $table->unsignedTinyInteger('promotion_priority')->default(0);
            $table->timestamp('promoted_until')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('job_posts', function (Blueprint $table) {
            $table->dropColumn(['promotion_tier', 'promotion_priority', 'promoted_until']);
        });
        Schema::dropIfExists('purchased_packages');
        Schema::dropIfExists('orders');
    }
};
