import { reactive, ref } from 'vue';
import type {
  User,
  Course,
  Student,
  StudentApplication,
  Clearance,
  Enrollment,
  AcademicSection,
  DocumentRequest,
  DashboardStats,
} from '../types';
import { resetPortalPage } from './navigation';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';

export class ApiRequestError extends Error {
  readonly status: number;
  readonly errors: Record<string, string[]>;

  constructor(message: string, status: number, errors: Record<string, string[]> = {}) {
    super(message);
    this.status = status;
    this.errors = errors;
    this.name = 'ApiRequestError';
  }
}

// Reactive Store State
export const authState = reactive<{
  user: User | null;
  token: string | null;
  personas: User[];
  isLiveApi: boolean;
  isLoading: boolean;
  serviceUnavailable: boolean;
}>({
  user: null,
  token: sessionStorage.getItem('pinacall_access_token'),
  personas: [],
  isLiveApi: true,
  isLoading: false,
  serviceUnavailable: false,
});

// Toast notification state
export interface ToastMessage {
  id: number;
  type: 'success' | 'error' | 'info' | 'warning';
  title: string;
  message: string;
}

export const toasts = ref<ToastMessage[]>([]);

export function formatDisplayName(name: string | null | undefined): string {
  return name?.replace(/\s+\((?:New Student|Transferee|Irregular Student|Old \/ Regular Student|Shiftee Student|Returnee Student|University Registrar|Chief Cashier|CCS Department Head|Faculty)\)$/, '') ?? '';
}

export function showToast(type: 'success' | 'error' | 'info' | 'warning', title: string, message: string) {
  const id = Date.now() + Math.random();
  toasts.value.push({ id, type, title, message });
  setTimeout(() => {
    toasts.value = toasts.value.filter((t) => t.id !== id);
  }, 4000);
}

