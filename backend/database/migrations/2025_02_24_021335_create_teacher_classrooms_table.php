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
        Schema::create('teacher_classrooms', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('teacher_id')->constrained();
            $table->foreignUlid('classroom_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_classrooms');
    }
};
