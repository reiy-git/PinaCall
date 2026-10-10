<?php

namespace Tests\Feature;

use App\Models\AcademicSection;
use App\Models\ApiAccessToken;
use App\Models\Clearance;
use App\Models\Course;
use App\Models\DocumentRequest;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\StudentApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PortalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seed_contains_test_accounts_for_every_portal_role(): void
    {
        $this->seed();

        $faculty = User::where('email', 'faculty@university.edu')->firstOrFail();
        $this->assertSame('faculty', $faculty->role);
        $this->assertTrue(Hash::check('Pinacall-Demo-Only-2026!', $faculty->password));
        $this->assertDatabaseHas('users', ['email' => 'registrar@university.edu', 'role' => 'registrar']);
        $this->assertDatabaseHas('users', ['email' => 'cashier@university.edu', 'role' => 'cashier']);
        $this->assertDatabaseHas('users', ['email' => 'depthead@university.edu', 'role' => 'department_head']);
        $this->assertDatabaseHas('users', ['email' => 'admin@university.edu', 'role' => 'admin']);
        $this->assertGreaterThan(0, AcademicSection::where('faculty_id', $faculty->id)->count());
        $this->assertGreaterThan(0, Enrollment::whereNotNull('academic_section_id')->count());
    }

    public function test_full_university_portal_lifecycle_is_role_scoped(): void
    {
        $studentUser = $this->createUser('student', 'maria@student.university.edu');
        $student = Student::create([
            'user_id' => $studentUser->id,
            'name' => $studentUser->name,
            'email' => $studentUser->email,
            'student_number' => '2026-00999',
            'course_program' => 'BS Information Technology',
            'year_level' => 2,
            'student_type' => 'transferee',
            'status' => 'pending',
        ]);
        $registrar = $this->createUser('registrar', 'registrar@university.edu');
        $cashier = $this->createUser('cashier', 'cashier@university.edu');
        $departmentHead = $this->createUser('department_head', 'dean@university.edu', 'CCS');
        $faculty = $this->createUser('faculty', 'faculty@university.edu', 'CCS');
        $admin = $this->createUser('admin', 'admin@university.edu');
        $course = Course::create([
            'code' => 'IT201',
            'title' => 'Data Structures',
            'units' => 3,
            'department' => 'CCS',
            'semester' => '1st Semester',
            'year_level' => 2,
        ]);
        $section = AcademicSection::create([
            'course_id' => $course->id,
            'faculty_id' => $faculty->id,
            'section_code' => 'IT-2B',
            'program' => 'BS Information Technology',
            'year_level' => 2,
            'academic_year' => '2026-2027',
            'semester' => '1st Semester',
            'capacity' => 30,
            'status' => 'open',
        ]);

        $this->actingAsPortalUser($studentUser);
        $this->getJson('/api/auth/personas')->assertForbidden();
        $this->postJson('/api/applications', [
            'student_type' => 'transferee',
            'target_program' => 'BS Information Technology',
            'year_level' => 2,
            'educational_background' => 'Thirty units completed at previous university.',
            'previous_school' => 'San Juan State College',
        ])->assertCreated()->assertJsonPath('application.status', 'pending');
        $application = StudentApplication::firstOrFail();

        $this->actingAsPortalUser($registrar);
        $this->getJson('/api/students')->assertOk()->assertJsonPath('students.0.student_number', '2026-00999');
        $this->patchJson("/api/applications/{$application->id}/review", [
            'status' => 'approved',
            'assigned_section_id' => $section->id,
            'evaluation_notes' => 'Transfer credentials reviewed.',
        ])->assertOk()->assertJsonPath('application.assigned_section', 'IT-2B');
        $this->assertDatabaseMissing('section_enrollments', [
            'academic_section_id' => $section->id,
            'student_id' => $student->id,
        ]);

        $clearance = Clearance::where('student_id', $student->id)->firstOrFail();
        $this->actingAsPortalUser($cashier);
        $this->patchJson("/api/clearances/{$clearance->id}/step", [
            'department' => 'cashier',
            'approved' => true,
            'remarks' => 'Assessment paid.',
        ])->assertOk()->assertJsonPath('clearance.cashier_status', true);
        $this->patchJson("/api/clearances/{$clearance->id}/step", [
            'department' => 'dept',
            'approved' => true,
        ])->assertForbidden();

        $this->actingAsPortalUser($departmentHead);
        $this->patchJson("/api/clearances/{$clearance->id}/step", [
            'department' => 'dept',
            'approved' => true,
            'remarks' => 'Curriculum reviewed.',
        ])->assertOk()->assertJsonPath('clearance.dept_status', true);
        $this->patchJson("/api/clearances/{$clearance->id}/step", [
            'department' => 'dean',
            'approved' => true,
        ])->assertOk();
        $this->getJson('/api/sections')->assertOk();
        $this->getJson("/api/sections/{$section->id}/roster")->assertOk();

        $this->actingAsPortalUser($registrar);
        $this->patchJson("/api/clearances/{$clearance->id}/step", [
            'department' => 'registrar',
            'approved' => true,
        ])->assertOk();
        $this->actingAsPortalUser($admin);
        $this->patchJson("/api/clearances/{$clearance->id}/step", [
            'department' => 'library',
            'approved' => true,
        ])->assertOk();
        $this->patchJson("/api/clearances/{$clearance->id}/step", [
            'department' => 'guidance',
            'approved' => true,
        ])->assertOk()->assertJsonPath('clearance.status', 'cleared');

        $this->actingAsPortalUser($studentUser);
        $this->postJson("/api/sections/{$section->id}/enroll", [])->assertOk();
        $this->postJson("/api/sections/{$section->id}/enroll", [])->assertOk();
        $this->assertDatabaseHas('section_enrollments', [
            'academic_section_id' => $section->id,
            'student_id' => $student->id,
            'status' => 'enrolled',
        ]);
        $this->assertSame(1, \App\Models\SectionEnrollment::where('academic_section_id', $section->id)
            ->where('student_id', $student->id)
            ->count());
        $enrollment = Enrollment::where('academic_section_id', $section->id)->firstOrFail();
        $this->actingAsPortalUser($faculty);
        $this->patchJson("/api/grades/{$enrollment->id}", [
            'midterm_grade' => '1e2',
            'final_grade' => 1.25,
        ])->assertUnprocessable();
        $this->patchJson("/api/grades/{$enrollment->id}", [
            'midterm_grade' => 1.50,
            'final_grade' => 1.25,
        ])->assertOk()->assertJsonPath('enrollment.grade', '1.38');

        $this->actingAsPortalUser($studentUser);
        $this->getJson('/api/grades')->assertOk()->assertJsonCount(0, 'grades');
        $this->actingAsPortalUser($faculty);
        $this->postJson("/api/grades/{$enrollment->id}/release")
            ->assertOk()
            ->assertJsonPath('enrollment.grades_released', true);
        $this->actingAsPortalUser($studentUser);
        $this->getJson('/api/grades')->assertOk()->assertJsonCount(1, 'grades');
        $documentResponse = $this->postJson('/api/documents/request', [
            'document_type' => 'Transcript of Records (TOR)',
            'purpose' => 'Scholarship application',
            'copies' => 2,
        ])->assertCreated()->assertJsonPath('request.fee', 300);
        $documentId = $documentResponse->json('request.id');

        $this->actingAsPortalUser($cashier);
        $this->patchJson("/api/documents/{$documentId}/status", [
            'payment_status' => 'paid',
        ])->assertOk()->assertJsonPath('request.status', 'processing');
        $this->patchJson("/api/documents/{$documentId}/status", [
            'status' => 'ready_for_pickup',
        ])->assertForbidden();

        $this->actingAsPortalUser($registrar);
        $this->patchJson("/api/documents/{$documentId}/status", [
            'status' => 'ready_for_pickup',
        ])->assertOk();
        $this->patchJson("/api/documents/{$documentId}/status", [
            'status' => 'released',
        ])->assertOk()->assertJsonPath('request.status', 'released');

        $this->actingAsPortalUser($studentUser);
        $this->getJson('/api/dashboard/stats')->assertForbidden();
        $this->actingAsPortalUser($admin);
        $this->getJson('/api/dashboard/stats')->assertOk()->assertJsonPath('stats.total_students', 1);
    }

    public function test_student_enrollment_rejects_full_or_closed_sections(): void
    {
        $firstUser = $this->createUser('student', 'first@student.university.edu');
        $firstStudent = $this->createStudent($firstUser, '2026-00101');
        Clearance::create([
            'student_id' => $firstStudent->id,
            'cashier_status' => true,
            'library_status' => true,
            'dept_status' => true,
            'dean_status' => true,
            'guidance_status' => true,
            'registrar_status' => true,
            'status' => 'cleared',
        ]);
        $secondUser = $this->createUser('student', 'second@student.university.edu');
        $secondStudent = $this->createStudent($secondUser, '2026-00102');
        Clearance::create([
            'student_id' => $secondStudent->id,
            'cashier_status' => true,
            'library_status' => true,
            'dept_status' => true,
            'dean_status' => true,
            'guidance_status' => true,
            'registrar_status' => true,
            'status' => 'cleared',
        ]);
        $course = Course::create([
            'code' => 'IT101',
            'title' => 'Introduction to Computing',
            'units' => 3,
            'department' => 'CCS',
            'semester' => '1st Semester',
            'year_level' => 1,
        ]);
        $section = AcademicSection::create([
            'course_id' => $course->id,
            'section_code' => 'IT101-1A',
            'program' => 'BS Information Technology',
            'year_level' => 1,
            'academic_year' => '2026-2027',
            'semester' => '1st Semester',
            'capacity' => 1,
            'status' => 'open',
        ]);

        $this->actingAsPortalUser($firstUser);
        $this->postJson("/api/sections/{$section->id}/enroll", [])->assertOk();
        $this->actingAsPortalUser($secondUser);
        $this->postJson("/api/sections/{$section->id}/enroll", [])->assertUnprocessable();

        $section->update(['status' => 'closed']);
        $this->actingAsPortalUser($firstUser);
        $this->postJson("/api/sections/{$section->id}/enroll", [])->assertUnprocessable();
    }

    public function test_section_enrollment_reuses_existing_unassigned_course_record(): void
    {
        $studentUser = $this->createUser('student', 'legacy-enrollment@student.university.edu');
        $student = $this->createStudent($studentUser, '2026-00103');
        Clearance::create([
            'student_id' => $student->id,
            'cashier_status' => true,
            'library_status' => true,
            'dept_status' => true,
            'dean_status' => true,
            'guidance_status' => true,
            'registrar_status' => true,
            'status' => 'cleared',
        ]);
        $course = Course::create([
            'code' => 'IT101',
            'title' => 'Introduction to Computing',
            'units' => 3,
            'department' => 'CCS',
            'semester' => '1st Semester',
            'year_level' => 1,
        ]);
        $section = AcademicSection::create([
            'course_id' => $course->id,
            'section_code' => 'IT101-1A',
            'program' => 'BS Information Technology',
            'year_level' => 1,
            'academic_year' => '2026-2027',
            'semester' => '1st Semester',
            'capacity' => 30,
            'status' => 'open',
        ]);
        $existingEnrollment = Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'grade' => '1.50',
        ]);

        $this->actingAsPortalUser($studentUser);
        $this->postJson("/api/sections/{$section->id}/enroll", [])->assertOk();

        $this->assertDatabaseCount('enrollments', 1);
        $this->assertDatabaseHas('enrollments', [
            'id' => $existingEnrollment->id,
            'student_id' => $student->id,
            'course_id' => $course->id,
            'academic_section_id' => $section->id,
            'grade' => '1.50',
        ]);
    }

    public function test_student_grade_visibility_requires_active_enrollment_and_explicit_release(): void
    {
        $studentUser = $this->createUser('student', 'grades@student.university.edu');
        $student = $this->createStudent($studentUser, '2026-00105');
        $course = Course::create([
            'code' => 'IT105',
            'title' => 'Test Subject',
            'units' => 3,
            'department' => 'CCS',
            'semester' => '1st Semester',
            'year_level' => 1,
        ]);
        $faculty = $this->createUser('faculty', 'grades-faculty@university.edu', 'CCS');
        $section = AcademicSection::create([
            'course_id' => $course->id,
            'faculty_id' => $faculty->id,
            'section_code' => 'IT105-1A',
            'program' => 'BS Information Technology',
            'year_level' => 1,
            'academic_year' => '2026-2027',
            'semester' => '1st Semester',
            'capacity' => 30,
            'status' => 'open',
        ]);
        $section->students()->attach($student->id, ['status' => 'enrolled', 'enrolled_at' => now()]);
        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'academic_section_id' => $section->id,
            'academic_year' => '2026-2027',
            'semester' => '1st Semester',
            'midterm_grade' => 1.50,
            'final_grade' => 1.75,
            'grade' => '1.63',
            'status' => 'graded',
            'grades_released' => false,
        ]);

        $this->actingAsPortalUser($studentUser);
        $this->getJson('/api/grades')->assertOk()->assertJsonCount(0, 'grades');

        $enrollment->update(['grades_released' => true]);
        $this->getJson('/api/grades')->assertOk()->assertJsonCount(1, 'grades');

        $student->update(['status' => 'pending']);
        $this->getJson('/api/grades')->assertOk()->assertJsonCount(0, 'grades');
    }

    public function test_only_administrators_can_create_and_maintain_accounts_and_subjects(): void
    {
        $admin = $this->createUser('admin', 'catalog-admin@university.edu');
        $faculty = $this->createUser('faculty', 'catalog-faculty@university.edu', 'CCS');
        $this->actingAsPortalUser($faculty);
        $this->postJson('/api/admin/courses', [
            'code' => 'CS101',
            'title' => 'Introduction to Computer Science',
            'units' => 3,
            'department' => 'CCS',
            'semester' => '1st Semester',
            'year_level' => 1,
        ])->assertForbidden();

        $this->actingAsPortalUser($admin);
        $this->postJson('/api/admin/courses', [
            'code' => 'cs101',
            'title' => 'Introduction to Computer Science',
            'units' => 3,
            'department' => 'CCS',
            'semester' => '1st Semester',
            'year_level' => 1,
        ])->assertCreated()->assertJsonPath('course.code', 'CS101');
        $this->postJson('/api/admin/courses', [
            'code' => 'CS101',
            'title' => 'Duplicate subject',
            'units' => 3,
            'department' => 'CCS',
            'semester' => '1st Semester',
            'year_level' => 1,
        ])->assertUnprocessable();

        $created = $this->postJson('/api/admin/users', [
            'name' => 'New Student',
            'email' => 'new.student@university.edu',
            'password' => 'Secure-Initial-Password-2026',
            'role' => 'student',
            'course_program' => 'BS Information Technology',
            'year_level' => 1,
            'student_type' => 'new',
            'student_status' => 'pending',
        ])->assertCreated()->assertJsonPath('user.role', 'student');
        $userId = $created->json('user.id');

        $this->patchJson("/api/admin/users/{$userId}", [
            'is_active' => false,
        ])->assertOk()->assertJsonPath('user.is_active', false);
        $this->getJson('/api/auth/personas')->assertOk();
        $this->postJson('/api/auth/login', [
            'email' => 'new.student@university.edu',
            'password' => 'Secure-Initial-Password-2026',
        ])->assertForbidden();
    }

    private function createStudent(User $user, string $studentNumber): Student
    {
        return Student::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'student_number' => $studentNumber,
            'course_program' => 'BS Information Technology',
            'year_level' => 1,
            'student_type' => 'new',
            'status' => 'active',
        ]);
    }

    private function createUser(string $role, string $email, ?string $department = null): User
    {
        return User::create([
            'name' => ucfirst(str_replace('_', ' ', $role)),
            'email' => $email,
            'password' => Hash::make(Str::random(24)),
            'role' => $role,
            'department' => $department,
        ]);
    }

    private function actingAsPortalUser(User $user): void
    {
        $plainToken = Str::random(80);
        ApiAccessToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addHours(8),
        ]);
        $this->withHeader('Authorization', "Bearer {$plainToken}");
    }
}
