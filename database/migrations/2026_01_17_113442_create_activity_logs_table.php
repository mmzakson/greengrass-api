<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('causer'); // Who performed the action (User or Admin)
            $table->uuidMorphs('subject'); // What was affected
            $table->string('event'); // created, updated, deleted, etc.
            $table->string('description')->nullable();
            $table->text('properties')->nullable(); // JSON: old/new values
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('event');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
