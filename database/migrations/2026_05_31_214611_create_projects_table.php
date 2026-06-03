<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->text('original_idea')->nullable();
            $table->enum('status', [
                'planning', 'active', 'on_hold', 'completed', 'archived'
            ])->default('planning');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('ai_metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};