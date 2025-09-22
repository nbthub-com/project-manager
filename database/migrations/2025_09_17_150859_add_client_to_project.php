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
        Schema::table('projects', function (Blueprint $table) {
            // Add client_id column
            $table->unsignedBigInteger('client_id')->nullable()->after('manager_id');

            // Foreign key -> users table
            $table->foreign('client_id')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Drop foreign key first, then column
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });
    }
};
