<?php

namespace App\Http\Controllers;

use App\Models\AcademicSection;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\SectionEnrollment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SectionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = AcademicSection::with(['course', 'faculty'])
            ->withCount(['roster as enrolled_count' => fn ($q) => $q->where('status', 'enrolled')]);

        if ($user->role === 'faculty') {
            $query->where('faculty_id', $user->id);
        } elseif ($user->role === 'student') {
            $student = $user->student;
            if (!$student) return response()->json(['message' => 'No student profile is attached to this account.'], 403);
            $query->where('status', 'open')
                ->where('program', $student->course_program)
                ->where('year_level', $student->year_level);
        } elseif ($user->role === 'department_head') {
            if (!$user->department) return response()->json(['message' => 'Your account must be assigned to a department before using this page.'], 403);
            $query->whereHas('course', fn ($course) => $course->where('department', $user->department));
        } elseif (!in_array($user->role, ['admin', 'registrar'], true)) {
            return response()->json(['message' => 'You are not authorized to view academic sections.'], 403);
        }

        if ($request->filled('status')) $query->where('status', $request->string('status')->toString());

        return response()->json(['success' => true, 'sections' => $query->orderBy('section_code')->get()]);
    }

    public function faculty(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['admin', 'department_head'], true)) {
            return response()->json(['message' => 'Only department heads and administrators can view faculty assignments.'], 403);
        }

        $query = User::query()->where('role', 'faculty')->orderBy('name');
        if ($user->role === 'department_head') {
            if (!$user->department) return response()->json(['message' => 'Your account must be assigned to a department first.'], 403);
            $query->where('department', $user->department);
        }

        return response()->json(['success' => true, 'faculty' => $query->get(['id', 'name', 'email', 'department'])]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['admin', 'department_head'], true)) {
            return response()->json(['message' => 'Only a department head or administrator can create a section.'], 403);
        }

        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'faculty_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'faculty')],
            'section_code' => ['required', 'string', 'max:30', Rule::unique('academic_sections', 'section_code')],
            'program' => ['required', 'string', 'max:255'],
            'year_level' => ['required', 'integer', 'min:1', 'max:6'],
            'academic_year' => ['required', 'string', 'max:30'],
            'semester' => ['required', 'string', 'max:30'],
            'schedule' => ['nullable', 'string', 'max:255'],
            'room' => ['nullable', 'string', 'max:100'],
            'capacity' => ['required', 'integer', 'min:1', 'max:250'],
            'status' => ['required', Rule::in(['draft', 'open', 'closed'])],
        ]);

        $course = Course::findOrFail($validated['course_id']);
        if ($user->role === 'department_head' && $course->department !== $user->department) {
            return response()->json(['message' => 'You can only manage subjects belonging to your department.'], 403);
        }
        if ((int) $course->year_level !== (int) $validated['year_level']) {
            return response()->json(['message' => 'The section year level must match the subject offering.'], 422);
        }
        if ($course->semester !== $validated['semester']) {
            return response()->json(['message' => 'The section semester must match the subject offering.'], 422);
        }

        if (!empty($validated['faculty_id'])) {
            $faculty = User::findOrFail($validated['faculty_id']);
            if ($user->role === 'department_head' && $faculty->department !== $user->department) {
                return response()->json(['message' => 'Assigned faculty must belong to your department.'], 422);
            }
        }

        $section = AcademicSection::create($validated);

        return response()->json(['success' => true, 'section' => $section->load(['course', 'faculty'])], 201);
    }

    public function update(Request $request, AcademicSection $section)
    {
        $user = $request->user();
        if (!in_array($user->role, ['admin', 'department_head'], true)) {
            return response()->json(['message' => 'Only a department head or administrator can update a section.'], 403);
        }
        if ($user->role === 'department_head' && (!$user->department || $section->course()->where('department', $user->department)->doesntExist())) {
            return response()->json(['message' => 'You can only manage sections belonging to your department.'], 403);
        }

        $validated = $request->validate([
            'faculty_id' => ['sometimes', 'nullable', Rule::exists('users', 'id')->where('role', 'faculty')],
            'section_code' => ['sometimes', 'string', 'max:30', Rule::unique('academic_sections', 'section_code')->ignore($section->id)],
            'program' => ['sometimes', 'string', 'max:255'],
            'year_level' => ['sometimes', 'integer', 'min:1', 'max:6'],
            'schedule' => ['sometimes', 'nullable', 'string', 'max:255'],
            'room' => ['sometimes', 'nullable', 'string', 'max:100'],
            'capacity' => ['sometimes', 'integer', 'min:1', 'max:250'],
            'status' => ['sometimes', Rule::in(['draft', 'open', 'closed'])],
        ]);

        $enrolledCount = $section->roster()->where('status', 'enrolled')->count();
        if (isset($validated['capacity']) && $validated['capacity'] < $enrolledCount) {
            return response()->json(['message' => 'Capacity cannot be lower than the current enrolled roster.'], 422);
        }
        if (array_key_exists('faculty_id', $validated) && $validated['faculty_id'] !== null && $user->role === 'department_head') {
            $faculty = User::findOrFail($validated['faculty_id']);
            if ($faculty->department !== $user->department) {
                return response()->json(['message' => 'Assigned faculty must belong to your department.'], 422);
            }
        }
        $section->update($validated);

        return response()->json(['success' => true, 'section' => $section->fresh()->load(['course', 'faculty'])]);
    }

    public function enroll(Request $request, AcademicSection $section)
    {
        $user = $request->user();
        if ($user->role === 'student') {
            if (!$user->student || ($request->filled('student_id') && (int) $request->input('student_id') !== (int) $user->student->id)) {
                return response()->json(['message' => 'You can only request enrollment for your own student record.'], 403);
            }
            $student = $user->student;
            if ($student->status !== 'active' || $student->clearance?->status !== 'cleared') {
                return response()->json(['message' => 'Section enrollment opens after the Registrar approves your application and required clearance is complete.'], 422);
            }
        } elseif (in_array($user->role, ['admin', 'registrar', 'department_head'], true)) {
            $data = $request->validate(['student_id' => ['required', 'integer', 'exists:students,id']]);
            $student = Student::findOrFail($data['student_id']);
            if ($user->role === 'department_head' && (!$user->department || $section->course()->where('department', $user->department)->doesntExist())) {
                return response()->json(['message' => 'You can only enroll students in your department’s sections.'], 403);
            }
        } else {
            return response()->json(['message' => 'You are not authorized to enroll students in sections.'], 403);
        }

        if ($section->status !== 'open') {
            return response()->json(['message' => 'This section is not open for enrollment.'], 422);
        }
        if ($section->program !== $student->course_program || (int) $section->year_level !== (int) $student->year_level) {
            return response()->json(['message' => 'This section does not match the student’s program and year level.'], 422);
        }

        $section = DB::transaction(function () use ($section, $student) {
            $lockedSection = AcademicSection::query()->lockForUpdate()->findOrFail($section->id);
            if ($lockedSection->status !== 'open') {
                throw ValidationException::withMessages(['section' => 'This section is not open for enrollment.']);
            }
            if ($lockedSection->program !== $student->course_program || (int) $lockedSection->year_level !== (int) $student->year_level) {
                throw ValidationException::withMessages(['section' => 'This section does not match the student’s program and year level.']);
            }
            $existing = SectionEnrollment::where('academic_section_id', $lockedSection->id)
                ->where('student_id', $student->id)
                ->first();

            if (!$existing && $lockedSection->roster()->where('status', 'enrolled')->count() >= $lockedSection->capacity) {
                throw ValidationException::withMessages(['section' => 'This section has reached its capacity.']);
            }

            SectionEnrollment::updateOrCreate(
                ['academic_section_id' => $lockedSection->id, 'student_id' => $student->id],
                ['status' => 'enrolled', 'enrolled_at' => now()]
            );

            $existingEnrollment = Enrollment::query()
                ->where('student_id', $student->id)
                ->where('course_id', $lockedSection->course_id)
                ->lockForUpdate()
                ->first();

            if ($existingEnrollment?->academic_section_id
                && (int) $existingEnrollment->academic_section_id !== (int) $lockedSection->id) {
                throw ValidationException::withMessages([
                    'section' => 'This student is already enrolled in another section for this subject.',
                ]);
            }

            if ($existingEnrollment) {
                $existingEnrollment->update([
                    'academic_section_id' => $lockedSection->id,
                    'academic_year' => $lockedSection->academic_year,
                    'semester' => $lockedSection->semester,
                    'status' => 'enrolled',
                ]);
            } else {
                Enrollment::create([
                    'student_id' => $student->id,
                    'course_id' => $lockedSection->course_id,
                    'academic_section_id' => $lockedSection->id,
                    'academic_year' => $lockedSection->academic_year,
                    'semester' => $lockedSection->semester,
                    'status' => 'enrolled',
                ]);
            }

            return $lockedSection;
        });

        return response()->json(['success' => true, 'section' => $section->fresh()->load(['course', 'faculty', 'students.user'])]);
    }

    public function roster(Request $request, AcademicSection $section)
    {
        $user = $request->user();
        $isAllowed = $user->role === 'admin'
            || $user->role === 'registrar'
            || ($user->role === 'faculty' && (int) $section->faculty_id === (int) $user->id)
            || ($user->role === 'department_head' && $user->department && $section->course()->where('department', $user->department)->exists());

        if (!$isAllowed) return response()->json(['message' => 'You are not authorized to view this section roster.'], 403);

        $section->load(['course', 'faculty']);
        $section->setRelation('students', $section->students()
            ->with(['user', 'enrollments' => fn ($query) => $query->where('academic_section_id', $section->id)])
            ->get());

        return response()->json(['success' => true, 'section' => $section]);
    }
}
