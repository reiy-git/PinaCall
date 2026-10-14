<?php

namespace App\Http\Controllers;

use App\Models\Clearance;
use App\Models\AcademicSection;
use App\Models\Student;
use App\Models\StudentApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnrollmentApplicationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['student', 'registrar', 'admin'], true)) {
            return response()->json(['message' => 'You are not authorized to view enrollment applications.'], 403);
        }
        $query = StudentApplication::with(['user', 'student']);
        if ($user->role === 'student') {
            $query->where('user_id', $user->id);
        }

        if ($request->has('student_type') && $request->student_type !== 'all') {
            $query->where('student_type', $request->student_type);
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && !empty($request->search)) {
            $term = $request->search;
            $query->where(function ($searchQuery) use ($term) {
                $searchQuery->whereHas('user', function ($userQuery) use ($term) {
                    $userQuery->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                })->orWhere('target_program', 'like', "%{$term}%")
                    ->orWhere('previous_school', 'like', "%{$term}%");
            });
        }

        $applications = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'applications' => $applications,
        ]);
    }

    public function students(Request $request)
    {
        if (!in_array($request->user()->role, ['registrar', 'admin'], true)) {
            return response()->json(['message' => 'Only the Registrar or an administrator can view the student registry.'], 403);
        }

        return response()->json([
            'success' => true,
            'students' => Student::with(['user', 'clearance'])->orderBy('student_number')->get(),
        ]);
    }

    public function show(Request $request, $id)
    {
        $application = StudentApplication::with(['user', 'student.enrollments.course', 'student.clearance'])->findOrFail($id);
        if ($request->user()->role === 'student' && (int) $application->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'You can only view your own application.'], 403);
        }
        if (!in_array($request->user()->role, ['student', 'registrar', 'admin'], true)) {
            return response()->json(['message' => 'You are not authorized to view this application.'], 403);
        }
        return response()->json([
            'success' => true,
            'application' => $application,
        ]);
    }

    public function store(Request $request)
    {
        if ($request->user()->role !== 'student' || !$request->user()->student) {
            return response()->json(['message' => 'Only a signed-in student can submit an enrollment application.'], 403);
        }
        $request->validate([
            'student_type' => 'required|in:new,transferee,irregular,old,shiftee,returnee',
            'target_program' => 'required|string|max:255',
            'year_level' => 'required|integer|min:1|max:6',
            'educational_background' => 'required|string',
            'previous_school' => 'nullable|string',
            'contact_number' => 'nullable|string',
            'address' => 'nullable|string',
            'emergency_contact' => 'nullable|string',
            'submitted_documents' => 'nullable|array',
        ]);

        $user = $request->user();
        $student = $user->student;
        // A provisioned account already completed initial admission. Other categories
        // remain available for continuing, returning and changing-program services.
        if (in_array($request->student_type, ['new', 'transferee'], true) && $user->approvedAdmission()->exists()) {
            return response()->json(['message' => 'Your initial admission is already approved. Use continuing-student services instead.'], 409);
        }

        if ($student->applications()->where('status', 'pending')->exists()) {
            return response()->json(['message' => 'You already have an application awaiting review.'], 409);
        }

        $student->course_program = $request->target_program;
        $student->year_level = $request->year_level;
        $student->student_type = $request->student_type;
        $student->status = 'pending';
        $student->save();

        $application = StudentApplication::create([
            'user_id' => $user->id,
            'student_id' => $student->id,
            // The legacy `type` enum is kept for backward compatibility.
            // The broader `student_type` field models every PINACALL enrollee category.
            'type' => in_array($request->student_type, ['new', 'transferee', 'shiftee', 'returnee'], true) ? $request->student_type : 'new',
            'student_type' => $request->student_type,
            'target_program' => $request->target_program,
            'year_level' => $request->year_level,
            'semester' => '1st Semester',
            'academic_year' => '2026-2027',
            'educational_background' => $request->educational_background,
            'previous_school' => $request->previous_school ?? 'N/A',
            'contact_number' => $request->contact_number,
            'address' => $request->address,
            'emergency_contact' => $request->emergency_contact,
            'submitted_documents' => $request->submitted_documents ?? ['Form 137', 'PSA Birth Certificate'],
            'status' => 'pending',
            'evaluation_notes' => 'Application received and queued for evaluation.',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Enrollment application submitted successfully.',
            'application' => $application->load(['user', 'student']),
        ], 201);
    }

    public function review(Request $request, $id)
    {
        if (!in_array($request->user()->role, ['registrar', 'admin'], true)) {
            return response()->json(['message' => 'Only the Registrar or an administrator can decide applications.'], 403);
        }
        $request->validate([
            'status' => 'required|in:approved,rejected,pending',
            'evaluation_notes' => 'nullable|string',
            'reviewed_by' => 'nullable|string',
            'assigned_section_id' => 'nullable|integer|exists:academic_sections,id',
        ]);

        $application = DB::transaction(function () use ($request, $id) {
            $application = StudentApplication::with(['user', 'student'])->lockForUpdate()->findOrFail($id);
            $section = null;
            if ($request->filled('assigned_section_id')) {
                $section = AcademicSection::with('course')->lockForUpdate()->findOrFail($request->integer('assigned_section_id'));
                if ($request->status !== 'approved') {
                    abort(422, 'A section can be assigned only when the application is approved.');
                }
                if ($section->status !== 'open'
                    || $section->program !== $application->target_program
                    || (int) $section->year_level !== (int) $application->year_level) {
                    abort(422, 'The selected section is not open or does not match the applicant’s program and year.');
                }
                $alreadyEnrolled = $section->roster()
                    ->where('status', 'enrolled')
                    ->where('student_id', $application->student_id)
                    ->exists();
                if (!$alreadyEnrolled && $section->roster()->where('status', 'enrolled')->count() >= $section->capacity) {
                    abort(422, 'The selected section has reached capacity.');
                }
            }

            $application->status = $request->status;
            $application->evaluation_notes = $request->evaluation_notes ?? $application->evaluation_notes;
            $application->reviewed_by = $request->user()->name;
            if ($section) $application->assigned_section = $section->section_code;
            $application->save();

            if ($application->student) {
                $application->student->status = $request->status === 'approved' ? 'active' : ($request->status === 'rejected' ? 'rejected' : 'pending');
                $application->student->course_program = $application->target_program;
                $application->student->year_level = $application->year_level;
                $application->student->student_type = $application->student_type;
                $application->student->save();

                if ($request->status === 'approved') {
                    Clearance::firstOrCreate(
                        ['student_id' => $application->student->id],
                        [
                            'academic_year' => $application->academic_year,
                            'semester' => $application->semester,
                            'cashier_status' => false,
                            'cashier_remarks' => 'Assessment ready for payment',
                            'library_status' => false,
                            'dept_status' => false,
                            'dept_remarks' => 'Awaiting curriculum advising',
                            'dean_status' => false,
                            'guidance_status' => false,
                            'registrar_status' => false,
                            'status' => 'pending',
                        ]
                    );
                }
            }

            return $application;
        });

        return response()->json([
            'success' => true,
            'message' => "Application has been {$request->status}.",
            'application' => $application,
        ]);
    }
}
