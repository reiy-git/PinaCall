<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequest;
use App\Models\QueueToken;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = DocumentRequest::with(['student.user']);

        if ($request->user()->role === 'student') {
            if (!$request->user()->student) return response()->json(['message' => 'No student profile is attached to this account.'], 403);
            $query->where('student_id', $request->user()->student->id);
        } elseif (!in_array($request->user()->role, ['registrar', 'cashier', 'admin'], true)) {
            return response()->json(['message' => 'You are not authorized to view document requests.'], 403);
        } elseif ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status') && $request->payment_status !== 'all') {
            $query->where('payment_status', $request->payment_status);
        }

        $requests = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'requests' => $requests,
        ]);
    }

    public function store(Request $request)
    {
        if ($request->user()->role !== 'student' || !$request->user()->student) {
            return response()->json(['message' => 'Only students can submit document requests.'], 403);
        }
        $request->validate([
            'student_id' => 'sometimes|integer|exists:students,id',
            'document_type' => 'required|string',
            'purpose' => 'required|string',
            'copies' => 'required|integer|min:1|max:10',
        ]);
        if ($request->filled('student_id') && (int) $request->student_id !== (int) $request->user()->student->id) {
            return response()->json(['message' => 'You can only request documents for your own student record.'], 403);
        }

        $feeTable = [
            'Transcript of Records (TOR)' => 150.00,
            'Certificate of Good Moral Character' => 150.00,
            'Certificate of Grades (COG)' => 100.00,
            'Certificate of Enrollment' => 100.00,
            'Honorable Dismissal' => 250.00,
            'Course Description & Syllabus' => 200.00,
        ];

        $unitFee = $feeTable[$request->document_type] ?? 150.00;
        $totalFee = $unitFee * $request->copies;

        $docRequest = DocumentRequest::create([
            'student_id' => $request->user()->student->id,
            'type' => $request->document_type,
            'document_type' => $request->document_type,
            'purpose' => $request->purpose,
            'copies' => $request->copies,
            'fee' => $totalFee,
            'payment_status' => 'unpaid',
            'status' => 'waiting_for_payment',
            'remarks' => 'Request submitted. Proceed to campus kiosk to check in and enter the digital queue.',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Official document request submitted successfully.',
            'request' => $docRequest->load('student.user'),
        ], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $role = $request->user()->role;
        if (!in_array($role, ['cashier', 'registrar', 'admin'], true)) {
            return response()->json(['message' => 'You are not authorized to update document requests.'], 403);
        }
        $request->validate([
            'status' => 'nullable|in:waiting_for_payment,submitted,processing,ready_for_pickup,released,rejected',
            'payment_status' => 'nullable|in:unpaid,paid',
            'remarks' => 'nullable|string',
        ]);
        if ($role === 'cashier' && ($request->has('status') || !$request->has('payment_status'))) {
            return response()->json(['message' => 'Cashier accounts may update payment status only.'], 403);
        }
        if ($role === 'registrar' && $request->has('payment_status')) {
            return response()->json(['message' => 'Only cashier accounts may update document payment status.'], 403);
        }

        $docRequest = DocumentRequest::with('student.user')->findOrFail($id);

        if ($request->has('payment_status')) {
            if ($docRequest->payment_status === 'paid' && $request->payment_status !== 'paid') {
                return response()->json(['message' => 'A settled document fee cannot be reversed through the standard cashier workflow.'], 422);
            }
            $docRequest->payment_status = $request->payment_status;
            if ($request->payment_status === 'paid' && in_array($docRequest->status, ['submitted', 'waiting_for_payment'], true)) {
                $docRequest->status = 'processing';
            }
        }

        if ($request->has('status')) {
            $allowedTransitions = [
                'waiting_for_payment' => ['submitted', 'rejected'],
                'submitted' => ['processing', 'rejected'],
                'processing' => ['ready_for_pickup', 'rejected'],
                'ready_for_pickup' => ['released'],
                'released' => [],
                'rejected' => [],
            ];
            if (!in_array($request->status, $allowedTransitions[$docRequest->status] ?? [], true)) {
                return response()->json(['message' => 'That document request status transition is not allowed.'], 422);
            }
            if (in_array($request->status, ['processing', 'ready_for_pickup', 'released'], true)
                && $docRequest->payment_status !== 'paid'
                && $request->payment_status !== 'paid') {
                return response()->json(['message' => 'The document fee must be settled before processing or release.'], 422);
            }
            $docRequest->status = $request->status;
            if ($request->status === 'released') {
                $docRequest->released_at = now();
            }
        }

        if ($request->has('remarks')) {
            $docRequest->remarks = $request->remarks;
        }

        DB::transaction(function () use ($docRequest, $request) {
            $docRequest->save();

            if ($request->status === 'ready_for_pickup') {
                $this->ensureRegistrarToken($docRequest);
            }

            if ($request->status === 'released') {
                QueueToken::where('document_request_id', $docRequest->id)
                    ->where('service_stage', 'registrar')
                    ->whereDate('queue_date', now()->toDateString())
                    ->whereIn('status', ['waiting', 'serving'])
                    ->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Document request updated successfully.',
            'request' => $docRequest,
        ]);
    }

    private function ensureRegistrarToken(DocumentRequest $docRequest): QueueToken
    {
        $existingToken = QueueToken::where('document_request_id', $docRequest->id)
            ->where('service_stage', 'registrar')
            ->whereDate('queue_date', now()->toDateString())
            ->whereNotIn('status', ['completed', 'no_show', 'skipped'])
            ->first();

        if ($existingToken) {
            return $existingToken;
        }

        $sequence = QueueToken::nextSequence('registrar');

        return QueueToken::create([
            'token_number' => QueueToken::generateTokenNumber('registrar', $sequence),
            'daily_sequence' => $sequence,
            'student_id' => $docRequest->student_id,
            'document_request_id' => $docRequest->id,
            'service_stage' => 'registrar',
            'status' => 'waiting',
            'queue_date' => now()->toDateString(),
            'checked_in_at' => now(),
        ]);
    }
}
