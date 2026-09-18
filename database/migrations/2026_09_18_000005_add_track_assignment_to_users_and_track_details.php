<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parallel-session workflow: every evaluator sits on the panel of exactly one
 * track, and each track has a venue, a session chair and a co-session chair
 * printed on its result sheet. Additive only; existing rows are untouched
 * (evaluators start unassigned until the admin picks their track).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('track_id')
                ->nullable()
                ->after('email_verified_at')
                ->constrained('tracks')
                ->nullOnDelete();
        });

        Schema::table('tracks', function (Blueprint $table) {
            $table->string('venue')->nullable()->after('name');
            $table->string('session_chair')->nullable()->after('venue');
            $table->string('co_session_chair')->nullable()->after('session_chair');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('track_id');
        });

        Schema::table('tracks', function (Blueprint $table) {
            $table->dropColumn(['venue', 'session_chair', 'co_session_chair']);
        });
    }
};
