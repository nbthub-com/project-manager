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

            // Task fields
            $table->string('title');
            $table->string('role_title')->nullable(); // e.g. "Frontend Dev", "Intern"
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->text('description')->nullable();

            // Foreign keys
            $table->unsignedBigInteger('assigned_id'); // user assigned to the task
            $table->unsignedBigInteger('project_id');  // project this task belongs to

            // Relations
            $table->foreign('assigned_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
