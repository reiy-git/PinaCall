<?php

namespace App\Http\Controllers;

use App\Models\ProvisioningEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $user = User::with(['student.clearance', 'student.enrollments.course', 'student.documentRequests', 'applications', 'approvedAdmission'])
            ->whereRaw('LOWER(email) = ?', [strtolower(trim((string) $request->email))])
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid institutional credentials. Please check your email and password.',
            ], 401);
        }
        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This account is inactive. Contact the university service desk for assistance.',
            ], 403);
        }

        if ($user->must_change_password && (! $user->temporary_password_expires_at || $user->temporary_password_expires_at->isPast())) {
            return response()->json(['message' => 'Temporary credentials expired. Contact the Registrar.'], 403);
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

    public function firstLoginReset(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string|max:255',
            'password' => 'required|string|min:12|max:255|confirmed|different:current_password',
        ]);
        DB::transaction(function () use ($request) {
            // Lock outbox first, matching delivery/reissue lock order.
            $mail = ProvisioningEmail::where('user_id', $request->user()->id)->lockForUpdate()->first();
            $user = User::lockForUpdate()->findOrFail($request->user()->id);
            abort_unless($user->must_change_password, 409, 'Your account password has already been set.');
            abort_unless($user->temporary_password_expires_at && $user->temporary_password_expires_at->isFuture(), 403, 'Temporary credentials expired. Contact the Registrar.');
            abort_unless(Hash::check($request->current_password, $user->password), 422, 'Current password is incorrect.');
            $user->update(['password' => $request->password, 'must_change_password' => false, 'temporary_password_expires_at' => null]);
            $mail?->update(['temporary_password' => null]);
            $user->apiAccessTokens()->delete();
        });

        return response()->json(['success' => true, 'message' => 'Password set. Sign in with your new password.']);
    }

    public function getPersonas(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Only administrators can view the account registry.'], 403);
        }

        $users = User::with(['student.clearance', 'student.enrollments.course', 'student.documentRequests', 'applications', 'approvedAdmission'])
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
            'user' => $request->user()->load(['student.clearance', 'student.enrollments.course', 'student.documentRequests', 'applications', 'approvedAdmission']),
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
