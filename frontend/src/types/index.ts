export type UserRole = 'student' | 'registrar' | 'cashier' | 'department_head' | 'faculty' | 'admin';
export type StudentType = 'new' | 'transferee' | 'irregular' | 'old' | 'shiftee' | 'returnee';
export type ApplicationStatus = 'pending' | 'approved' | 'rejected';
export type ClearanceStatus = 'pending' | 'cleared';
export type DocRequestStatus = 'submitted' | 'processing' | 'ready_for_pickup' | 'released' | 'rejected';

export interface User {
  must_change_password: boolean;
  id: number;
  name: string;
  email: string;
  role: UserRole;
  department?: string | null;
  is_active?: boolean;
  student?: Student;
  applications?: StudentApplication[];
}

export interface Student {
  id: number;
  user_id: number;
  name: string;
  email: string;
  student_number: string;
  course_program: string;
  year_level: number;
  student_type: StudentType;
  status: 'active' | 'pending' | 'graduated' | 'rejected';
  clearance?: Clearance;
  enrollments?: Enrollment[];
  document_requests?: DocumentRequest[];
}

export interface Course {
  id: number;
  code: string;
  title: string;
  description?: string;
  units: number;
  department: string;
  semester: string;
  year_level: number;
  schedule?: string;
  instructor?: string;
}

export interface AcademicSection {
  id: number;
  course_id: number;
  faculty_id: number | null;
  section_code: string;
  program: string;
  year_level: number;
  academic_year: string;
  semester: string;
  schedule?: string | null;
  room?: string | null;
  capacity: number;
  status: 'draft' | 'open' | 'closed';
  enrolled_count?: number;
  course?: Course;
  faculty?: Pick<User, 'id' | 'name' | 'email'> | null;
  students?: Array<Student & { user?: Pick<User, 'id' | 'name' | 'email'>; pivot?: { status: string; enrolled_at: string } }>;
}

export interface Enrollment {
  id: number;
  student_id: number;
  course_id: number;
  academic_section_id?: number | null;
  academic_year: string;
  semester: string;
  midterm_grade?: number;
  final_grade?: number;
  grade?: string;
  status: string;
  grades_released?: boolean;
  remarks?: string;
  student?: {
    id: number;
    name: string;
    student_number: string;
    user?: { name: string; email: string };
  };
  course?: Course;
  section?: AcademicSection;
}

export interface Clearance {
  id: number;
  student_id: number;
  academic_year: string;
  semester: string;
  cashier_status: boolean;
  cashier_remarks?: string;
  library_status: boolean;
  library_remarks?: string;
  dept_status: boolean;
  dept_remarks?: string;
  dean_status: boolean;
  dean_remarks?: string;
  guidance_status: boolean;
  guidance_remarks?: string;
  registrar_status: boolean;
  registrar_remarks?: string;
  status: ClearanceStatus;
  student?: {
    id: number;
    name: string;
    student_number: string;
    course_program: string;
    year_level: number;
    student_type?: StudentType;
    user?: { name: string; email: string };
  };
  enrollments?: Enrollment[];
}

export interface DocumentRequest {
  id: number;
  student_id: number;
  type: string;
  document_type: string;
  purpose: string;
  copies: number;
  fee: number;
  payment_status: 'unpaid' | 'paid';
  status: DocRequestStatus;
  remarks?: string;
  released_at?: string;
  created_at?: string;
  student?: {
    id: number;
    name: string;
    student_number: string;
    user?: { name: string; email: string };
  };
}

export interface StudentApplication {
  id: number;
  user_id: number;
  student_id?: number;
  type: StudentType;
  student_type: StudentType;
  target_program: string;
  year_level: number;
  semester: string;
  academic_year: string;
  educational_background: string;
  previous_school?: string;
  contact_number?: string;
  address?: string;
  emergency_contact?: string;
  submitted_documents?: string[];
  status: ApplicationStatus;
  evaluation_notes?: string;
  reviewed_by?: string;
  assigned_section?: string;
  assigned_section_id?: number;
  created_at?: string;
  user?: { id: number; name: string; email: string };
  student?: Student;
}

export interface DashboardStats {
  total_students: number;
  total_users: number;
  total_courses: number;
  pending_applications: number;
  approved_applications: number;
  total_clearances: number;
  cleared_students: number;
  pending_clearances: number;
  pending_documents: number;
  released_documents: number;
  student_types: Record<StudentType, number>;
}
