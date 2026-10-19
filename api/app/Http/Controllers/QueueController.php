<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequest;
use App\Models\PhysicalQueueToken;
use App\Models\PhysicalQueueTokenAttempt;
use App\Models\PhysicalQueueTokenEvent;
use App\Models\QueueToken;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueueController extends Controller
{
    private const UNAVAILABLE_TOKEN_MESSAGE = 'This queue token is unavailable. Please use an available token at the service area or ask staff for assistance.';

    public function activatePhysicalToken(Request $request)
    {
        $request->validate([
            'qr_secret' => 'required|string|min:32|max:255',
            'document_request_id' => 'required|integer|exists:document_requests,id',
        ]);

        $student = $request->user()->student;
        if ($request->user()->role !== 'student' || ! $student || $student->status !== 'active') {
            return response()->json(['message' => 'An active student account is required for queue check-in.'], 403);
        }

        $today = now()->toDateString();
        $secretHash = hash('sha256', $request->qr_secret);
        $physicalToken = PhysicalQueueToken::where('qr_secret_hash', $secretHash)->first();
        if (! $physicalToken) {
            return response()->json(['message' => self::UNAVAILABLE_TOKEN_MESSAGE], 422);
        }

        $sameStudentAttempts = PhysicalQueueTokenAttempt::where('physical_queue_token_id', $physicalToken->id)
            ->where('student_id', $student->id)
            ->whereDate('service_date', $today)
            ->count();
        if ($sameStudentAttempts >= 3) {
            $this->recordAttempt($physicalToken, $student, 'blocked', 'student_attempt_limit');

            return response()->json(['message' => 'This token was already used or attempted today. Please use another available token or ask staff for help.'], 429);
        }

        return DB::transaction(function () use ($request, $student, $physicalToken, $today) {
            $physicalToken = PhysicalQueueToken::lockForUpdate()->findOrFail($physicalToken->id);
            if ($physicalToken->status !== 'available') {
                $this->recordAttempt($physicalToken, $student, 'unavailable', 'token_unavailable');
                $this->lockRepeatedlyFailedToken($physicalToken, $today);

                return response()->json(['message' => self::UNAVAILABLE_TOKEN_MESSAGE], 422);
            }

            $existingTicket = QueueToken::where('student_id', $student->id)
                ->whereDate('queue_date', $today)
                ->whereIn('status', ['waiting', 'serving'])
                ->lockForUpdate()
                ->first();
            if ($existingTicket) {
                $this->recordAttempt($physicalToken, $student, 'blocked', 'active_ticket');

                return response()->json([
                    'success' => true,
                    'message' => 'You already have an active queue ticket.',
                    'token' => $existingTicket->load('documentRequest'),
                    'already_checked_in' => true,
                ]);
            }

            $documentRequest = DocumentRequest::where('id', $request->integer('document_request_id'))
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->first();
            if (! $documentRequest || ! $this->requestIsEligibleForStage($documentRequest, $physicalToken->service_stage)) {
                $this->recordAttempt($physicalToken, $student, 'blocked', 'ineligible_request');

                return response()->json(['message' => 'The selected request is not eligible for this queue.'], 422);
            }

            $sequence = QueueToken::nextSequence($physicalToken->service_stage);
            $queueTicket = QueueToken::create([
                'token_number' => QueueToken::generateTokenNumber($physicalToken->service_stage, $sequence),
                'daily_sequence' => $sequence,
                'student_id' => $student->id,
                'document_request_id' => $documentRequest->id,
                'service_stage' => $physicalToken->service_stage,
                'status' => 'waiting',
                'queue_date' => $today,
                'checked_in_at' => now(),
            ]);

            $physicalToken->update([
                'status' => 'assigned',
                'assigned_student_id' => $student->id,
                'assigned_queue_token_id' => $queueTicket->id,
                'assigned_at' => now(),
            ]);
            $this->recordAttempt($physicalToken, $student, 'assigned', 'activated', $queueTicket);
            $this->recordEvent($physicalToken, 'assigned', $student->id, $queueTicket->id);

            return response()->json([
                'success' => true,
                'message' => "Check-in successful. Your queue number is {$queueTicket->token_number}. Please keep your physical token and bring it to the window.",
                'token' => $queueTicket->load('documentRequest'),
            ], 201);
        });
    }

    private function requestIsEligibleForStage(DocumentRequest $documentRequest, string $stage): bool
    {
        return $stage === 'cashier'
            ? $documentRequest->status === 'waiting_for_payment' && $documentRequest->payment_status === 'unpaid'
            : $documentRequest->status === 'ready_for_pickup' && $documentRequest->payment_status === 'paid';
    }

    private function recordAttempt(PhysicalQueueToken $physicalToken, Student $student, string $result, string $reason, ?QueueToken $queueTicket = null): void
    {
        PhysicalQueueTokenAttempt::create([
            'physical_queue_token_id' => $physicalToken->id,
            'student_id' => $student->id,
            'queue_token_id' => $queueTicket?->id,
            'service_date' => now()->toDateString(),
            'result' => $result,
            'reason' => $reason,
        ]);
    }

    private function recordEvent(PhysicalQueueToken $physicalToken, string $eventType, ?int $studentId = null, ?int $queueTicketId = null, ?int $staffId = null, ?string $notes = null): void
    {
        PhysicalQueueTokenEvent::create([
            'physical_queue_token_id' => $physicalToken->id,
            'student_id' => $studentId,
            'queue_token_id' => $queueTicketId,
            'staff_id' => $staffId,
            'event_type' => $eventType,
            'notes' => $notes,
        ]);
    }

    private function lockRepeatedlyFailedToken(PhysicalQueueToken $physicalToken, string $today): void
    {
        $differentStudents = PhysicalQueueTokenAttempt::where('physical_queue_token_id', $physicalToken->id)
            ->whereDate('service_date', $today)
            ->where('result', 'unavailable')
            ->distinct('student_id')
            ->count('student_id');
        if ($differentStudents >= 5) {
            $physicalToken->update(['status' => 'locked']);
            $this->recordEvent($physicalToken, 'locked', notes: 'Five different students made failed activation attempts today.');
        }
    }

    /**
     * Staff action: Call the next person in queue for the given stage/window.
     */
    public function callNext(Request $request)
    {
        $request->validate([
            'service_stage' => 'required|in:cashier,registrar',
            'window_number' => 'required|string',
        ]);

        $role = $request->user()->role;
        $stage = $request->service_stage;

        if ($stage === 'cashier' && ! in_array($role, ['cashier', 'admin'], true)) {
            return response()->json(['message' => 'Only cashier staff can manage the cashier queue.'], 403);
        }
        if ($stage === 'registrar' && ! in_array($role, ['registrar', 'admin'], true)) {
            return response()->json(['message' => 'Only registrar staff can manage the registrar queue.'], 403);
        }

        $windowAlreadyServing = QueueToken::where('service_stage', $stage)
            ->where('status', 'serving')
            ->where('window_number', $request->window_number)
            ->whereDate('queue_date', now()->toDateString())
            ->exists();

        if ($windowAlreadyServing) {
            return response()->json([
                'success' => false,
                'message' => 'Complete or re-call the current student before calling the next token.',
            ], 422);
        }

        // Get the next waiting token for this stage
        $token = QueueToken::where('service_stage', $stage)
            ->where('status', 'waiting')
            ->whereDate('queue_date', now()->toDateString())
            ->orderBy('daily_sequence')
            ->first();

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'No more students waiting in the queue.',
            ]);
        }

        $token->update([
            'status' => 'serving',
            'window_number' => $request->window_number,
            'called_at' => now(),
        ]);
        $physicalToken = PhysicalQueueToken::where('assigned_queue_token_id', $token->id)->first();
        if ($physicalToken) {
            $physicalToken->update(['status' => 'called']);
            $this->recordEvent($physicalToken, 'called', $token->student_id, $token->id, $request->user()->id);
        }

        return response()->json([
            'success' => true,
            'message' => "Now serving {$token->token_number} at {$request->window_number}.",
            'token' => $token->load(['student.user', 'documentRequest', 'physicalQueueToken']),
        ]);
    }

    /**
     * Staff action: Re-broadcast the current serving token to displays.
     */
    public function recallDisplay(Request $request, $id)
    {
        $token = QueueToken::with(['student.user', 'documentRequest'])->findOrFail($id);
        $this->authorizeStaffStage($request, $token->service_stage);

        if ($token->status !== 'serving') {
            return response()->json(['message' => 'Only currently serving tokens can be re-called.'], 422);
        }

        // Re-call just refreshes called_at to trigger display update
        $token->update(['called_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "Re-calling {$token->token_number} to {$token->window_number}.",
            'token' => $token,
        ]);
    }

    /**
     * Staff action: Mark a called token as no-show and auto-advance.
     */
    public function markNoShow(Request $request, $id)
    {
        $token = QueueToken::findOrFail($id);
        $this->authorizeStaffStage($request, $token->service_stage);

        if ($token->status !== 'serving') {
            return response()->json(['message' => 'Only a currently serving token can be marked as no-show.'], 422);
        }

        $token->update([
            'status' => 'no_show',
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "{$token->token_number} marked as no-show.",
            'token' => $token,
        ]);
    }

    /**
     * Staff action: Complete the current transaction at cashier stage.
     * Marks payment as paid on the linked document request, and transitions
     * the queue token to 'hold' (document processing) or creates a registrar token.
     */
    public function completeTransaction(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:pay_and_process,release_document',
            'physical_token_reference' => 'required|string|max:12',
        ]);

        $token = QueueToken::with('documentRequest')->findOrFail($id);
        $this->authorizeStaffStage($request, $token->service_stage);
        if ($token->status !== 'serving') {
            return response()->json(['message' => 'Only a currently serving token can be completed.'], 422);
        }

        $physicalToken = PhysicalQueueToken::where('assigned_queue_token_id', $token->id)->first();
        if (! $physicalToken || $physicalToken->reference !== $request->physical_token_reference) {
            return response()->json(['message' => 'The matching physical token must be returned before this service can be completed.'], 422);
        }

        return DB::transaction(function () use ($request, $token, $physicalToken) {
            $documentRequest = $token->documentRequest;
            if ($request->action === 'pay_and_process') {
                if ($token->service_stage !== 'cashier') {
                    return response()->json(['message' => 'Payment processing is only available at the cashier stage.'], 422);
                }
                $documentRequest->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                    'remarks' => 'Payment received. Document is being processed.',
                ]);
                $token->update(['status' => 'hold', 'completed_at' => now()]);
            } else {
                if ($token->service_stage !== 'registrar' || $documentRequest->payment_status !== 'paid') {
                    return response()->json(['message' => 'The document is not eligible for release.'], 422);
                }
                $documentRequest->update([
                    'status' => 'released',
                    'released_at' => now(),
                    'remarks' => 'Document released to student.',
                ]);
                $token->update(['status' => 'completed', 'completed_at' => now()]);
            }

            $physicalToken->update([
                'status' => 'available',
                'assigned_student_id' => null,
                'assigned_queue_token_id' => null,
                'last_reset_by_staff_id' => $request->user()->id,
                'last_reset_at' => now(),
            ]);
            $this->recordEvent($physicalToken, 'returned', $token->student_id, $token->id, $request->user()->id);
            $this->recordEvent($physicalToken, 'reset', staffId: $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => $request->action === 'pay_and_process'
                    ? "Payment for {$token->token_number} confirmed. Document is now in processing."
                    : "Document for {$token->token_number} has been released.",
                'token' => $token->fresh()->load('documentRequest'),
            ]);
        });
    }

    public function markPhysicalTokenMissing(Request $request, PhysicalQueueToken $physicalToken)
    {
        $this->authorizeStaffStage($request, $physicalToken->service_stage);
        $physicalToken->update(['status' => 'missing']);
        $this->recordEvent($physicalToken, 'missing', $physicalToken->assigned_student_id, $physicalToken->assigned_queue_token_id, $request->user()->id);

        return response()->json(['success' => true, 'message' => 'Physical token marked missing and blocked from further use.']);
    }

    public function physicalTokens(Request $request)
    {
        $validated = $request->validate([
            'service_stage' => 'required|in:cashier,registrar',
        ]);
        $stage = $validated['service_stage'];
        $this->authorizeStaffStage($request, $stage);

        $tokens = PhysicalQueueToken::with([
            'assignedStudent.user',
            'assignedQueueTicket.documentRequest',
            'lastResetByStaff:id,name',
        ])
            ->where('service_stage', $stage)
            ->orderBy('id')
            ->get();

        return response()->json(['success' => true, 'stage' => $stage, 'tokens' => $tokens]);
    }

    public function physicalTokenHistory(Request $request, PhysicalQueueToken $physicalToken)
    {
        $this->authorizeStaffStage($request, $physicalToken->service_stage);

        return response()->json([
            'success' => true,
            'token' => $physicalToken->load([
                'assignedStudent.user',
                'assignedQueueTicket.documentRequest',
                'events' => fn ($query) => $query->latest()->with([
                    'student.user',
                    'staff:id,name',
                    'queueTicket.documentRequest',
                ]),
                'attempts' => fn ($query) => $query->latest()->with([
                    'student.user',
                    'queueTicket.documentRequest',
                ]),
            ]),
        ]);
    }

    public function physicalTokenQr(Request $request, PhysicalQueueToken $physicalToken)
    {
        $this->authorizeStaffStage($request, $physicalToken->service_stage);
        if (! $physicalToken->qr_secret_encrypted) {
            return response()->json([
                'message' => 'This token QR cannot be reprinted because its original secret is not recoverable. Replace the token through an administrator.',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'reference' => $physicalToken->reference,
            'service_stage' => $physicalToken->service_stage,
            'qr_secret' => $physicalToken->qr_secret_encrypted,
        ]);
    }

    public function resetPhysicalToken(Request $request, PhysicalQueueToken $physicalToken)
    {
        $this->authorizeStaffStage($request, $physicalToken->service_stage);
        $activeTicket = $physicalToken->assignedQueueTicket()
            ->whereIn('status', ['waiting', 'serving'])
            ->exists();
        if ($activeTicket) {
            return response()->json([
                'message' => 'This token is still linked to an active queue ticket. Complete or resolve that ticket before resetting the physical token.',
            ], 422);
        }

        $previousStudentId = $physicalToken->assigned_student_id;
        $previousQueueTokenId = $physicalToken->assigned_queue_token_id;
        $physicalToken->update([
            'status' => 'available',
            'assigned_student_id' => null,
            'assigned_queue_token_id' => null,
            'last_reset_by_staff_id' => $request->user()->id,
            'last_reset_at' => now(),
        ]);
        $this->recordEvent(
            $physicalToken,
            'reset',
            $previousStudentId,
            $previousQueueTokenId,
            $request->user()->id,
            'Physical token verified by staff.',
        );

        return response()->json(['success' => true, 'message' => 'Physical token has been reset and is available for use.']);
    }

    private function authorizeStaffStage(Request $request, string $stage): void
    {
        $allowedRoles = $stage === 'cashier' ? ['cashier', 'admin'] : ['registrar', 'admin'];
        abort_unless(in_array($request->user()->role, $allowedRoles, true), 403, 'You are not authorized to manage this queue.');
    }

    /**
     * Staff action: Mark a paid document ready for pickup. The student must
     * later activate a Registrar physical token; this action never queues them.
     */
    public function markReadyForPickup(Request $request, $id)
    {
        $token = QueueToken::with('documentRequest')->findOrFail($id);
        $this->authorizeStaffStage($request, 'registrar');
        if ($token->status !== 'hold' || $token->service_stage !== 'cashier' || $token->documentRequest->payment_status !== 'paid') {
            return response()->json(['message' => 'Only paid documents in processing can be marked ready for pickup.'], 422);
        }

        $token->documentRequest->update([
            'status' => 'ready_for_pickup',
            'remarks' => 'Document ready for pickup. Check in at the Registrar queue when you return.',
        ]);
        $token->update(['status' => 'completed', 'completed_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Document ready for pickup. The student must check in at the Registrar queue when they return.',
            'token' => $token->fresh()->load(['student.user', 'documentRequest']),
        ]);
    }

    /**
     * Public: Get the live lobby display data for both cashier and registrar queues.
     * No authentication required — intended for lobby TV monitors.
     */
    public function lobbyDisplay()
    {
        $today = now()->toDateString();

        $cashierServing = QueueToken::query()
            ->select(['id', 'token_number', 'service_stage', 'window_number', 'status', 'called_at'])
            ->where('service_stage', 'cashier')
            ->where('status', 'serving')
            ->whereDate('queue_date', $today)
            ->orderBy('called_at', 'desc')
            ->first();

        $cashierUpcoming = QueueToken::query()
            ->select(['id', 'token_number', 'service_stage', 'status'])
            ->where('service_stage', 'cashier')
            ->whereDate('queue_date', $today)
            ->orderBy('daily_sequence')
            ->limit(5)
            ->get();

        $registrarServing = QueueToken::query()
            ->select(['id', 'token_number', 'service_stage', 'window_number', 'status', 'called_at'])
            ->where('service_stage', 'registrar')
            ->where('status', 'serving')
            ->whereDate('queue_date', $today)
            ->orderBy('called_at', 'desc')
            ->first();

        $registrarUpcoming = QueueToken::query()
            ->select(['id', 'token_number', 'service_stage', 'status'])
            ->where('service_stage', 'registrar')
            ->whereDate('queue_date', $today)
            ->orderBy('daily_sequence')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'date' => $today,
            'cashier' => [
                'now_serving' => $cashierServing,
                'upcoming' => $cashierUpcoming,
                'waiting_count' => QueueToken::where('service_stage', 'cashier')
                    ->where('status', 'waiting')
                    ->whereDate('queue_date', $today)
                    ->count(),
            ],
            'registrar' => [
                'now_serving' => $registrarServing,
                'upcoming' => $registrarUpcoming,
                'waiting_count' => QueueToken::where('service_stage', 'registrar')
                    ->where('status', 'waiting')
                    ->whereDate('queue_date', $today)
                    ->count(),
            ],
        ]);
    }

    /**
     * Authenticated: Check queue status for the signed-in student only.
     */
    public function authenticatedQueueStatus(Request $request)
    {
        $student = $request->user()->student;
        if ($request->user()->role !== 'student' || ! $student || $student->status !== 'active') {
            return response()->json(['message' => 'An active student account is required for queue status.'], 403);
        }

        $today = now()->toDateString();
        $tokens = QueueToken::with(['documentRequest', 'physicalQueueToken'])
            ->where('student_id', $student->id)
            ->whereDate('queue_date', $today)
            ->orderBy('daily_sequence')
            ->get();

        $activeToken = $tokens->first(fn (QueueToken $token) => ! in_array($token->status, ['completed', 'no_show', 'skipped'], true)
            && ! ($token->status === 'hold' && in_array($token->documentRequest?->status, ['ready_for_pickup', 'released'], true)));

        $position = null;
        if ($activeToken && $activeToken->status === 'waiting') {
            $position = QueueToken::where('service_stage', $activeToken->service_stage)
                ->where('status', 'waiting')
                ->whereDate('queue_date', $today)
                ->where('daily_sequence', '<=', $activeToken->daily_sequence)
                ->count();
        }

        return response()->json([
            'success' => true,
            'student_name' => $student->name,
            'student_number' => $student->student_number,
            'tokens' => $tokens,
            'active_token' => $activeToken,
            'position_in_queue' => $position,
        ]);
    }

    private static function normalizeStudentNumber(string $studentNumber): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $studentNumber) ?? '');
    }

    private function canManageStage(Request $request, string $stage): bool
    {
        $role = $request->user()->role;

        return $role === 'admin'
            || ($stage === 'cashier' && $role === 'cashier')
            || ($stage === 'registrar' && $role === 'registrar');
    }

    /**
     * Staff: Get the full queue list for a given stage (for the staff console).
     */
    public function staffQueue(Request $request)
    {
        $request->validate([
            'service_stage' => 'required|in:cashier,registrar',
        ]);

        $role = $request->user()->role;
        if (! in_array($role, ['cashier', 'registrar', 'admin'], true)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $today = now()->toDateString();
        $stage = $request->service_stage;

        $serving = QueueToken::with(['student.user', 'documentRequest', 'physicalQueueToken'])
            ->where('service_stage', $stage)
            ->where('status', 'serving')
            ->whereDate('queue_date', $today)
            ->get();

        $waiting = QueueToken::with(['student.user', 'documentRequest', 'physicalQueueToken'])
            ->where('service_stage', $stage)
            ->where('status', 'waiting')
            ->whereDate('queue_date', $today)
            ->orderBy('daily_sequence')
            ->get();

        $onHold = QueueToken::with(['student.user', 'documentRequest', 'physicalQueueToken'])
            ->where('service_stage', $stage)
            ->where('status', 'hold')
            ->whereDate('queue_date', $today)
            ->get();

        $completed = QueueToken::with(['student.user', 'documentRequest', 'physicalQueueToken'])
            ->where('service_stage', $stage)
            ->whereIn('status', ['completed', 'no_show', 'skipped'])
            ->whereDate('queue_date', $today)
            ->orderBy('completed_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'stage' => $stage,
            'date' => $today,
            'serving' => $serving,
            'waiting' => $waiting,
            'on_hold' => $onHold,
            'completed' => $completed,
            'stats' => [
                'total_today' => QueueToken::where('service_stage', $stage)->whereDate('queue_date', $today)->count(),
                'served' => $completed->where('status', 'completed')->count(),
                'waiting_count' => $waiting->count(),
                'no_shows' => $completed->where('status', 'no_show')->count(),
            ],
        ]);
    }
}
