<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PortalAdminController extends Controller
{
    private const ROLES = ['student', 'registrar', 'cashier', 'department_head', 'faculty', 'admin'];

    public function storeUser(Request $request)
    {
        if ($response = $this->requireAdmin($request)) return $response;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'max:255'],
            'role' => ['required', Rule::in(self::ROLES)],
            'department' => ['nullable', 'string', 'max:100'],
            'course_program' => ['required_if:role,student', 'nullable', 'string', 'max:255'],
            'year_level' => ['required_if:role,student', 'nullable', 'integer', 'min:1', 'max:6'],
            'student_type' => ['required_if:role,student', 'nullable', Rule::in(['new', 'transferee', 'irregular', 'old', 'shiftee', 'returnee'])],
            'student_status' => ['required_if:role,student', 'nullable', Rule::in(['pending', 'active', 'graduated', 'rejected'])],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => trim($data['name']),
                'email' => mb_strtolower(trim($data['email'])),
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'department' => $data['department'] ?? null,
                'is_active' => true,
            ]);

            if ($user->role === 'student') {
                Student::create([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'student_number' => 'PENDING-' . strtoupper(bin2hex(random_bytes(5))),
                    'course_program' => $data['course_program'],
                    'year_level' => $data['year_level'],
                    'student_type' => $data['student_type'],
                    'status' => $data['student_status'],
                ]);
            }

            return $user->load('student');
        });

        Log::info('Portal account created by administrator.', [
            'actor_user_id' => $request->user()->id,
            'target_user_id' => $user->id,
            'role' => $user->role,
        ]);

        return response()->json(['success' => true, 'user' => $user], 201);
    }

    public function updateUser(Request $request, User $user)
    {
        if ($response = $this->requireAdmin($request)) return $response;
        if ($user->is($request->user()) && ($request->input('role', $user->role) !== 'admin' || !$request->boolean('is_active', $user->is_active))) {
            return response()->json(['message' => 'You cannot change or deactivate your own administrator account.'], 422);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'nullable', 'string', 'min:12', 'max:255'],
            'role' => ['sometimes', 'required', Rule::in(self::ROLES)],
            'department' => ['sometimes', 'nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'required', 'boolean'],
            'course_program' => ['sometimes', 'required', 'string', 'max:255'],
            'year_level' => ['sometimes', 'required', 'integer', 'min:1', 'max:6'],
            'student_type' => ['sometimes', 'required', Rule::in(['new', 'transferee', 'irregular', 'old', 'shiftee', 'returnee'])],
            'student_status' => ['sometimes', 'required', Rule::in(['pending', 'active', 'graduated', 'rejected'])],
        ]);
        if (isset($data['email'])) $data['email'] = mb_strtolower(trim($data['email']));
        if (isset($data['name'])) $data['name'] = trim($data['name']);
        if (isset($data['password']) && $data['password'] !== null) $data['password'] = Hash::make($data['password']);

        DB::transaction(function () use ($user, $data) {
            $studentFields = ['course_program', 'year_level', 'student_type'];
            $studentData = array_intersect_key($data, array_flip($studentFields));
            if (array_key_exists('student_status', $data)) $studentData['status'] = $data['student_status'];
            unset($data['course_program'], $data['year_level'], $data['student_type'], $data['student_status']);

            $user->update($data);
            if ($studentData && $user->student) {
                $user->student->update($studentData);
            }
            if (($data['role'] ?? $user->role) === 'student' && !$user->student) {
                Student::create([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'student_number' => 'PENDING-' . strtoupper(bin2hex(random_bytes(5))),
                    'course_program' => $studentData['course_program'] ?? 'BS Information Technology',
                    'year_level' => $studentData['year_level'] ?? 1,
                    'student_type' => $studentData['student_type'] ?? 'new',
                    'status' => $studentData['status'] ?? 'pending',
                ]);
            }
            if (isset($data['name']) || isset($data['email'])) {
                $user->student?->update(array_intersect_key($data, array_flip(['name', 'email'])));
            }
        });

        Log::info('Portal account updated by administrator.', [
            'actor_user_id' => $request->user()->id,
            'target_user_id' => $user->id,
            'changed_fields' => array_keys($request->except(['password'])),
        ]);

        return response()->json(['success' => true, 'user' => $user->fresh()->load('student')]);
    }

    public function storeCourse(Request $request)
    {
        if ($response = $this->requireAdmin($request)) return $response;
        $request->merge(['code' => strtoupper(trim((string) $request->input('code', '')))]);
        $data = $request->validate($this->courseRules());
        $data['title'] = trim($data['title']);
        $course = Course::create($data);

        Log::info('Course catalog subject created by administrator.', [
            'actor_user_id' => $request->user()->id,
            'course_id' => $course->id,
        ]);

        return response()->json(['success' => true, 'course' => $course], 201);
    }

    public function updateCourse(Request $request, Course $course)
    {
        if ($response = $this->requireAdmin($request)) return $response;
        if ($request->has('code')) $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        $data = $request->validate($this->courseRules($course));
        if (isset($data['title'])) $data['title'] = trim($data['title']);
        if (isset($data['year_level']) && (int) $data['year_level'] !== (int) $course->year_level && $course->sections()->exists()) {
            return response()->json(['message' => 'Year level cannot change while sections reference this subject. Close or update those offerings first.'], 422);
        }
        if (isset($data['semester']) && $data['semester'] !== $course->semester && $course->sections()->exists()) {
            return response()->json(['message' => 'Semester cannot change while sections reference this subject. Update the section offerings first.'], 422);
        }
        $course->update($data);

        Log::info('Course catalog subject updated by administrator.', [
            'actor_user_id' => $request->user()->id,
            'course_id' => $course->id,
            'changed_fields' => array_keys($data),
        ]);

        return response()->json(['success' => true, 'course' => $course->fresh()]);
    }

    private function requireAdmin(Request $request)
    {
        return $request->user()->role === 'admin'
            ? null
            : response()->json(['message' => 'Only system administrators can maintain accounts and subjects.'], 403);
    }

    private function courseRules(?Course $course = null): array
    {
        $uniqueCode = Rule::unique('courses', 'code');
        if ($course) $uniqueCode->ignore($course->id);

        return [
            'code' => ['required', 'string', 'max:30', $uniqueCode],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'units' => ['required', 'integer', 'min:1', 'max:12'],
            'department' => ['required', 'string', 'max:100'],
            'semester' => ['required', 'string', 'max:30'],
            'year_level' => ['required', 'integer', 'min:1', 'max:6'],
        ];
    }
}
