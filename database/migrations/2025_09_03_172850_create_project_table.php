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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // project title
            $table->unsignedBigInteger('manager_id'); // manager (user) assigned
            $table->text('description')->nullable(); // optional project description
            $table->boolean('is_starred')->default(false); // for quick access/starred projects
            $table->timestamps();
            $table->string('status'); // project status

            // Foreign key
            $table->foreign('manager_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
