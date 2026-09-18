<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paper_id')->constrained('papers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('total', 5, 2)->default(0);
            $table->text('comments')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            // One evaluation per evaluator per paper. Re-submits update the same row.
            $table->unique(['paper_id', 'user_id']);
        });

        Schema::create('evaluation_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('evaluations')->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('criteria')->cascadeOnDelete();
            $table->decimal('score', 5, 2);
            $table->timestamps();

            $table->unique(['evaluation_id', 'criterion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_scores');
        Schema::dropIfExists('evaluations');
    }
};
