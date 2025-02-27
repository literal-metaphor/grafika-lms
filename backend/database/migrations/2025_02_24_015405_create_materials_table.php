<?php

use App\Models\Material;
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
        Schema::create('materials', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->string('description');
            $table->string('file_path');
            $table->boolean('must_be_reviewed');
            $table->foreignIdFor(Material::class, 'preceeding_material_id')->nullable();
            $table->foreignUlid('classroom_id')->constrained();
            $table->foreignUlid('teacher_id')->constrained();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
