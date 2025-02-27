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
        Schema::create('session_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->timestamp('login_at')->nullable(); // For some reason it's automatically on update CURRENT_TIMESTAMP without this, so just leave it be lol
            $table->timestamp('logout_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_records');
    }
};
