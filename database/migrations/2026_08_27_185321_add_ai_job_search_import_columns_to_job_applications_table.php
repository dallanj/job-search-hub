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
        Schema::table('job_applications', function (Blueprint $table): void {
            $table->date('deadline')->nullable()->after('closed_at');
            $table->string('cv_file_path')->nullable()->after('description');
            $table->string('cover_letter_file_path')->nullable()->after('cv_file_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table): void {
            $table->dropColumn(['deadline', 'cv_file_path', 'cover_letter_file_path']);
        });
    }
};
