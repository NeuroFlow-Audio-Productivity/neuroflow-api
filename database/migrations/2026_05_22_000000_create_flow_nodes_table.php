<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('flow_nodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('time');
            $table->unsignedInteger('order');
            $table->foreignId('flow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mode_id')->constrained()->cascadeOnDelete();
            $table->foreignId('end_audio_id')->constrained('audios')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flow_nodes');
    }
};
