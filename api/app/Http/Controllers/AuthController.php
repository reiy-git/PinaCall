<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string|max:255',
        ]);

        $user = User::with(['student.clearance', 'student.enrollments.course', 'student.documentRequests', 'applications'])
            ->where('email', $request->email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid institutional credentials. Please check your email and password.',
            ], 401);
        }
        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This account is inactive. Contact the university service desk for assistance.',
            ], 403);
        }

        [$plainToken, $token] = $this->issueToken($user);

        return response()->json([
            'success' => true,
            'message' => 'Authentication successful.',
            'user' => $user,
            'access_token' => $plainToken,
            'expires_at' => $token->expires_at,
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:12',
            'student_type' => 'required|in:new,transferee,irregular,old,shiftee,returnee',
            'course_program' => 'required|string|max:255',
            'year_level' => 'required|integer|min:1|max:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'student',
        ]);

        Student::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'student_number' => 'PENDING-' . Str::upper(Str::random(10)),
            'course_program' => $request->course_program,
            'year_level' => $request->year_level,
            'student_type' => $request->student_type,
            'status' => 'pending',
        ]);

        $user->load(['student.clearance', 'student.enrollments', 'applications']);

        [$plainToken, $token] = $this->issueToken($user);

        return response()->json([
            'success' => true,
            'message' => 'Student registration successful.',
            'user' => $user,
            'access_token' => $plainToken,
            'expires_at' => $token->expires_at,
        ], 201);
    }

    public function getPersonas(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Only administrators can view the account registry.'], 403);
        }

        $users = User::with(['student.clearance', 'student.enrollments.course', 'student.documentRequests', 'applications'])
            ->get();

        return response()->json([
            'success' => true,
            'personas' => $users,
        ]);
    }

    public function logout(Request $request)
    {
        $request->attributes->get('portal_token')?->delete();

        return response()->json(['success' => true]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user()->load(['student.clearance', 'student.enrollments.course', 'student.documentRequests', 'applications']),
        ]);
    }

    private function issueToken(User $user): array
    {
        $plainToken = Str::random(80);
        $token = $user->apiAccessTokens()->create([
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addHours(8),
        ]);

        return [$plainToken, $token];
    }
}
