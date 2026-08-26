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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedTinyInteger('priority')->default(2);
            $table->timestamps();

            $table->index(['job_application_id', 'completed_at', 'due_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
