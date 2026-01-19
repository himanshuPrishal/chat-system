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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['image', 'video', 'audio', 'file']);
            $table->string('path');
            $table->string('filename');
            $table->integer('size'); // in bytes
            $table->string('mime_type');
            $table->string('thumbnail_path')->nullable(); // For videos
            $table->integer('duration')->nullable(); // For audio/video in seconds
            $table->timestamps();
            
            $table->index(['message_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
