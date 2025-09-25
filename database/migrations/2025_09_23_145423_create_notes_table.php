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
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->text("content"); // Content of note
            $table->string("context"); // Can be for "proj" or "task"
            $table->string("context_id"); // Task or project id
            $table->unsignedBigInteger("member_id"); // member id
            $table->enum("type", ["note", "question"])->default("note");
            $table->timestamps();

            $table->foreignId("member_id")
                  ->constrained("users")
                  ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
