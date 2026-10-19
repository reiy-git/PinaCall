<?php

namespace Database\Seeders;

use App\Models\AcademicSection;
use App\Models\Clearance;
use App\Models\Course;
use App\Models\DocumentRequest;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\StudentApplication;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'Pinacall-Demo-Only-2026!';

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PhysicalQueueTokenSeeder::class);

        // 1. Staff and Admin Personas
        $admin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@university.edu',
            'password' => Hash::make(self::DEMO_PASSWORD),
            'role' => 'admin',
        ]);

        $registrar = User::create([
            'name' => 'Maria Santos (University Registrar)',
            'email' => 'registrar@university.edu',
            'password' => Hash::make(self::DEMO_PASSWORD),
            'role' => 'registrar',
        ]);

        $cashier = User::create([
            'name' => 'Robert Tan (Chief Cashier)',
            'email' => 'cashier@university.edu',
            'password' => Hash::make(self::DEMO_PASSWORD),
            'role' => 'cashier',
        ]);

        $deptHead = User::create([
            'name' => 'Dr. Elena Cruz (CCS Department Head)',
            'email' => 'depthead@university.edu',
            'password' => Hash::make(self::DEMO_PASSWORD),
            'role' => 'department_head',
            'department' => 'CCS',
        ]);

        $faculty = User::create([
            'name' => 'Prof. Ada Lovelace (Faculty)',
            'email' => 'faculty@university.edu',
            'password' => Hash::make(self::DEMO_PASSWORD),
            'role' => 'faculty',
            'department' => 'CCS',
        ]);

        // 2. Sample Courses
        $courses = [
            ['code' => 'IT101', 'title' => 'Introduction to Computing', 'units' => 3, 'department' => 'CCS', 'semester' => '1st Semester', 'year_level' => 1, 'schedule' => 'Mon/Wed 8:00 - 9:30 AM', 'instructor' => 'Prof. Alan Turing'],
            ['code' => 'IT102', 'title' => 'Computer Programming 1', 'units' => 3, 'department' => 'CCS', 'semester' => '1st Semester', 'year_level' => 1, 'schedule' => 'Tue/Thu 10:00 - 11:30 AM', 'instructor' => 'Prof. Ada Lovelace'],
            ['code' => 'MATH101', 'title' => 'Discrete Mathematics', 'units' => 3, 'department' => 'GenEd', 'semester' => '1st Semester', 'year_level' => 1, 'schedule' => 'Fri 1:00 - 4:00 PM', 'instructor' => 'Dr. John von Neumann'],
            ['code' => 'ENG101', 'title' => 'Purposive Communication', 'units' => 3, 'department' => 'GenEd', 'semester' => '1st Semester', 'year_level' => 1, 'schedule' => 'Mon/Wed 1:00 - 2:30 PM', 'instructor' => 'Prof. Maya Angelou'],
            ['code' => 'IT201', 'title' => 'Data Structures & Algorithms', 'units' => 3, 'department' => 'CCS', 'semester' => '1st Semester', 'year_level' => 2, 'schedule' => 'Mon/Wed 10:00 - 11:30 AM', 'instructor' => 'Dr. Donald Knuth'],
            ['code' => 'IT202', 'title' => 'Database Management Systems', 'units' => 3, 'department' => 'CCS', 'semester' => '1st Semester', 'year_level' => 2, 'schedule' => 'Tue/Thu 1:00 - 2:30 PM', 'instructor' => 'Prof. Edgar Codd'],
            ['code' => 'IT301', 'title' => 'Web Systems & Technologies', 'units' => 3, 'department' => 'CCS', 'semester' => '1st Semester', 'year_level' => 3, 'schedule' => 'Mon/Wed 3:00 - 4:30 PM', 'instructor' => 'Prof. Tim Berners-Lee'],
            ['code' => 'IT302', 'title' => 'Information Assurance & Security', 'units' => 3, 'department' => 'CCS', 'semester' => '1st Semester', 'year_level' => 3, 'schedule' => 'Tue/Thu 3:00 - 4:30 PM', 'instructor' => 'Dr. Claude Shannon'],
            ['code' => 'SAD401', 'title' => 'Systems Analysis & Design', 'units' => 3, 'department' => 'CCS', 'semester' => '1st Semester', 'year_level' => 4, 'schedule' => 'Fri 8:00 - 11:00 AM', 'instructor' => 'Dr. Elena Cruz'],
            ['code' => 'CAP402', 'title' => 'Capstone Project 1', 'units' => 3, 'department' => 'CCS', 'semester' => '1st Semester', 'year_level' => 4, 'schedule' => 'Sat 9:00 - 12:00 PM', 'instructor' => 'Prof. Linus Torvalds'],
        ];

        $createdCourses = [];
        foreach ($courses as $c) {
            $createdCourses[] = Course::create($c);
        }

        $sectionsByCourse = [];
        foreach ($createdCourses as $course) {
            $sectionsByCourse[$course->id] = AcademicSection::create([
                'course_id' => $course->id,
                'faculty_id' => $faculty->id,
                'section_code' => "{$course->code}-{$course->year_level}A",
                'program' => 'BS Information Technology',
                'year_level' => $course->year_level,
                'academic_year' => '2026-2027',
                'semester' => '1st Semester',
                'schedule' => $course->schedule,
                'room' => 'CCS-'.(100 + $course->year_level),
                'capacity' => 40,
                'status' => 'open',
            ]);
        }

        // 3. Student Personas
        $studentTypes = [
            [
                'name' => 'Alex Rivera (New Student)',
                'email' => 'alex.new@student.university.edu',
                'type' => 'new',
                'year' => 1,
                'program' => 'BS Information Technology',
                'student_num' => '2026-00101',
                'status' => 'active',
                'school' => 'Metro Science High School',
                'app_status' => 'approved',
                'clearance' => ['cashier' => true, 'library' => true, 'dept' => true, 'dean' => true, 'guidance' => true, 'registrar' => true],
            ],
            [
                'name' => 'Beatrice Gomez (Transferee)',
                'email' => 'beatrice.transferee@student.university.edu',
                'type' => 'transferee',
                'year' => 2,
                'program' => 'BS Information Technology',
                'student_num' => '2026-00204',
                'status' => 'pending',
                'school' => 'St. Jude State College',
                'app_status' => 'pending',
                'clearance' => ['cashier' => false, 'library' => true, 'dept' => false, 'dean' => false, 'guidance' => true, 'registrar' => false],
            ],
            [
                'name' => 'Carlos Mendoza (Irregular Student)',
                'email' => 'carlos.irregular@student.university.edu',
                'type' => 'irregular',
                'year' => 3,
                'program' => 'BS Information Technology',
                'student_num' => '2024-00892',
                'status' => 'active',
                'school' => 'University High School',
                'app_status' => 'approved',
                'clearance' => ['cashier' => true, 'library' => true, 'dept' => true, 'dean' => false, 'guidance' => true, 'registrar' => false],
            ],
            [
                'name' => 'Diana Morales (Old / Regular Student)',
                'email' => 'diana.old@student.university.edu',
                'type' => 'old',
                'year' => 4,
                'program' => 'BS Information Technology',
                'student_num' => '2023-00431',
                'status' => 'active',
                'school' => 'City Central Academy',
                'app_status' => 'approved',
                'clearance' => ['cashier' => true, 'library' => true, 'dept' => true, 'dean' => true, 'guidance' => true, 'registrar' => true],
            ],
            [
                'name' => 'Edward Lim (Shiftee Student)',
                'email' => 'edward.shiftee@student.university.edu',
                'type' => 'shiftee',
                'year' => 2,
                'program' => 'BS Information Technology',
                'student_num' => '2025-00612',
                'status' => 'pending',
                'school' => 'University College of Engineering',
                'app_status' => 'pending',
                'clearance' => ['cashier' => true, 'library' => true, 'dept' => false, 'dean' => false, 'guidance' => true, 'registrar' => false],
            ],
            [
                'name' => 'Fiona Reyes (Returnee Student)',
                'email' => 'fiona.returnee@student.university.edu',
                'type' => 'returnee',
                'year' => 3,
                'program' => 'BS Information Technology',
                'student_num' => '2022-00155',
                'status' => 'active',
                'school' => 'University High School',
                'app_status' => 'approved',
                'clearance' => ['cashier' => false, 'library' => false, 'dept' => true, 'dean' => false, 'guidance' => true, 'registrar' => false],
            ],
        ];

        foreach ($studentTypes as $st) {
            $user = User::create([
                'name' => $st['name'],
                'email' => $st['email'],
                'password' => Hash::make(self::DEMO_PASSWORD),
                'role' => 'student',
            ]);

            $student = Student::create([
                'user_id' => $user->id,
                'name' => $st['name'],
                'email' => $st['email'],
                'student_number' => $st['student_num'],
                'course_program' => $st['program'],
                'year_level' => $st['year'],
                'student_type' => $st['type'],
                'status' => $st['status'],
            ]);

            // Create Application Record
            StudentApplication::create([
                'user_id' => $user->id,
                'student_id' => $student->id,
                // Legacy `type` column supports only admission pathway values;
                // `student_type` preserves every supported lifecycle category.
                'type' => in_array($st['type'], ['new', 'transferee', 'shiftee', 'returnee'], true) ? $st['type'] : 'new',
                'student_type' => $st['type'],
                'target_program' => $st['program'],
                'year_level' => $st['year'],
                'semester' => '1st Semester',
                'academic_year' => '2026-2027',
                'educational_background' => "Graduate / Previous student of {$st['school']}. Applied with complete entrance credentials.",
                'previous_school' => $st['school'],
                'contact_number' => '+63 917 '.rand(100, 999).' '.rand(1000, 9999),
                'address' => 'Quezon City, Metro Manila, Philippines',
                'emergency_contact' => 'Parent/Guardian (+63 918 555 1234)',
                'submitted_documents' => ['Form 137 / TOR', 'Certificate of Good Moral', 'PSA Birth Certificate', '2x2 ID Photo'],
                'status' => $st['app_status'],
                'evaluation_notes' => $st['app_status'] === 'approved' ? 'Credentials verified by Registrar Office. Eligible for enrollment.' : 'Pending document evaluation by Dean and Registrar.',
                'reviewed_by' => $st['app_status'] === 'approved' ? 'Maria Santos' : null,
                'assigned_section' => $st['app_status'] === 'approved' ? "IT-{$st['year']}A" : null,
            ]);

            // Create Clearance
            $cl = $st['clearance'];
            Clearance::create([
                'student_id' => $student->id,
                'academic_year' => '2026-2027',
                'semester' => '1st Semester',
                'cashier_status' => $cl['cashier'],
                'cashier_remarks' => $cl['cashier'] ? 'Paid full tuition assessment' : 'Remaining balance: PHP 4,500.00',
                'library_status' => $cl['library'],
                'library_remarks' => $cl['library'] ? 'No unreturned books' : '1 Overdue Book: Introduction to Algorithms',
                'dept_status' => $cl['dept'],
                'dept_remarks' => $cl['dept'] ? 'Curriculum checklist verified' : 'Pending curriculum advising for prerequisite verification',
                'dean_status' => $cl['dean'],
                'dean_remarks' => $cl['dean'] ? 'Academic standing in good order' : 'Pending Dean final endorsement',
                'guidance_status' => $cl['guidance'],
                'guidance_remarks' => 'Exit interview & routine counseling passed',
                'registrar_status' => $cl['registrar'],
                'registrar_remarks' => $cl['registrar'] ? 'All official records complete' : 'Waiting for Cashier and Department clearances',
                'status' => ($cl['cashier'] && $cl['library'] && $cl['dept'] && $cl['dean'] && $cl['guidance'] && $cl['registrar']) ? 'cleared' : 'pending',
            ]);

            // Create Enrollments (Sample Grades)
            foreach ($createdCourses as $course) {
                if ($student->status !== 'active') {
                    continue;
                }
                if ((int) $course->year_level !== (int) $student->year_level) {
                    continue;
                }
                $midterm = rand(10, 20) / 10 + 1.0; // 1.0 to 3.0
                $final = rand(10, 20) / 10 + 1.0;
                $grade = round(($midterm + $final) / 2, 2);
                $courseSection = $sectionsByCourse[$course->id] ?? null;
                $eligibleForSection = $courseSection
                    && $student->course_program === $courseSection->program
                    && (int) $student->year_level === (int) $courseSection->year_level;

                Enrollment::create([
                    'student_id' => $student->id,
                    'course_id' => $course->id,
                    'academic_section_id' => $eligibleForSection ? $courseSection->id : null,
                    'academic_year' => '2026-2027',
                    'semester' => '1st Semester',
                    'midterm_grade' => $midterm,
                    'final_grade' => $final,
                    'grade' => (string) $grade,
                    'status' => 'enrolled',
                    'grades_released' => true,
                    'remarks' => $grade <= 3.0 ? 'Passed' : 'Conditional',
                ]);
                if ($eligibleForSection) {
                    $courseSection->students()->syncWithoutDetaching([
                        $student->id => ['status' => 'enrolled', 'enrolled_at' => now()],
                    ]);
                }
            }

            // Create Sample Document Requests
            DocumentRequest::create([
                'student_id' => $student->id,
                'type' => 'Transcript of Records (TOR)',
                'document_type' => 'Transcript of Records (TOR)',
                'purpose' => 'Employment / Scholarship Application',
                'copies' => 2,
                'fee' => 300.00,
                'payment_status' => $cl['cashier'] ? 'paid' : 'unpaid',
                'status' => $cl['cashier'] ? 'processing' : 'waiting_for_payment',
                'remarks' => 'Express processing requested',
            ]);

            DocumentRequest::create([
                'student_id' => $student->id,
                'type' => 'Certificate of Good Moral Character',
                'document_type' => 'Certificate of Good Moral Character',
                'purpose' => 'Internship Requirement',
                'copies' => 1,
                'fee' => 150.00,
                'payment_status' => 'paid',
                'status' => 'ready_for_pickup',
                'remarks' => 'Signed by Guidance Office. Available at Counter 3.',
            ]);
        }
    }
}
