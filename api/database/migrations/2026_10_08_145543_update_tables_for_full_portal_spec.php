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
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'student_number')) {
                $table->string('student_number')->nullable()->unique();
            }
            if (!Schema::hasColumn('students', 'course_program')) {
                $table->string('course_program')->nullable()->default('BS Information Technology');
            }
            if (!Schema::hasColumn('students', 'year_level')) {
                $table->integer('year_level')->nullable()->default(1);
            }
        });

        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'code')) {
                $table->string('code')->nullable();
            }
            if (!Schema::hasColumn('courses', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('courses', 'units')) {
                $table->integer('units')->default(3);
            }
            if (!Schema::hasColumn('courses', 'department')) {
                $table->string('department')->default('College of Computer Studies');
            }
            if (!Schema::hasColumn('courses', 'semester')) {
                $table->string('semester')->default('1st Semester');
            }
            if (!Schema::hasColumn('courses', 'year_level')) {
                $table->integer('year_level')->default(1);
            }
            if (!Schema::hasColumn('courses', 'schedule')) {
                $table->string('schedule')->nullable();
            }
            if (!Schema::hasColumn('courses', 'instructor')) {
                $table->string('instructor')->nullable();
            }
        });

        Schema::table('enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('enrollments', 'academic_year')) {
                $table->string('academic_year')->default('2026-2027');
            }
            if (!Schema::hasColumn('enrollments', 'semester')) {
                $table->string('semester')->default('1st Semester');
            }
            if (!Schema::hasColumn('enrollments', 'midterm_grade')) {
                $table->decimal('midterm_grade', 4, 2)->nullable();
            }
            if (!Schema::hasColumn('enrollments', 'final_grade')) {
                $table->decimal('final_grade', 4, 2)->nullable();
            }
            if (!Schema::hasColumn('enrollments', 'status')) {
                $table->string('status')->default('enrolled');
            }
            if (!Schema::hasColumn('enrollments', 'remarks')) {
                $table->string('remarks')->nullable();
            }
        });

        Schema::table('clearances', function (Blueprint $table) {
            if (!Schema::hasColumn('clearances', 'academic_year')) {
                $table->string('academic_year')->default('2026-2027');
            }
            if (!Schema::hasColumn('clearances', 'semester')) {
                $table->string('semester')->default('1st Semester');
            }
            if (!Schema::hasColumn('clearances', 'cashier_remarks')) {
                $table->string('cashier_remarks')->nullable();
            }
            if (!Schema::hasColumn('clearances', 'library_status')) {
                $table->boolean('library_status')->default(false);
            }
            if (!Schema::hasColumn('clearances', 'library_remarks')) {
                $table->string('library_remarks')->nullable();
            }
            if (!Schema::hasColumn('clearances', 'dept_remarks')) {
                $table->string('dept_remarks')->nullable();
            }
            if (!Schema::hasColumn('clearances', 'dean_status')) {
                $table->boolean('dean_status')->default(false);
            }
            if (!Schema::hasColumn('clearances', 'dean_remarks')) {
                $table->string('dean_remarks')->nullable();
            }
            if (!Schema::hasColumn('clearances', 'guidance_status')) {
                $table->boolean('guidance_status')->default(false);
            }
            if (!Schema::hasColumn('clearances', 'guidance_remarks')) {
                $table->string('guidance_remarks')->nullable();
            }
            if (!Schema::hasColumn('clearances', 'registrar_status')) {
                $table->boolean('registrar_status')->default(false);
            }
            if (!Schema::hasColumn('clearances', 'registrar_remarks')) {
                $table->string('registrar_remarks')->nullable();
            }
            if (!Schema::hasColumn('clearances', 'status')) {
                $table->string('status')->default('pending');
            }
        });

        Schema::table('document_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('document_requests', 'document_type')) {
                $table->string('document_type')->nullable();
            }
            if (!Schema::hasColumn('document_requests', 'purpose')) {
                $table->string('purpose')->nullable();
            }
            if (!Schema::hasColumn('document_requests', 'copies')) {
                $table->integer('copies')->default(1);
            }
            if (!Schema::hasColumn('document_requests', 'fee')) {
                $table->decimal('fee', 8, 2)->default(150.00);
            }
            if (!Schema::hasColumn('document_requests', 'payment_status')) {
                $table->string('payment_status')->default('unpaid');
            }
            if (!Schema::hasColumn('document_requests', 'remarks')) {
                $table->text('remarks')->nullable();
            }
            if (!Schema::hasColumn('document_requests', 'released_at')) {
                $table->timestamp('released_at')->nullable();
            }
        });

        Schema::table('student_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('student_applications', 'student_id')) {
                $table->foreignId('student_id')->nullable()->constrained();
            }
            if (!Schema::hasColumn('student_applications', 'student_type')) {
                $table->string('student_type')->nullable()->default('new');
            }
            if (!Schema::hasColumn('student_applications', 'target_program')) {
                $table->string('target_program')->nullable()->default('BS Information Technology');
            }
            if (!Schema::hasColumn('student_applications', 'year_level')) {
                $table->integer('year_level')->default(1);
            }
            if (!Schema::hasColumn('student_applications', 'semester')) {
                $table->string('semester')->default('1st Semester');
            }
            if (!Schema::hasColumn('student_applications', 'academic_year')) {
                $table->string('academic_year')->default('2026-2027');
            }
            if (!Schema::hasColumn('student_applications', 'previous_school')) {
                $table->string('previous_school')->nullable();
            }
            if (!Schema::hasColumn('student_applications', 'contact_number')) {
                $table->string('contact_number')->nullable();
            }
            if (!Schema::hasColumn('student_applications', 'address')) {
                $table->text('address')->nullable();
            }
            if (!Schema::hasColumn('student_applications', 'emergency_contact')) {
                $table->string('emergency_contact')->nullable();
            }
            if (!Schema::hasColumn('student_applications', 'submitted_documents')) {
                $table->json('submitted_documents')->nullable();
            }
            if (!Schema::hasColumn('student_applications', 'evaluation_notes')) {
                $table->text('evaluation_notes')->nullable();
            }
            if (!Schema::hasColumn('student_applications', 'reviewed_by')) {
                $table->string('reviewed_by')->nullable();
            }
            if (!Schema::hasColumn('student_applications', 'assigned_section')) {
                $table->string('assigned_section')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversible if needed
    }
};

