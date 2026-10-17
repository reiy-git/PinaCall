<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequest;
use App\Models\QueueToken;
use App\Models\Student;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    /**
     * Kiosk Check-In: Student enters their Student ID at the physical kiosk.
     * Validates an active document request in 'waiting_for_payment' status,
     * generates a sequential queue token, and activates it.
     */
    public function checkin(Request $request)
    {
        $request->validate([
            'student_number' => 'required|string',
        ]);

        $student = Student::where('student_number', $request->student_number)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student ID not found. Please verify your Student ID and try again.',
            ], 404);
        }

        // Check for existing active token today
        $existingToken = QueueToken::where('student_id', $student->id)
            ->whereDate('queue_date', now()->toDateString())
            ->whereNotIn('status', ['completed', 'no_show', 'skipped'])
            ->first();

        if ($existingToken) {
            return response()->json([
                'success' => true,
                'message' => 'You already have an active queue token for today.',
                'token' => $existingToken->load('documentRequest'),
                'already_checked_in' => true,
            ]);
        }

        // Find an active document request in waiting_for_payment status
        $docRequest = DocumentRequest::where('student_id', $student->id)
            ->where('status', 'waiting_for_payment')
            ->where('payment_status', 'unpaid')
            ->oldest()
            ->first();

        if (!$docRequest) {
            return response()->json([
                'success' => false,
                'message' => 'No active request found. You must submit a document request through the PINACALL portal before checking in.',
            ], 422);
        }

        // Generate sequential queue token
        $sequence = QueueToken::nextSequence('cashier');
        $tokenNumber = QueueToken::generateTokenNumber('cashier', $sequence);

        $token = QueueToken::create([
            'token_number' => $tokenNumber,
            'daily_sequence' => $sequence,
            'student_id' => $student->id,
            'document_request_id' => $docRequest->id,
            'service_stage' => 'cashier',
            'status' => 'waiting',
            'queue_date' => now()->toDateString(),
            'checked_in_at' => now(),
        ]);

        // Transition document request status
        $docRequest->update(['status' => 'submitted']);

        return response()->json([
            'success' => true,
            'message' => "Check-in successful! Your queue number is {$tokenNumber}. Please wait for your number to be called.",
            'token' => $token->load('documentRequest'),
        ], 201);
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

        if ($stage === 'cashier' && !in_array($role, ['cashier', 'admin'], true)) {
            return response()->json(['message' => 'Only cashier staff can manage the cashier queue.'], 403);
        }
        if ($stage === 'registrar' && !in_array($role, ['registrar', 'admin'], true)) {
            return response()->json(['message' => 'Only registrar staff can manage the registrar queue.'], 403);
        }

        // Get the next waiting token for this stage
        $token = QueueToken::where('service_stage', $stage)
            ->where('status', 'waiting')
            ->whereDate('queue_date', now()->toDateString())
            ->orderBy('daily_sequence')
            ->first();

        if (!$token) {
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

        return response()->json([
            'success' => true,
            'message' => "Now serving {$token->token_number} at {$request->window_number}.",
            'token' => $token->load(['student.user', 'documentRequest']),
        ]);
    }

    /**
     * Staff action: Re-broadcast the current serving token to displays.
     */
    public function recallDisplay(Request $request, $id)
    {
        $token = QueueToken::with(['student.user', 'documentRequest'])->findOrFail($id);

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
        ]);

        $token = QueueToken::with('documentRequest')->findOrFail($id);

        if ($token->status !== 'serving') {
            return response()->json(['message' => 'Only a currently serving token can be completed.'], 422);
        }

        $docRequest = $token->documentRequest;

        if ($request->action === 'pay_and_process') {
            // Cashier stage: mark payment as paid, move document to processing
            if ($token->service_stage !== 'cashier') {
                return response()->json(['message' => 'Payment processing is only available at the cashier stage.'], 422);
            }

            $docRequest->update([
                'payment_status' => 'paid',
                'status' => 'processing',
                'remarks' => 'Payment received. Document is being processed.',
            ]);

            $token->update([
                'status' => 'hold',
                'completed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Payment for {$token->token_number} confirmed. Document is now in processing.",
                'token' => $token->fresh()->load('documentRequest'),
            ]);
        }

        if ($request->action === 'release_document') {
            // Registrar stage: release the document
            if ($token->service_stage !== 'registrar') {
                return response()->json(['message' => 'Document release is only available at the registrar stage.'], 422);
            }
            if ($docRequest->payment_status !== 'paid') {
                return response()->json(['message' => 'Payment must be confirmed before document release.'], 422);
            }

            $docRequest->update([
                'status' => 'released',
                'released_at' => now(),
                'remarks' => 'Document released to student.',
            ]);

            $token->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Document for {$token->token_number} has been released.",
                'token' => $token->fresh()->load('documentRequest'),
            ]);
        }
    }

    /**
     * Staff action: Mark a document as ready for pickup.
     * This transitions the held token into the registrar queue.
     */
    public function markReadyForPickup(Request $request, $id)
    {
        $token = QueueToken::with('documentRequest')->findOrFail($id);

        if ($token->status !== 'hold' || $token->service_stage !== 'cashier') {
            return response()->json(['message' => 'Only held cashier tokens can be moved to the registrar queue.'], 422);
        }

        $docRequest = $token->documentRequest;
        $docRequest->update([
            'status' => 'ready_for_pickup',
            'remarks' => 'Document ready for pickup. Please proceed to the Registrar window.',
        ]);

        // Create a new registrar queue token
        $sequence = QueueToken::nextSequence('registrar');
        $regTokenNumber = QueueToken::generateTokenNumber('registrar', $sequence);

        $regToken = QueueToken::create([
            'token_number' => $regTokenNumber,
            'daily_sequence' => $sequence,
            'student_id' => $token->student_id,
            'document_request_id' => $token->document_request_id,
            'service_stage' => 'registrar',
            'status' => 'waiting',
            'queue_date' => now()->toDateString(),
            'checked_in_at' => now(),
        ]);

        // Mark original cashier token as completed
        $token->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Document ready for pickup. Registrar token {$regTokenNumber} issued.",
            'token' => $regToken->load(['student.user', 'documentRequest']),
        ]);
    }

    /**
     * Public: Get the live lobby display data for both cashier and registrar queues.
     * No authentication required — intended for lobby TV monitors.
     */
    public function lobbyDisplay()
    {
        $today = now()->toDateString();

        $cashierServing = QueueToken::with(['student.user', 'documentRequest'])
            ->where('service_stage', 'cashier')
            ->where('status', 'serving')
            ->whereDate('queue_date', $today)
            ->orderBy('called_at', 'desc')
            ->first();

        $cashierUpcoming = QueueToken::where('service_stage', 'cashier')
            ->where('status', 'waiting')
            ->whereDate('queue_date', $today)
            ->orderBy('daily_sequence')
            ->limit(5)
            ->get();

        $registrarServing = QueueToken::with(['student.user', 'documentRequest'])
            ->where('service_stage', 'registrar')
            ->where('status', 'serving')
            ->whereDate('queue_date', $today)
            ->orderBy('called_at', 'desc')
            ->first();

        $registrarUpcoming = QueueToken::where('service_stage', 'registrar')
            ->where('status', 'waiting')
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
     * Public/Student: Check queue status by student number.
     */
    public function queueStatus(Request $request)
    {
        $request->validate([
            'student_number' => 'required|string',
        ]);

        $student = Student::where('student_number', $request->student_number)->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student ID not found.'], 404);
        }

        $today = now()->toDateString();
        $tokens = QueueToken::with('documentRequest')
            ->where('student_id', $student->id)
            ->whereDate('queue_date', $today)
            ->orderBy('daily_sequence')
            ->get();

        $activeToken = $tokens->whereNotIn('status', ['completed', 'no_show', 'skipped'])->first();

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

    /**
     * Staff: Get the full queue list for a given stage (for the staff console).
     */
    public function staffQueue(Request $request)
    {
        $request->validate([
            'service_stage' => 'required|in:cashier,registrar',
        ]);

        $role = $request->user()->role;
        if (!in_array($role, ['cashier', 'registrar', 'admin'], true)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $today = now()->toDateString();
        $stage = $request->service_stage;

        $serving = QueueToken::with(['student.user', 'documentRequest'])
            ->where('service_stage', $stage)
            ->where('status', 'serving')
            ->whereDate('queue_date', $today)
            ->get();

        $waiting = QueueToken::with(['student.user', 'documentRequest'])
            ->where('service_stage', $stage)
            ->where('status', 'waiting')
            ->whereDate('queue_date', $today)
            ->orderBy('daily_sequence')
            ->get();

        $onHold = QueueToken::with(['student.user', 'documentRequest'])
            ->where('service_stage', $stage)
            ->where('status', 'hold')
            ->whereDate('queue_date', $today)
            ->get();

        $completed = QueueToken::with(['student.user', 'documentRequest'])
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
