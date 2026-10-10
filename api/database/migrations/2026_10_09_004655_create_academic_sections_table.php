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
        Schema::create('academic_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('faculty_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('section_code');
            $table->string('program');
            $table->unsignedTinyInteger('year_level');
            $table->string('academic_year');
            $table->string('semester');
            $table->string('schedule')->nullable();
            $table->string('room')->nullable();
            $table->unsignedSmallInteger('capacity')->default(40);
            $table->enum('status', ['draft', 'open', 'closed'])->default('draft');
            $table->timestamps();

            $table->unique(['course_id', 'section_code', 'academic_year', 'semester'], 'academic_section_term_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_sections');
    }
};
