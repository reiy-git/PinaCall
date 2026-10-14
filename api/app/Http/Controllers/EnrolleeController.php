<?php

namespace App\Http\Controllers;

use App\Models\Clearance;
use App\Models\EnrolleeApplication;
use App\Models\ProvisioningEmail;
use App\Models\Student;
use App\Models\StudentApplication;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnrolleeController extends Controller
{
    private function authorizeReviewer(Request $request): void
    {
        abort_unless(in_array($request->user()->role, ['admin', 'registrar'], true), 403);
    }

    public function apply(Request $request): JsonResponse
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email|unique:enrollee_applications,email',
            'contact_number' => 'required|string|max:30',
            'program' => ['required', Rule::in(config('enrollment.programs'))],
            'student_type' => 'required|in:new,transferee,irregular,old,shiftee,returnee',
            'year_level' => 'required|integer|min:1|max:6',
            'educational_background' => 'required|string|max:5000',
            'previous_school' => 'nullable|string|max:255',
            'documents' => 'required|array|min:1|max:5',
            'documents.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
        $documents = [];
        try {
            foreach ($request->file('documents') as $file) {
                $path = $file->store('enrollee-documents', 'local');
                if (! $path) {
                    throw new \RuntimeException('Document storage failed.');
                }
                $documents[] = ['path' => $path, 'name' => basename($file->getClientOriginalName())];
            }
            $data['documents'] = $documents;
            $data['reference'] = (string) Str::uuid();
            $application = EnrolleeApplication::create($data);
        } catch (\Throwable $error) {
            Storage::disk('local')->delete(array_column($documents, 'path'));
            if ($error instanceof UniqueConstraintViolationException) {
                abort(409, 'An application or account already exists for this email.');
            }
            throw $error;
        }

        return response()->json(['success' => true, 'reference' => $application->reference, 'status' => 'PENDING'], 201);
    }

    public function options(): JsonResponse
    {
        return response()->json([
            'programs' => config('enrollment.programs'),
            'document_guidance' => config('enrollment.document_guidance'),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeReviewer($request);
        $request->validate(['status' => 'nullable|in:PENDING,APPROVED,REJECTED', 'page' => 'nullable|integer|min:1']);
        $query = EnrolleeApplication::query();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $page = $query->latest()->paginate(20);
        $page->getCollection()->transform(function ($item) {
            $result = $item->toArray();
            $result['documents'] = array_map(fn ($doc, $index) => ['name' => $doc['name'], 'index' => $index], $item->documents, array_keys($item->documents));
            $result['email_sent_at'] = $item->user_id ? ProvisioningEmail::where('user_id', $item->user_id)->value('sent_at') : null;

            return $result;
        });

        return response()->json($page);
    }

    public function document(Request $request, int $id, int $index): StreamedResponse
    {
        $this->authorizeReviewer($request);
        $application = EnrolleeApplication::findOrFail($id);
        $doc = $application->documents[$index] ?? null;
        abort_unless($doc && Storage::disk('local')->exists($doc['path']), 404);

        return Storage::disk('local')->download($doc['path'], 'application-document.'.pathinfo($doc['path'], PATHINFO_EXTENSION), ['X-Content-Type-Options' => 'nosniff']);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $this->authorizeReviewer($request);
        $request->validate(['remarks' => 'nullable|string|max:5000']);
        try {
            $application = DB::transaction(function () use ($request, $id) {
                $a = EnrolleeApplication::lockForUpdate()->findOrFail($id);
                if ($a->status === 'APPROVED') {
                    return $a;
                } // repeat click is safe
                abort_unless($a->status === 'PENDING', 409, 'Only pending applications can be approved.');
                abort_if(User::whereRaw('LOWER(email) = ?', [$a->email])->exists() || Student::whereRaw('LOWER(email) = ?', [$a->email])->exists(), 409, 'This email already belongs to an account or student.');
                $password = bin2hex(random_bytes(24));
                $user = User::create(['name' => $a->name, 'email' => $a->email, 'password' => $password, 'role' => 'student', 'must_change_password' => true, 'temporary_password_expires_at' => now()->addHours(72)]);
                $student = Student::create(['user_id' => $user->id, 'name' => $a->name, 'email' => $a->email, 'student_number' => 'PC-'.Str::upper(Str::random(12)), 'course_program' => $a->program, 'year_level' => $a->year_level, 'student_type' => $a->student_type, 'status' => 'active']);
                StudentApplication::create(['user_id' => $user->id, 'student_id' => $student->id, 'type' => in_array($a->student_type, ['new', 'transferee', 'shiftee', 'returnee']) ? $a->student_type : 'new', 'student_type' => $a->student_type, 'educational_background' => $a->educational_background, 'previous_school' => $a->previous_school, 'target_program' => $a->program, 'year_level' => $a->year_level, 'contact_number' => $a->contact_number, 'academic_year' => config('enrollment.academic_year'), 'semester' => config('enrollment.semester'), 'submitted_documents' => array_column($a->documents, 'name'), 'status' => 'approved', 'reviewed_by' => $request->user()->name, 'evaluation_notes' => $request->remarks]);
                Clearance::create(['student_id' => $student->id, 'academic_year' => config('enrollment.academic_year'), 'semester' => config('enrollment.semester'), 'status' => 'pending']);
                $a->update(['status' => 'APPROVED', 'user_id' => $user->id, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'remarks' => $request->remarks]);
                ProvisioningEmail::create(['user_id' => $user->id, 'temporary_password' => $password, 'available_at' => now()]);

                return $a;
            });
        } catch (UniqueConstraintViolationException $error) {
            abort(409, 'An account or student already exists for this email.');
        }

        return response()->json(['success' => true, 'application' => $application, 'message' => 'Approved. Login instructions are queued for delivery.']);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $this->authorizeReviewer($request);
        $request->validate(['remarks' => 'nullable|string|max:5000']);
        $a = DB::transaction(function () use ($request, $id) {
            $a = EnrolleeApplication::lockForUpdate()->findOrFail($id);
            abort_unless($a->status === 'PENDING', 409, 'Only pending applications can be rejected.');
            $a->update(['status' => 'REJECTED', 'remarks' => $request->remarks, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);

            return $a;
        });

        return response()->json(['success' => true, 'application' => $a]);
    }

    public function resend(Request $request, int $id): JsonResponse
    {
        $this->authorizeReviewer($request);
        DB::transaction(function () use ($id) {
            $a = EnrolleeApplication::lockForUpdate()->findOrFail($id);
            abort_unless($a->status === 'APPROVED', 409);
            // Consistent lock order with mail worker: outbox then user.
            $mail = ProvisioningEmail::where('user_id', $a->user_id)->lockForUpdate()->firstOrFail();
            $user = User::lockForUpdate()->findOrFail($a->user_id);
            abort_unless($user->must_change_password && $user->is_active, 409, 'This account has already activated or is inactive.');
            $password = bin2hex(random_bytes(24));
            $user->update(['password' => $password, 'temporary_password_expires_at' => now()->addHours(72)]);
            $user->apiAccessTokens()->delete();
            $mail->update(['temporary_password' => $password, 'attempts' => 0, 'sent_at' => null, 'available_at' => now()]);
        });

        return response()->json(['success' => true, 'message' => 'New credentials queued; previous credentials revoked.']);
    }
}
