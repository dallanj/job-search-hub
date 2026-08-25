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
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('role_title');
            $table->string('status')->default('saved');
            $table->unsignedBigInteger('sort_order')->default(0);
            $table->string('employment_type')->nullable();
            $table->string('workplace_type')->nullable();
            $table->string('location')->nullable();
            $table->string('source')->nullable();
            $table->string('job_url')->nullable();
            $table->unsignedInteger('salary_min')->nullable();
            $table->unsignedInteger('salary_max')->nullable();
            $table->char('salary_currency', 3)->nullable();
            $table->date('applied_at')->nullable();
            $table->date('closed_at')->nullable();
            $table->longText('description')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'sort_order']);
            $table->index(['user_id', 'applied_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
