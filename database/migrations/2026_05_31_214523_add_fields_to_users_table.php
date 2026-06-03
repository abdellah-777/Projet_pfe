<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->after('id');
            $table->foreignId('organization_id')
                  ->nullable()
                  ->after('uuid')
                  ->constrained()
                  ->nullOnDelete();
            $table->string('avatar_url')->nullable()->after('email');
            $table->string('job_title')->nullable()->after('avatar_url');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'organization_id', 'avatar_url', 'job_title']);
            $table->dropSoftDeletes();
        });
    }
};