// Shared API fetch with explicit errors
async function apiFetch<T>(endpoint: string, options: RequestInit = {}, preserveView = false): Promise<T> {
  try {
    const res = await fetch(`${API_BASE_URL}${endpoint}`, {
      ...options,
      headers: {
        ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
        Accept: 'application/json',
        ...(authState.token ? { Authorization: `Bearer ${authState.token}` } : {}),
        ...(options.headers || {}),
      },
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      if (res.status === 422 && data.errors) {
        const firstError = Object.values(data.errors).flat()[0];
        throw new ApiRequestError(typeof firstError === 'string' ? firstError : data.message || 'Please check the information and try again.', res.status, data.errors);
      }
      if (data.code === 'PASSWORD_CHANGE_REQUIRED') window.location.assign('/set-password');
      throw new ApiRequestError(data.message || `Request failed (${res.status}).`, res.status);
    }
    authState.isLiveApi = true;
    authState.serviceUnavailable = false;
    return data;
  } catch (err) {
    authState.isLiveApi = false;
    if (!preserveView && (err instanceof TypeError || (err instanceof ApiRequestError && err.status >= 500))) authState.serviceUnavailable = true;
    console.warn(`PINACALL request to ${endpoint} failed:`, err);
    throw err;
  }
}

export const portalApi = {
  // Auth & Personas
  async loadPersonas(): Promise<User[]> {
    const res = await apiFetch<{ success: boolean; personas: User[] }>('/auth/personas');
    authState.personas = res.personas;
    return res.personas;
  },

  async restoreSession(): Promise<void> {
    if (!authState.token) return;
    try {
      const res = await apiFetch<{ success: boolean; user: User }>('/auth/me');
      authState.user = res.user;
      if (res.user.must_change_password) window.history.replaceState({}, '', '/set-password');
      resetPortalPage(res.user.role);
    } catch (error) {
      if (error instanceof TypeError || (error instanceof ApiRequestError && error.status >= 500)) {
        authState.serviceUnavailable = true;
        return;
      }
      authState.token = null;
      sessionStorage.removeItem('pinacall_access_token');
      authState.user = null;
    }
  },

  async login(email: string, password: string): Promise<User> {
    try {
      const res = await apiFetch<{ success: boolean; user: User; access_token: string }>('/auth/login', {
        method: 'POST',
        body: JSON.stringify({ email, password }),
      });
      authState.token = res.access_token;
      sessionStorage.setItem('pinacall_access_token', res.access_token);
      authState.user = res.user;
      if (res.user.must_change_password) window.history.replaceState({}, '', '/set-password');
      resetPortalPage(res.user.role);
      authState.isLiveApi = true;
      showToast('success', 'Logged In', `Welcome back, ${res.user.name}`);
      return res.user;
    } catch (error) {
      throw error instanceof Error ? error : new Error('Sign-in could not be completed.');
    }
  },

  async logout() {
    try {
      if (authState.token) await apiFetch('/auth/logout', { method: 'POST' });
    } catch (error) {
      console.warn('Unable to revoke the PINACALL session on the server:', error);
    }
    authState.token = null;
    sessionStorage.removeItem('pinacall_access_token');
    authState.user = null;
    authState.personas = [];
    resetPortalPage(null);
    showToast('info', 'Signed Out', 'You have been logged out of the portal.');
  },

  async getEnrollmentOptions(): Promise<{ programs: string[]; document_guidance: string }> {
    return apiFetch('/enrollees/options', {}, true);
  },
  async applyEnrollee(payload: FormData): Promise<{ reference: string }> {
    return apiFetch('/enrollees/apply', { method: 'POST', body: payload }, true);
  },
  async getEnrollees(status: string, page = 1): Promise<EnrolleePage> {
    return apiFetch(`/admin/enrollees?status=${encodeURIComponent(status)}&page=${page}`);
  },
  async decideEnrollee(id: number, decision: 'approve' | 'reject' | 'resend', remarks = '') {
    return apiFetch(`/admin/enrollees/${id}/${decision}`, { method: 'POST', body: JSON.stringify({ remarks }) });
  },
  async downloadEnrolleeDocument(id: number, index: number) {
    const response = await fetch(`${API_BASE_URL}/admin/enrollees/${id}/documents/${index}`, {
      headers: { Authorization: `Bearer ${authState.token}` },
    });
    if (!response.ok) throw new Error('Document download failed.');
    const url = URL.createObjectURL(await response.blob());
    const link = document.createElement('a'); link.href = url; link.download = 'application-document'; link.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  },
  async firstLoginReset(payload: { current_password: string; password: string; password_confirmation: string }) {
    await apiFetch('/auth/first-login-reset', { method: 'POST', body: JSON.stringify(payload) });
    authState.token = null; authState.user = null;
    sessionStorage.removeItem('pinacall_access_token');
    window.location.assign('/');
  },

  // Applications
  async getApplications(params: { student_type?: string; status?: string; search?: string } = {}): Promise<StudentApplication[]> {
    const searchParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined) searchParams.set(key, value);
    });
    const query = searchParams.toString();
    const res = await apiFetch<{ success: boolean; applications: StudentApplication[] }>(`/applications?${query}`);
    return res.applications;
  },

  async getStudents(): Promise<Student[]> {
    const res = await apiFetch<{ success: boolean; students: Student[] }>('/students');
    return res.students;
  },

  async submitApplication(payload: Partial<StudentApplication>): Promise<StudentApplication> {
    try {
      const res = await apiFetch<{ success: boolean; application: StudentApplication }>('/applications', {
        method: 'POST',
        body: JSON.stringify(payload),
      });
      showToast('success', 'Application Submitted', 'Your enrollment application has been logged into the review queue.');
      return res.application;
    } catch (err: any) {
      showToast('error', 'Submission Failed', err.message || 'Could not submit application');
      throw err;
    }
  },

  async reviewApplication(id: number, payload: { status: 'approved' | 'rejected' | 'pending'; evaluation_notes?: string; assigned_section_id?: number }): Promise<StudentApplication> {
    try {
      const res = await apiFetch<{ success: boolean; application: StudentApplication }>(`/applications/${id}/review`, {
        method: 'PATCH',
        body: JSON.stringify(payload),
      });
      showToast('success', 'Application Updated', `Application was marked as ${payload.status.toUpperCase()}`);
      return res.application;
    } catch (err: any) {
      showToast('error', 'Review Failed', err.message);
      throw err;
    }
  },

  // Clearances
  async getClearances(params: { status?: string; student_id?: number } = {}): Promise<Clearance[]> {
    const queryParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined) queryParams.set(key, String(value));
    });
    const query = queryParams.toString();
    const res = await apiFetch<{ success: boolean; clearances: Clearance[] }>(`/clearances?${query}`);
    return res.clearances;
  },

  async requestClearance(student_id: number): Promise<Clearance> {
    try {
      const res = await apiFetch<{ success: boolean; clearance: Clearance }>('/clearances/request', {
        method: 'POST',
        body: JSON.stringify({ student_id }),
      });
      showToast('success', 'Clearance Initiated', 'Clearance requests sent to Cashier, Library, and Department.');
      return res.clearance;
    } catch (err: any) {
      showToast('error', 'Request Failed', err.message);
      throw err;
    }
  },

  async updateClearanceStep(id: number, department: string, approved: boolean, remarks?: string): Promise<Clearance> {
    try {
      const res = await apiFetch<{ success: boolean; clearance: Clearance }>(`/clearances/${id}/step`, {
        method: 'PATCH',
        body: JSON.stringify({ department, approved, remarks }),
      });
      showToast('success', 'Clearance Signed', `${department.toUpperCase()} sign-off recorded.`);
      return res.clearance;
    } catch (err: any) {
      showToast('error', 'Update Failed', err.message);
      throw err;
    }
  },

  // Grades
  async getCourses(): Promise<Course[]> {
    const res = await apiFetch<{ success: boolean; courses: Course[] }>('/courses');
    return res.courses;
  },

  async createUser(payload: {
    name: string; email: string; password: string; role: User['role']; department?: string;
    course_program?: string; year_level?: number; student_type?: Student['student_type']; student_status?: Student['status'];
  }): Promise<User> {
    const res = await apiFetch<{ success: boolean; user: User }>('/admin/users', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.user;
  },

  async updateUser(id: number, payload: Partial<{
    name: string; email: string; password: string; role: User['role']; department: string | null;
    is_active: boolean; course_program: string; year_level: number; student_type: Student['student_type']; student_status: Student['status'];
  }>): Promise<User> {
    const res = await apiFetch<{ success: boolean; user: User }>(`/admin/users/${id}`, {
      method: 'PATCH',
      body: JSON.stringify(payload),
    });
    return res.user;
  },

  async createCourse(payload: Omit<Course, 'id'>): Promise<Course> {
    const res = await apiFetch<{ success: boolean; course: Course }>('/admin/courses', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.course;
  },

  async updateCourse(id: number, payload: Partial<Omit<Course, 'id'>>): Promise<Course> {
    const res = await apiFetch<{ success: boolean; course: Course }>(`/admin/courses/${id}`, {
      method: 'PATCH',
      body: JSON.stringify(payload),
    });
    return res.course;
  },

  async getFaculty(): Promise<Pick<User, 'id' | 'name' | 'email'>[]> {
    const res = await apiFetch<{ success: boolean; faculty: Pick<User, 'id' | 'name' | 'email'>[] }>('/faculty');
    return res.faculty;
  },

  async getSections(): Promise<AcademicSection[]> {
    const res = await apiFetch<{ success: boolean; sections: AcademicSection[] }>('/sections');
    return res.sections;
  },

  async createSection(payload: Omit<AcademicSection, 'id' | 'enrolled_count' | 'course' | 'faculty' | 'students'>): Promise<AcademicSection> {
    const res = await apiFetch<{ success: boolean; section: AcademicSection }>('/sections', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
    return res.section;
  },

  async updateSection(id: number, payload: Partial<Pick<AcademicSection, 'faculty_id' | 'section_code' | 'program' | 'year_level' | 'schedule' | 'room' | 'capacity' | 'status'>>): Promise<AcademicSection> {
    const res = await apiFetch<{ success: boolean; section: AcademicSection }>(`/sections/${id}`, {
      method: 'PATCH',
      body: JSON.stringify(payload),
    });
    return res.section;
  },

  async getSectionRoster(id: number): Promise<AcademicSection> {
    const res = await apiFetch<{ success: boolean; section: AcademicSection }>(`/sections/${id}/roster`);
    return res.section;
  },

  async enrollInSection(sectionId: number, studentId?: number): Promise<AcademicSection> {
    const res = await apiFetch<{ success: boolean; section: AcademicSection }>(`/sections/${sectionId}/enroll`, {
      method: 'POST',
      body: JSON.stringify(studentId === undefined ? {} : { student_id: studentId }),
    });
    return res.section;
  },

  async getGrades(student_id?: number): Promise<Enrollment[]> {
    const query = student_id ? `?student_id=${student_id}` : '';
    const res = await apiFetch<{ success: boolean; grades: Enrollment[] }>(`/grades${query}`);
    return res.grades;
  },

  async updateGrade(id: number, payload: { midterm_grade?: number; final_grade?: number; remarks?: string }): Promise<Enrollment> {
    try {
      const res = await apiFetch<{ success: boolean; enrollment: Enrollment }>(`/grades/${id}`, {
        method: 'PATCH',
        body: JSON.stringify(payload),
      });
      showToast('success', 'Grade Encoded', 'Academic record updated successfully.');
      return res.enrollment;
    } catch (err: any) {
      showToast('error', 'Grade Update Failed', err.message);
      throw err;
    }
  },

  async releaseGrade(id: number): Promise<Enrollment> {
    const res = await apiFetch<{ success: boolean; enrollment: Enrollment }>(`/grades/${id}/release`, {
      method: 'POST',
    });
    showToast('success', 'Grade released', 'The student can now view this academic result.');
    return res.enrollment;
  },

  // Documents
  async getDocuments(params: { student_id?: number; status?: string; payment_status?: string } = {}): Promise<DocumentRequest[]> {
    const queryParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined) queryParams.set(key, String(value));
    });
    const query = queryParams.toString();
    const res = await apiFetch<{ success: boolean; requests: DocumentRequest[] }>(`/documents?${query}`);
    return res.requests;
  },

  async requestDocument(payload: { student_id: number; document_type: string; purpose: string; copies: number }): Promise<DocumentRequest> {
    try {
      const res = await apiFetch<{ success: boolean; request: DocumentRequest }>('/documents/request', {
        method: 'POST',
        body: JSON.stringify(payload),
      });
      showToast('success', 'Document Requested', 'Request submitted to Registrar queue. Settle fee at Cashier.');
      return res.request;
    } catch (err: any) {
      showToast('error', 'Request Failed', err.message);
      throw err;
    }
  },

  async updateDocumentStatus(id: number, payload: { status?: string; payment_status?: string; remarks?: string }): Promise<DocumentRequest> {
    try {
      const res = await apiFetch<{ success: boolean; request: DocumentRequest }>(`/documents/${id}/status`, {
        method: 'PATCH',
        body: JSON.stringify(payload),
      });
      showToast('success', 'Document Status Updated', 'Request status transitioned.');
      return res.request;
    } catch (err: any) {
      showToast('error', 'Update Failed', err.message);
      throw err;
    }
  },

  // Dashboard Stats
  async getDashboardStats(): Promise<DashboardStats> {
    const res = await apiFetch<{ success: boolean; stats: DashboardStats }>('/dashboard/stats');
    return res.stats;
  },
};

export interface Enrollee {
  id: number; reference: string; name: string; email: string; contact_number: string;
  program: string; year_level: number; student_type: string; educational_background: string; previous_school?: string | null;
  status: 'PENDING' | 'APPROVED' | 'REJECTED'; remarks: string | null;
  documents: { name: string; index: number }[]; email_sent_at: string | null;
}
export interface EnrolleePage { data: Enrollee[]; current_page: number; last_page: number; total: number }
