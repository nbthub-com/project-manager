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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('for_id')->nullable();
            $table->enum('context', ['project', 'task'])->nullable();
            $table->text('content');
            $table->foreignId('reply_to')->nullable()->constrained('comments')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
