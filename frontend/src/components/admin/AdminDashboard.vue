<template>
  <div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-[#161f3d] via-[#1b2750] to-[#2a3b6f] rounded-3xl p-6 text-white shadow-lg border border-emerald-900/60">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="text-emerald-400 text-xs font-bold uppercase tracking-wider mb-1">
            PINACALL System Administration
          </div>
          <h2 class="text-2xl font-bold tracking-tight text-white">Executive Control & System Analytics Center</h2>
          <p class="text-xs text-slate-300 mt-1">Cross-departmental monitoring, user role management, system health, and institutional metrics.</p>
        </div>
        <button
          @click="loadData"
          class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white font-semibold text-xs rounded-2xl border border-white/20 transition"
        >
          Refresh Analytics
        </button>
      </div>

      <!-- Top Level Analytics Grid -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-5 border-t border-emerald-800/30">
        <div class="bg-black/30 rounded-2xl p-3 border border-emerald-500/10">
          <span class="text-xs text-emerald-300/80 uppercase tracking-wider font-semibold">Total Students</span>
          <p class="text-2xl font-bold text-white mt-0.5">{{ stats?.total_students ?? '—' }}</p>
        </div>
        <div class="bg-black/30 rounded-2xl p-3 border border-emerald-500/10">
          <span class="text-xs text-emerald-300/80 uppercase tracking-wider font-semibold">Pending Admissions</span>
          <p class="text-2xl font-bold text-amber-300 mt-0.5">{{ stats?.pending_applications ?? '—' }}</p>
        </div>
        <div class="bg-black/30 rounded-2xl p-3 border border-emerald-500/10">
          <span class="text-xs text-emerald-300/80 uppercase tracking-wider font-semibold">Cleared Students</span>
          <p class="text-2xl font-bold text-emerald-300 mt-0.5">{{ stats?.cleared_students ?? '—' }}</p>
        </div>
        <div class="bg-black/30 rounded-2xl p-3 border border-emerald-500/10">
          <span class="text-xs text-emerald-300/80 uppercase tracking-wider font-semibold">Document Volume</span>
          <p class="text-2xl font-bold text-teal-300 mt-0.5">{{ stats?.pending_documents ?? '—' }}<span v-if="stats"> Active</span></p>
        </div>
      </div>
    </div>

    <nav class="flex flex-wrap gap-2 border-b border-slate-200 pb-3" aria-label="Administration pages">
      <button type="button" @click="setSidebarSection('analytics', 'admin')" class="rounded-lg px-3 py-2 text-sm font-semibold" :class="activeSidebarSection === 'analytics' ? 'bg-emerald-900 text-white' : 'text-slate-700 hover:bg-slate-100'">Institutional overview</button>
      <button type="button" @click="setSidebarSection('users', 'admin')" class="rounded-lg px-3 py-2 text-sm font-semibold" :class="activeSidebarSection === 'users' ? 'bg-emerald-900 text-white' : 'text-slate-700 hover:bg-slate-100'">User accounts</button>
      <button type="button" @click="setSidebarSection('courses', 'admin')" class="rounded-lg px-3 py-2 text-sm font-semibold" :class="activeSidebarSection === 'courses' ? 'bg-emerald-900 text-white' : 'text-slate-700 hover:bg-slate-100'">Subject catalog</button>
    </nav>

    <p v-if="loadError" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
      {{ loadError }}
    </p>

    <!-- Analytics Charts & Breakdown -->
    <div v-if="activeSidebarSection === 'analytics'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Enrollee Category Breakdown -->
      <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200">
        <h3 class="text-sm font-bold text-slate-900 mb-4 flex items-center justify-between">
          <span>Student Category Distribution</span>
          <span class="text-xs text-slate-500 font-normal">Active Cohort</span>
        </h3>

        <div v-if="stats" class="space-y-3 text-xs">
          <div v-for="(count, type) in stats.student_types" :key="type" class="space-y-1">
            <div class="flex items-center justify-between font-semibold">
              <span class="capitalize text-slate-700">{{ type }} Enrollees</span>
              <span class="text-slate-900">{{ count }} ({{ stats.total_students ? Math.round((Number(count) / stats.total_students) * 100) : 0 }}%)</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
              <div
                class="h-2 rounded-full transition-all duration-500"
                :class="getCategoryBarColor(type as string)"
                :style="{ width: `${stats.total_students ? (Number(count) / stats.total_students) * 100 : 0}%` }"
              ></div>
            </div>
          </div>
        </div>
        <p v-else class="text-sm text-slate-600">{{ isLoading ? 'Loading institutional data…' : 'Institutional data is unavailable.' }}</p>
      </div>

      <!-- Security & Architecture Compliance Card -->
      <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200">
        <h3 class="text-sm font-bold text-slate-900 mb-4 flex items-center justify-between">
          <span>Security & Compliance Checklist</span>
          <span class="px-2 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-800">Operational</span>
        </h3>

        <div class="space-y-3 text-xs">
          <div class="p-2.5 rounded-2xl bg-slate-50 border border-slate-200 flex items-start gap-2.5">
            <span class="text-emerald-600 font-bold">✓</span>
            <div>
              <p class="font-bold text-slate-900">SQL Injection Protection</p>
              <p class="text-xs text-slate-500">Parameterized queries enforced via Laravel Eloquent ORM.</p>
            </div>
          </div>

          <div class="p-2.5 rounded-2xl bg-slate-50 border border-slate-200 flex items-start gap-2.5">
            <span class="text-emerald-600 font-bold">✓</span>
            <div>
              <p class="font-bold text-slate-900">Rate Limiting & Throttling</p>
              <p class="text-xs text-slate-500">API endpoints protected against brute-force attacks.</p>
            </div>
          </div>

          <div class="p-2.5 rounded-2xl bg-slate-50 border border-slate-200 flex items-start gap-2.5">
            <span class="text-emerald-600 font-bold">✓</span>
            <div>
              <p class="font-bold text-slate-900">Role-Based Access Control</p>
              <p class="text-xs text-slate-500">Strict separation of student, registrar, cashier, and dean scopes.</p>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- User Accounts Registry -->
    <div v-if="activeSidebarSection === 'users'" class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="p-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-900">Institutional User Accounts Master Registry</h3>
        <div class="flex items-center gap-3">
          <span class="text-xs text-slate-500">{{ authState.personas.length }} Total Accounts</span>
          <button type="button" class="rounded-lg bg-emerald-900 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-950" @click="openUser()">Add account</button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
              <th class="py-3.5 px-4">User Name</th>
              <th class="py-3.5 px-4">Institutional Email</th>
              <th class="py-3.5 px-4">Assigned Role</th>
              <th class="py-3.5 px-4">Student Category</th>
              <th class="py-3.5 px-4">Account status</th>
              <th class="py-3.5 px-4">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
            <tr v-for="user in authState.personas" :key="user.id" class="hover:bg-slate-50/70">
              <td class="py-3.5 px-4 font-bold text-slate-900">{{ formatDisplayName(user.name) }}</td>
              <td class="py-3.5 px-4 text-slate-600 font-mono">{{ user.email }}</td>
              <td class="py-3.5 px-4">
                <span
                  class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase"
                  :class="formatRoleBadgeClass(user.role)"
                >
                  {{ user.role }}
                </span>
              </td>
              <td class="py-3.5 px-4">
                <span v-if="user.student" class="font-medium text-slate-700 capitalize">
                  {{ user.student.student_type }} Enrollee
                </span>
                <span v-else class="text-slate-400">N/A (Staff)</span>
              </td>
              <td class="py-3.5 px-4 text-slate-500">{{ user.is_active === false ? 'Disabled' : user.student?.status || 'Active staff account' }}</td>
              <td class="py-3.5 px-4"><button type="button" class="font-semibold text-emerald-900 underline" @click="openUser(user)">Edit</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <section v-if="activeSidebarSection === 'courses'" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-4">
        <div><h3 class="text-sm font-bold text-slate-900">Subject catalog</h3><p class="mt-1 text-xs text-slate-500">Maintain approved subjects used when departments create section offerings.</p></div>
        <button type="button" class="rounded-lg bg-emerald-900 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-950" @click="openCourse()">Add subject</button>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
          <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">Code</th><th class="px-4 py-3">Subject</th><th class="px-4 py-3">Department</th><th class="px-4 py-3">Units</th><th class="px-4 py-3">Term / year</th><th class="px-4 py-3"></th></tr></thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="course in courses" :key="course.id">
              <td class="px-4 py-3 font-bold text-emerald-950">{{ course.code }}</td><td class="px-4 py-3">{{ course.title }}</td><td class="px-4 py-3">{{ course.department }}</td><td class="px-4 py-3">{{ course.units }}</td><td class="px-4 py-3">{{ course.semester }} · Year {{ course.year_level }}</td>
              <td class="px-4 py-3 text-right"><button type="button" class="font-semibold text-emerald-900 underline" @click="openCourse(course)">Edit</button></td>
            </tr>
            <tr v-if="!courses.length"><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">{{ isLoading ? 'Loading subjects…' : 'No subjects are in the catalog yet.' }}</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <Modal v-model="userModalOpen" :title="editingUserId ? 'Edit account' : 'Create account'" subtitle="Account access and academic profile" max-width="2xl">
      <form id="admin-user-form" class="grid gap-4 sm:grid-cols-2" @submit.prevent="saveUser">
        <label class="text-sm font-semibold text-slate-700">Full name<input v-model.trim="userForm.name" required maxlength="255" autocomplete="name" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700">Email<input v-model.trim="userForm.email" required type="email" maxlength="255" autocomplete="email" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700">Role<select v-model="userForm.role" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5"><option value="student">Student</option><option value="faculty">Faculty</option><option value="department_head">Department head</option><option value="registrar">Registrar</option><option value="cashier">Cashier</option><option value="admin">Administrator</option></select></label>
        <label class="text-sm font-semibold text-slate-700">Department<input v-model.trim="userForm.department" maxlength="100" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
        <label v-if="!editingUserId" class="text-sm font-semibold text-slate-700 sm:col-span-2">Initial password<input v-model="userForm.password" type="password" required minlength="12" maxlength="255" autocomplete="new-password" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5"><span class="mt-1 block text-xs font-normal text-slate-500">At least 12 characters. Share credentials through an approved secure channel.</span></label>
        <label v-else class="flex items-center gap-2 text-sm font-semibold text-slate-700"><input v-model="userForm.is_active" type="checkbox" class="h-4 w-4 accent-emerald-800">Account enabled</label>
        <template v-if="userForm.role === 'student'">
          <label class="text-sm font-semibold text-slate-700">Program<input v-model.trim="userForm.course_program" required maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
          <label class="text-sm font-semibold text-slate-700">Year level<select v-model.number="userForm.year_level" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5"><option v-for="year in 6" :key="year" :value="year">{{ year }}</option></select></label>
          <label class="text-sm font-semibold text-slate-700">Student category<select v-model="userForm.student_type" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5"><option value="new">New</option><option value="transferee">Transferee</option><option value="irregular">Irregular</option><option value="old">Continuing</option><option value="shiftee">Shiftee</option><option value="returnee">Returnee</option></select></label>
          <label class="text-sm font-semibold text-slate-700">Student status<select v-model="userForm.student_status" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5"><option value="pending">Pending</option><option value="active">Active</option><option value="graduated">Graduated</option><option value="rejected">Rejected</option></select></label>
        </template>
        <p v-if="userFormError" class="sm:col-span-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" role="alert">{{ userFormError }}</p>
      </form>
      <template #footer><button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" @click="userModalOpen = false">Cancel</button><button form="admin-user-form" type="submit" :disabled="savingRecord" class="rounded-lg bg-emerald-900 px-4 py-2 text-sm font-bold text-white disabled:opacity-50">{{ savingRecord ? 'Saving…' : 'Save account' }}</button></template>
    </Modal>

    <Modal v-model="courseModalOpen" :title="editingCourseId ? 'Edit subject' : 'Add subject'" subtitle="Course catalog entry" max-width="2xl">
      <form id="admin-course-form" class="grid gap-4 sm:grid-cols-2" @submit.prevent="saveCourse">
        <label class="text-sm font-semibold text-slate-700">Subject code<input v-model.trim="courseForm.code" required maxlength="30" pattern="[A-Za-z0-9._-]+" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700">Title<input v-model.trim="courseForm.title" required maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700">Department<input v-model.trim="courseForm.department" required maxlength="100" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700">Units<input v-model.number="courseForm.units" type="number" inputmode="numeric" min="1" max="12" step="1" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700">Semester<select v-model="courseForm.semester" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5"><option>1st Semester</option><option>2nd Semester</option><option>Summer</option></select></label>
        <label class="text-sm font-semibold text-slate-700">Year level<select v-model.number="courseForm.year_level" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5"><option v-for="year in 6" :key="year" :value="year">{{ year }}</option></select></label>
        <label class="text-sm font-semibold text-slate-700 sm:col-span-2">Description<textarea v-model.trim="courseForm.description" maxlength="5000" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5"></textarea></label>
        <p v-if="courseFormError" class="sm:col-span-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" role="alert">{{ courseFormError }}</p>
      </form>
      <template #footer><button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" @click="courseModalOpen = false">Cancel</button><button form="admin-course-form" type="submit" :disabled="savingRecord" class="rounded-lg bg-emerald-900 px-4 py-2 text-sm font-bold text-white disabled:opacity-50">{{ savingRecord ? 'Saving…' : 'Save subject' }}</button></template>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted, watch } from 'vue';
import { authState, formatDisplayName, portalApi } from '../../services/api';
import type { Course, DashboardStats, StudentType, User, UserRole } from '../../types';
import { activeSidebarSection, setSidebarSection } from '../../services/navigation';
import Modal from '../Modal.vue';

const stats = ref<DashboardStats | null>(null);
const courses = ref<Course[]>([]);
const isLoading = ref(false);
const loadError = ref('');
const userModalOpen = ref(false);
const courseModalOpen = ref(false);
const editingUserId = ref<number | null>(null);
const editingCourseId = ref<number | null>(null);
const savingRecord = ref(false);
const userFormError = ref('');
const courseFormError = ref('');
const userForm = reactive({
  name: '', email: '', role: 'student' as UserRole, department: '', password: '',
  is_active: true, course_program: 'BS Information Technology', year_level: 1,
  student_type: 'new' as StudentType, student_status: 'pending' as NonNullable<User['student']>['status'],
});
const courseForm = reactive({ code: '', title: '', description: '', units: 3, department: 'CCS', semester: '1st Semester', year_level: 1 });

async function loadData() {
  isLoading.value = true;
  loadError.value = '';
  try {
    if (activeSidebarSection.value === 'users') {
      await portalApi.loadPersonas();
    } else if (activeSidebarSection.value === 'courses') {
      courses.value = await portalApi.getCourses();
    } else {
      stats.value = await portalApi.getDashboardStats();
    }
  } catch (err) {
    console.error(err);
    if (activeSidebarSection.value === 'analytics') stats.value = null;
    loadError.value = err instanceof Error ? err.message : 'Unable to load administration data.';
  } finally {
    isLoading.value = false;
  }
}

function openUser(user?: User) {
  editingUserId.value = user?.id ?? null;
  userFormError.value = '';
  Object.assign(userForm, {
    name: user?.name ?? '',
    email: user?.email ?? '',
    role: user?.role ?? 'student',
    department: user?.department ?? '',
    password: '',
    is_active: user?.is_active !== false,
    course_program: user?.student?.course_program ?? 'BS Information Technology',
    year_level: user?.student?.year_level ?? 1,
    student_type: user?.student?.student_type ?? 'new',
    student_status: user?.student?.status ?? 'pending',
  });
  userModalOpen.value = true;
}

async function saveUser() {
  savingRecord.value = true;
  userFormError.value = '';
  try {
    const payload = {
      name: userForm.name, email: userForm.email, role: userForm.role,
      department: userForm.department || null,
      ...(userForm.role === 'student' ? {
        course_program: userForm.course_program,
        year_level: userForm.year_level,
        student_type: userForm.student_type,
        student_status: userForm.student_status,
      } : {}),
      ...(editingUserId.value ? { is_active: userForm.is_active } : { password: userForm.password }),
    };
    if (editingUserId.value) await portalApi.updateUser(editingUserId.value, payload);
    else await portalApi.createUser(payload as Parameters<typeof portalApi.createUser>[0]);
    userModalOpen.value = false;
    await portalApi.loadPersonas();
  } catch (error) {
    userFormError.value = error instanceof Error ? error.message : 'Account could not be saved.';
  } finally {
    savingRecord.value = false;
  }
}

function openCourse(course?: Course) {
  editingCourseId.value = course?.id ?? null;
  courseFormError.value = '';
  Object.assign(courseForm, {
    code: course?.code ?? '', title: course?.title ?? '', description: course?.description ?? '',
    units: course?.units ?? 3, department: course?.department ?? 'CCS',
    semester: course?.semester ?? '1st Semester', year_level: course?.year_level ?? 1,
  });
  courseModalOpen.value = true;
}

async function saveCourse() {
  savingRecord.value = true;
  courseFormError.value = '';
  try {
    if (editingCourseId.value) await portalApi.updateCourse(editingCourseId.value, { ...courseForm });
    else await portalApi.createCourse({ ...courseForm });
    courseModalOpen.value = false;
    courses.value = await portalApi.getCourses();
  } catch (error) {
    courseFormError.value = error instanceof Error ? error.message : 'Subject could not be saved.';
  } finally {
    savingRecord.value = false;
  }
}

function getCategoryBarColor(type: string) {
  switch (type) {
    case 'new': return 'bg-emerald-500';
    case 'transferee': return 'bg-purple-600';
    case 'irregular': return 'bg-amber-500';
    case 'old': return 'bg-blue-600';
    case 'shiftee': return 'bg-pink-600';
    case 'returnee': return 'bg-orange-500';
    default: return 'bg-slate-600';
  }
}

function formatRoleBadgeClass(role: UserRole) {
  switch (role) {
    case 'admin': return 'bg-red-100 text-red-800';
    case 'registrar': return 'bg-blue-100 text-blue-800';
    case 'cashier': return 'bg-amber-100 text-amber-800';
    case 'department_head': return 'bg-purple-100 text-purple-800';
    case 'student': return 'bg-emerald-100 text-emerald-800';
    default: return 'bg-slate-100 text-slate-800';
  }
}

watch(activeSidebarSection, () => { void loadData(); });
onMounted(() => { void loadData(); });
</script>
