<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Http\Request;

class GradesController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Enrollment::with(['student.user', 'course', 'section']);

        if ($user->role === 'student') {
            if (!$user->student) return response()->json(['message' => 'No student profile is attached to this account.'], 403);
            if ($user->student->status !== 'active') {
                return response()->json(['success' => true, 'grades' => []]);
            }
            $query->where('student_id', $user->student->id)
                ->where('status', 'graded')
                ->where('grades_released', true)
                ->whereNotNull('academic_section_id')
                ->whereHas('section.roster', fn ($roster) => $roster
                    ->where('student_id', $user->student->id)
                    ->where('status', 'enrolled'));
        } elseif ($user->role === 'faculty') {
            $query->whereHas('section', fn ($section) => $section->where('faculty_id', $user->id));
        } elseif ($user->role === 'department_head') {
            if (!$user->department) return response()->json(['message' => 'Your account must be assigned to a department first.'], 403);
            $query->whereHas('course', fn ($course) => $course->where('department', $user->department));
        } elseif (!in_array($user->role, ['admin', 'registrar'], true)) {
            return response()->json(['message' => 'You are not authorized to view academic records.'], 403);
        } elseif ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        $enrollments = $query->orderBy('academic_year', 'desc')->get();

        return response()->json([
            'success' => true,
            'grades' => $enrollments,
        ]);
    }

    public function getCourses(Request $request)
    {
        $query = Course::withCount('enrollments');
        if ($request->user()->role === 'department_head') {
            if (!$request->user()->department) {
                return response()->json(['message' => 'Your account must be assigned to a department first.'], 403);
            }
            $query->where('department', $request->user()->department);
        }
        $courses = $query->orderBy('code')->get();
        return response()->json([
            'success' => true,
            'courses' => $courses,
        ]);
    }

    public function updateGrade(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['faculty', 'department_head', 'admin'], true)) {
            return response()->json(['message' => 'You are not authorized to update grades.'], 403);
        }

        $request->validate([
            'midterm_grade' => ['nullable', 'numeric', 'between:1,5', 'regex:/^[1-5](?:\\.\\d{1,2})?$/'],
            'final_grade' => ['nullable', 'numeric', 'between:1,5', 'regex:/^[1-5](?:\\.\\d{1,2})?$/'],
            'remarks' => 'nullable|string|max:500',
        ]);

        $enrollment = Enrollment::with(['student.user', 'course', 'section'])->findOrFail($id);
        if ($user->role === 'faculty' && (!$enrollment->section || (int) $enrollment->section->faculty_id !== (int) $user->id)) {
            return response()->json(['message' => 'Faculty may update grades only for students in their assigned sections.'], 403);
        }
        if ($user->role === 'department_head' && (!$user->department || $enrollment->course->department !== $user->department)) {
            return response()->json(['message' => 'You can only review grades belonging to your department.'], 403);
        }
        if (!$enrollment->section || !\App\Models\SectionEnrollment::query()
            ->where('academic_section_id', $enrollment->academic_section_id)
            ->where('student_id', $enrollment->student_id)
            ->where('status', 'enrolled')
            ->exists()) {
            return response()->json(['message' => 'Grades can only be entered for students on the active section roster.'], 422);
        }

        if ($request->has('midterm_grade')) {
            $enrollment->midterm_grade = $request->midterm_grade;
        }

        if ($request->has('final_grade')) {
            $enrollment->final_grade = $request->final_grade;
        }

        if ($enrollment->midterm_grade && $enrollment->final_grade) {
            $computed = round(($enrollment->midterm_grade + $enrollment->final_grade) / 2, 2);
            $enrollment->grade = (string)$computed;
            $enrollment->remarks = $computed <= 3.0 ? 'Passed' : ($computed <= 3.5 ? 'Conditional' : 'Failed');
            $enrollment->status = 'graded';
        } else {
            $enrollment->status = 'enrolled';
        }
        $enrollment->grades_released = false;

        if ($request->has('remarks')) {
            $enrollment->remarks = $request->remarks;
        }

        $enrollment->save();

        return response()->json([
            'success' => true,
            'message' => 'Academic grade successfully updated.',
            'enrollment' => $enrollment,
        ]);
    }

    public function releaseGrade(Request $request, Enrollment $enrollment)
    {
        $user = $request->user();
        $isAuthorized = in_array($user->role, ['admin', 'registrar'], true)
            || ($user->role === 'faculty' && $enrollment->section && (int) $enrollment->section->faculty_id === (int) $user->id);

        if (!$isAuthorized) {
            return response()->json(['message' => 'You are not authorized to release this grade.'], 403);
        }
        if ($enrollment->status !== 'graded' || $enrollment->midterm_grade === null || $enrollment->final_grade === null) {
            return response()->json(['message' => 'Complete and save both grading periods before releasing this record.'], 422);
        }
        if (!$enrollment->section || !\App\Models\SectionEnrollment::query()
            ->where('academic_section_id', $enrollment->academic_section_id)
            ->where('student_id', $enrollment->student_id)
            ->where('status', 'enrolled')
            ->exists()) {
            return response()->json(['message' => 'Only grades for an active section roster may be released.'], 422);
        }

        $enrollment->update(['grades_released' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Grade released to the student portal.',
            'enrollment' => $enrollment->fresh()->load(['student.user', 'course', 'section']),
        ]);
    }
}
