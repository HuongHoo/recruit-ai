<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves OAuth identities out of users.google_id into their own table so more providers can be added.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32);
            $table->string('provider_user_id');
            $table->timestamps();

            $table->unique(['provider', 'provider_user_id']);
            $table->unique(['user_id', 'provider']);
        });

        DB::table('social_accounts')->insertUsing(
            ['user_id', 'provider', 'provider_user_id', 'created_at', 'updated_at'],
            DB::table('users')->whereNotNull('google_id')
                ->select('id', DB::raw("'google'"), 'google_id', 'created_at', 'updated_at'),
        );

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('google_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email');
        });

        DB::table('social_accounts')->where('provider', 'google')->orderBy('id')->each(function (object $account): void {
            DB::table('users')->where('id', $account->user_id)->update(['google_id' => $account->provider_user_id]);
        });

        Schema::dropIfExists('social_accounts');
    }
};
