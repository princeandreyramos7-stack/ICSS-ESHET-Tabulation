<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('papers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('track_id')->constrained('tracks')->cascadeOnDelete();
            $table->string('paper_no', 50)->unique();
            $table->string('title');
            $table->string('researcher');
            $table->string('affiliation')->nullable();
            $table->unsignedSmallInteger('presentation_order')->nullable();
            $table->timestamps();

            $table->index(['track_id', 'presentation_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('papers');
    }
};
