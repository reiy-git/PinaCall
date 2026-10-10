<?php

namespace App\Http\Controllers;

use App\Models\Clearance;
use App\Models\Course;
use App\Models\DocumentRequest;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\StudentApplication;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        if (!in_array($request->user()->role, ['admin', 'registrar'], true)) {
            return response()->json(['message' => 'You are not authorized to view institution-wide statistics.'], 403);
        }

        $totalStudents = Student::count();
        $totalUsers = User::count();
        $totalCourses = Course::count();
        $pendingApplications = StudentApplication::where('status', 'pending')->count();
        $approvedApplications = StudentApplication::where('status', 'approved')->count();
        $totalClearances = Clearance::count();
        $clearedCount = Clearance::where('status', 'cleared')->count();
        $pendingClearance = Clearance::where('status', 'pending')->count();
        $pendingDocuments = DocumentRequest::whereIn('status', ['submitted', 'processing'])->count();
        $releasedDocuments = DocumentRequest::where('status', 'released')->count();

        $studentTypeBreakdown = [
            'new' => Student::where('student_type', 'new')->count(),
            'transferee' => Student::where('student_type', 'transferee')->count(),
            'irregular' => Student::where('student_type', 'irregular')->count(),
            'old' => Student::where('student_type', 'old')->count(),
            'shiftee' => Student::where('student_type', 'shiftee')->count(),
            'returnee' => Student::where('student_type', 'returnee')->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => [
                'total_students' => $totalStudents,
                'total_users' => $totalUsers,
                'total_courses' => $totalCourses,
                'pending_applications' => $pendingApplications,
                'approved_applications' => $approvedApplications,
                'total_clearances' => $totalClearances,
                'cleared_students' => $clearedCount,
                'pending_clearances' => $pendingClearance,
                'pending_documents' => $pendingDocuments,
                'released_documents' => $releasedDocuments,
                'student_types' => $studentTypeBreakdown,
            ],
        ]);
    }
}
