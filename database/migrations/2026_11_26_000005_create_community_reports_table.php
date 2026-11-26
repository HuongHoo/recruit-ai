<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Members flag a post or comment as inappropriate; admins resolve the reports.
     */
    public function up(): void
    {
        Schema::create('community_reports', function (Blueprint $table) {
            $table->id();
            $table->morphs('reportable');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 500);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['reportable_type', 'reportable_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_reports');
    }
};
