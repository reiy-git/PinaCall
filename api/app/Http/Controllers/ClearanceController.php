<?php

namespace App\Http\Controllers;

use App\Models\Clearance;
use App\Models\Student;
use Illuminate\Http\Request;

class ClearanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Clearance::with(['student.user']);

        if ($user->role === 'student') {
            if (!$user->student) return response()->json(['message' => 'No student profile is attached to this account.'], 403);
            $query->where('student_id', $user->student->id);
        } elseif (!in_array($user->role, ['cashier', 'department_head', 'registrar', 'admin'], true)) {
            return response()->json(['message' => 'You are not authorized to view clearance records.'], 403);
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $clearances = $query->orderBy('updated_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'clearances' => $clearances,
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['student', 'cashier', 'department_head', 'registrar', 'admin'], true)) {
            return response()->json(['message' => 'You are not authorized to view clearance records.'], 403);
        }

        $clearance = Clearance::with(['student.user', 'student.enrollments.course'])->findOrFail($id);
        if ($user->role === 'student' && $clearance->student?->user_id !== $user->id) {
            return response()->json(['message' => 'You can only view your own clearance record.'], 403);
        }
        return response()->json([
            'success' => true,
            'clearance' => $clearance,
        ]);
    }

    public function requestClearance(Request $request)
    {
        if ($request->user()->role !== 'student' || !$request->user()->student) {
            return response()->json(['message' => 'Only students can initiate their own clearance request.'], 403);
        }
        $request->validate([
            'student_id' => 'sometimes|integer|exists:students,id',
            'academic_year' => 'nullable|string',
            'semester' => 'nullable|string',
        ]);
        if ($request->filled('student_id') && (int) $request->student_id !== (int) $request->user()->student->id) {
            return response()->json(['message' => 'You can only request clearance for your own student record.'], 403);
        }

        $clearance = Clearance::firstOrCreate(
            ['student_id' => $request->user()->student->id],
            [
                'academic_year' => $request->academic_year ?? '2026-2027',
                'semester' => $request->semester ?? '1st Semester',
                'cashier_status' => false,
                'cashier_remarks' => 'Assessment awaiting settlement',
                'library_status' => false,
                'library_remarks' => 'Checking library account',
                'dept_status' => false,
                'dept_remarks' => 'Awaiting department evaluation',
                'dean_status' => false,
                'dean_remarks' => 'Pending Dean review',
                'guidance_status' => true,
                'guidance_remarks' => 'Guidance requirement cleared',
                'registrar_status' => false,
                'registrar_remarks' => 'Pending other department sign-offs',
                'status' => 'pending',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Clearance application initiated.',
            'clearance' => $clearance->load('student.user'),
        ]);
    }

    public function updateStep(Request $request, $id)
    {
        $role = $request->user()->role;
        $request->validate([
            'department' => 'required|in:cashier,library,dept,dean,guidance,registrar',
            'approved' => 'required|boolean',
            'remarks' => 'nullable|string',
        ]);

        $allowedDepartments = match ($role) {
            'admin' => ['cashier', 'library', 'dept', 'dean', 'guidance', 'registrar'],
            'cashier' => ['cashier'],
            'department_head' => ['dept', 'dean'],
            'registrar' => ['registrar'],
            default => [],
        };
        if (!in_array($request->department, $allowedDepartments, true)) {
            return response()->json(['message' => 'Your role cannot update this clearance step.'], 403);
        }

        $clearance = Clearance::with('student.user')->findOrFail($id);
        $field = "{$request->department}_status";
        $remarksField = "{$request->department}_remarks";

        $clearance->$field = $request->approved;
        if ($request->has('remarks')) {
            $clearance->$remarksField = $request->remarks;
        }

        // Check if all are cleared
        $allCleared = $clearance->cashier_status &&
                      $clearance->library_status &&
                      $clearance->dept_status &&
                      $clearance->dean_status &&
                      $clearance->guidance_status &&
                      $clearance->registrar_status;

        $clearance->status = $allCleared ? 'cleared' : 'pending';
        $clearance->save();

        return response()->json([
            'success' => true,
            'message' => ucfirst($request->department) . ' clearance updated successfully.',
            'clearance' => $clearance,
        ]);
    }
}
