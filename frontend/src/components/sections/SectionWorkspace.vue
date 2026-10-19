<template>
  <section class="space-y-6">
    <header class="rounded-3xl border border-emerald-900/10 bg-white p-5 shadow-sm sm:p-7">
      <p class="text-xs font-bold uppercase tracking-wider text-emerald-800">
        {{ mode === 'department' ? 'Department academic affairs' : mode === 'faculty' ? 'Faculty teaching assignments' : 'Enrollment' }}
      </p>
      <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">
        {{ mode === 'department' ? 'Subjects, sections, and rosters' : mode === 'faculty' ? 'My assigned sections' : 'Available course sections' }}
      </h2>
      <p class="mt-2 max-w-3xl text-sm leading-relaxed text-slate-600">
        {{ mode === 'department'
          ? 'Manage course offerings for your department, assign faculty, set section capacity, and review current rosters.'
          : mode === 'faculty'
            ? 'View the courses assigned to you and the students currently enrolled in each section.'
            : 'Review open sections that match your program and year. Enrollment is subject to section capacity and academic review.' }}
      </p>
    </header>

    <div v-if="mode === 'department'" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
      <div class="mb-5">
        <h3 class="text-base font-bold text-slate-900">Create a section offering</h3>
        <p class="mt-1 text-sm text-slate-600">Choose an approved subject, faculty member, term, meeting time, and enrollment capacity.</p>
      </div>
      <form class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" @submit.prevent="createSection">
        <label class="text-sm font-semibold text-slate-700">
          Subject
          <select v-model.number="form.course_id" required class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
            <option disabled :value="0">Select a subject</option>
            <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }} · {{ course.title }}</option>
          </select>
        </label>
        <label class="text-sm font-semibold text-slate-700">
          Faculty
          <select v-model.number="form.faculty_id" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
            <option :value="null">Unassigned</option>
            <option v-for="person in faculty" :key="person.id" :value="person.id">{{ person.name }}</option>
          </select>
        </label>
        <label class="text-sm font-semibold text-slate-700">
          Section code
          <input v-model.trim="form.section_code" required maxlength="30" placeholder="e.g. IT101-1A" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
        </label>
        <label class="text-sm font-semibold text-slate-700">
          Degree program
          <select v-model="form.program" required class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
            <option>BS Information Technology</option><option>BS Computer Science</option><option>BS Information Systems</option><option>Associate in Computer Technology</option>
          </select>
        </label>
        <label class="text-sm font-semibold text-slate-700">
          Year level
          <select v-model.number="form.year_level" required class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
            <option :value="1">1st year</option><option :value="2">2nd year</option><option :value="3">3rd year</option><option :value="4">4th year</option><option :value="5">5th year</option><option :value="6">6th year</option>
          </select>
        </label>
        <label class="text-sm font-semibold text-slate-700">
          Academic year
          <input v-model.trim="form.academic_year" required maxlength="30" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
        </label>
        <label class="text-sm font-semibold text-slate-700">
          Semester
          <select v-model="form.semester" required class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
            <option>1st Semester</option><option>2nd Semester</option><option>Summer</option>
          </select>
        </label>
        <label class="text-sm font-semibold text-slate-700">
          Capacity
          <input v-model.number="form.capacity" type="number" min="1" max="250" required class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
        </label>
        <label class="text-sm font-semibold text-slate-700">
          Schedule
          <input v-model.trim="form.schedule" maxlength="255" placeholder="e.g. Mon/Wed 8:00–9:30 AM" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
        </label>
        <label class="text-sm font-semibold text-slate-700">
          Room
          <input v-model.trim="form.room" maxlength="100" placeholder="Room assignment" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
        </label>
        <label class="text-sm font-semibold text-slate-700">
          Enrollment status
          <select v-model="form.status" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm">
            <option value="draft">Draft</option><option value="open">Open</option><option value="closed">Closed</option>
          </select>
        </label>
        <div class="flex items-end xl:col-span-2">
          <button type="submit" :disabled="saving || !courses.length" class="w-full rounded-lg bg-emerald-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-950 disabled:cursor-not-allowed disabled:opacity-50">
            {{ saving ? 'Creating offering…' : 'Create section offering' }}
          </button>
        </div>
      </form>
    </div>

    <div v-if="errorMessage" role="alert" class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ errorMessage }}</div>
    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-5 py-8 text-center text-sm text-slate-600">Loading section information…</div>
    <div v-else-if="sections.length === 0" class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-10 text-center">
      <h3 class="font-semibold text-slate-900">No sections to display</h3>
      <p class="mt-1 text-sm text-slate-600">{{ emptyMessage }}</p>
    </div>

    <div v-else class="grid gap-4 xl:grid-cols-2">
      <article v-for="section in sections" :key="section.id" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-start sm:justify-between">
          <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-wide text-emerald-800">{{ section.course?.code }} · {{ section.section_code }}</p>
            <h3 class="mt-1 text-lg font-bold text-slate-900">{{ section.course?.title || 'Subject offering' }}</h3>
            <p class="mt-1 text-sm text-slate-600">{{ section.program }} · Year {{ section.year_level }}</p>
          </div>
          <span class="w-fit rounded-full px-2.5 py-1 text-xs font-bold capitalize" :class="section.status === 'open' ? 'bg-emerald-100 text-emerald-800' : section.status === 'draft' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700'">{{ section.status }}</span>
        </div>
        <div class="grid grid-cols-2 gap-3 px-5 py-4 text-sm sm:grid-cols-4">
          <div><p class="text-xs text-slate-500">Term</p><p class="mt-1 font-medium text-slate-900">{{ section.semester }} {{ section.academic_year }}</p></div>
          <div><p class="text-xs text-slate-500">Schedule</p><p class="mt-1 font-medium text-slate-900">{{ section.schedule || 'To be scheduled' }}</p></div>
          <div><p class="text-xs text-slate-500">Room</p><p class="mt-1 font-medium text-slate-900">{{ section.room || 'TBA' }}</p></div>
          <div><p class="text-xs text-slate-500">Roster / capacity</p><p class="mt-1 font-medium text-slate-900">{{ section.enrolled_count ?? section.students?.length ?? 0 }} / {{ section.capacity }}</p></div>
        </div>
        <div class="flex flex-col gap-3 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="text-sm">
            <span class="text-slate-500">Faculty: </span>
            <span class="font-semibold text-slate-900">{{ section.faculty?.name || 'Not assigned' }}</span>
          </div>
          <div class="flex flex-wrap gap-2">
            <label v-if="mode === 'department'" class="sr-only" :for="`faculty-${section.id}`">Assign faculty to {{ section.section_code }}</label>
            <select v-if="mode === 'department'" :id="`faculty-${section.id}`" :value="section.faculty_id ?? ''" class="rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-xs" @change="assignFaculty(section, $event)">
              <option value="">Unassigned</option><option v-for="person in faculty" :key="person.id" :value="person.id">{{ person.name }}</option>
            </select>
            <button v-if="mode === 'department'" type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="toggleStatus(section)">
              {{ section.status === 'open' ? 'Close enrollment' : 'Open enrollment' }}
            </button>
            <button v-if="mode !== 'student'" type="button" class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-800 hover:bg-slate-200" @click="showRoster(section)">
              {{ selectedRoster?.id === section.id ? 'Refresh roster' : gradebook ? 'Open gradebook' : 'View roster' }}
            </button>
            <button v-if="mode === 'student'" type="button" :disabled="saving || section.status !== 'open'" class="rounded-lg bg-emerald-900 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-950 disabled:cursor-not-allowed disabled:opacity-50" @click="requestEnrollment(section)">
              Request enrollment
            </button>
          </div>
        </div>
        <div v-if="selectedRoster?.id === section.id" class="border-t border-slate-100 bg-slate-50/70 px-5 py-4">
          <h4 class="text-sm font-bold text-slate-900">{{ gradebook ? 'Section gradebook' : 'Enrolled students' }} ({{ selectedRoster.students?.length ?? 0 }})</h4>
          <p v-if="gradebook" class="mt-1 text-xs text-slate-600">Enter valid ratings from 1.00 to 5.00. Saved grades remain private until you explicitly release them.</p>
          <p v-if="rosterError" class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" role="alert">{{ rosterError }}</p>
          <div v-if="selectedRoster.students?.length && gradebook" class="mt-3 overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full min-w-[680px] text-left text-sm">
              <thead class="bg-slate-100 text-xs uppercase tracking-wide text-slate-600"><tr><th class="px-3 py-2">Student</th><th class="px-3 py-2">Student number</th><th class="px-3 py-2 text-center">Midterm</th><th class="px-3 py-2 text-center">Final</th><th class="px-3 py-2 text-center">Rating</th><th class="px-3 py-2">Grade status</th><th class="px-3 py-2"></th></tr></thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="student in selectedRoster.students" :key="student.id">
                  <template v-if="student.enrollments?.length">
                    <template v-for="enrollment in student.enrollments" :key="enrollment.id">
                      <td class="px-3 py-3 font-medium text-slate-900">{{ formatDisplayName(student.user?.name || student.name) }}</td>
                      <td class="px-3 py-3 text-xs text-slate-600">{{ student.student_number }}</td>
                      <td class="px-3 py-3 text-center"><input v-model="gradeDrafts[enrollment.id].midterm" type="number" inputmode="decimal" min="1" max="5" step="0.01" class="w-20 rounded-md border border-slate-300 px-2 py-1 text-center" :aria-label="`Midterm grade for ${student.name}`"></td>
                      <td class="px-3 py-3 text-center"><input v-model="gradeDrafts[enrollment.id].final" type="number" inputmode="decimal" min="1" max="5" step="0.01" class="w-20 rounded-md border border-slate-300 px-2 py-1 text-center" :aria-label="`Final grade for ${student.name}`"></td>
                      <td class="px-3 py-3 text-center font-bold">{{ enrollment.grade ?? '—' }}</td>
                      <td class="px-3 py-3 text-xs text-slate-600">{{ enrollment.grades_released ? 'Released' : enrollment.status === 'graded' ? 'Ready to release' : 'In progress' }}</td>
                      <td class="whitespace-nowrap px-3 py-3 text-right">
                        <button type="button" :disabled="savingEnrollmentId === enrollment.id" class="rounded-md bg-emerald-900 px-2.5 py-1.5 text-xs font-semibold text-white disabled:opacity-50" @click="saveGrade(enrollment)">Save</button>
                        <button v-if="enrollment.status === 'graded' && !enrollment.grades_released" type="button" :disabled="savingEnrollmentId === enrollment.id" class="ml-1 rounded-md border border-emerald-800 px-2.5 py-1.5 text-xs font-semibold text-emerald-900 disabled:opacity-50" @click="releaseGrade(enrollment)">Release</button>
                      </td>
                    </template>
                  </template>
                  <td v-else colspan="7" class="px-3 py-3 text-center text-xs text-slate-500">{{ formatDisplayName(student.user?.name || student.name) }} — no active enrollment record found for this section.</td>
                </tr>
              </tbody>
            </table>
          </div>
          <ul v-else-if="selectedRoster.students?.length" class="mt-3 divide-y divide-slate-200">
            <li v-for="student in selectedRoster.students" :key="student.id" class="flex items-center justify-between gap-3 py-2 text-sm">
              <span class="font-medium text-slate-800">{{ formatDisplayName(student.user?.name || student.name) }}</span>
              <span class="text-xs text-slate-500">{{ student.student_number }} · {{ student.pivot?.status || 'Enrolled' }}</span>
            </li>
          </ul>
          <p v-else class="mt-2 text-sm text-slate-600">There are no students on this roster yet.</p>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { authState, formatDisplayName, portalApi, showToast } from '../../services/api';
import type { AcademicSection, Course, Enrollment, User } from '../../types';

const props = withDefaults(defineProps<{ mode: 'department' | 'faculty' | 'student'; gradebook?: boolean }>(), { gradebook: false });
const sections = ref<AcademicSection[]>([]);
const courses = ref<Course[]>([]);
const faculty = ref<Pick<User, 'id' | 'name' | 'email'>[]>([]);
const selectedRoster = ref<AcademicSection | null>(null);
const loading = ref(true);
const saving = ref(false);
const errorMessage = ref('');
const rosterError = ref('');
const savingEnrollmentId = ref<number | null>(null);
const gradeDrafts = reactive<Record<number, { midterm: string; final: string }>>({});
const emptyMessage = ref('New offerings will appear here when they are available.');
const form = reactive({
  course_id: 0,
  faculty_id: null as number | null,
  section_code: '',
  program: authState.user?.student?.course_program || 'BS Information Technology',
  year_level: authState.user?.student?.year_level || 1,
  academic_year: '2026-2027',
  semester: '1st Semester',
  schedule: '',
  room: '',
  capacity: 40,
  status: 'draft' as AcademicSection['status'],
});

async function loadWorkspace() {
  loading.value = true;
  errorMessage.value = '';
  try {
    sections.value = await portalApi.getSections();
    if (props.mode === 'department') {
      [courses.value, faculty.value] = await Promise.all([portalApi.getCourses(), portalApi.getFaculty()]);
    }
    emptyMessage.value = props.mode === 'faculty'
      ? 'No sections are assigned to your account. Ask your department head to assign you to an offering.'
      : props.mode === 'department'
        ? 'Create a section offering after confirming that its subject, term, and faculty assignment are correct.'
        : 'There are no open sections matching your program and year at this time.';
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Section information could not be loaded.';
  } finally {
    loading.value = false;
  }
}

async function createSection() {
  saving.value = true;
  errorMessage.value = '';
  try {
    const section = await portalApi.createSection({ ...form });
    showToast('success', 'Section created', `${section.section_code} is now on the academic offering list.`);
    form.section_code = '';
    await loadWorkspace();
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Section could not be created.';
  } finally {
    saving.value = false;
  }
}

async function assignFaculty(section: AcademicSection, event: Event) {
  const value = (event.target as HTMLSelectElement).value;
  errorMessage.value = '';
  try {
    await portalApi.updateSection(section.id, { faculty_id: value ? Number(value) : null });
    await loadWorkspace();
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Faculty assignment could not be updated.';
  }
}

async function toggleStatus(section: AcademicSection) {
  errorMessage.value = '';
  try {
    await portalApi.updateSection(section.id, { status: section.status === 'open' ? 'closed' : 'open' });
    await loadWorkspace();
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Section status could not be updated.';
  }
}

async function showRoster(section: AcademicSection) {
  errorMessage.value = '';
  rosterError.value = '';
  try {
    selectedRoster.value = await portalApi.getSectionRoster(section.id);
    for (const student of selectedRoster.value.students ?? []) {
      for (const enrollment of student.enrollments ?? []) {
        gradeDrafts[enrollment.id] = {
          midterm: enrollment.midterm_grade == null ? '' : String(enrollment.midterm_grade),
          final: enrollment.final_grade == null ? '' : String(enrollment.final_grade),
        };
      }
    }
  } catch (error) {
    rosterError.value = error instanceof Error ? error.message : 'Roster could not be loaded.';
  }
}

async function saveGrade(enrollment: Enrollment) {
  const draft = gradeDrafts[enrollment.id];
  if (!draft || !/^[1-5](?:\.\d{1,2})?$/.test(draft.midterm) || !/^[1-5](?:\.\d{1,2})?$/.test(draft.final)) {
    rosterError.value = 'Enter both ratings as a number from 1.00 to 5.00, with up to two decimal places.';
    return;
  }
  savingEnrollmentId.value = enrollment.id;
  rosterError.value = '';
  try {
    await portalApi.updateGrade(enrollment.id, { midterm_grade: Number(draft.midterm), final_grade: Number(draft.final) });
    if (selectedRoster.value) await showRoster(selectedRoster.value);
  } catch (error) {
    rosterError.value = error instanceof Error ? error.message : 'Grade could not be saved.';
  } finally {
    savingEnrollmentId.value = null;
  }
}

async function releaseGrade(enrollment: Enrollment) {
  savingEnrollmentId.value = enrollment.id;
  rosterError.value = '';
  try {
    await portalApi.releaseGrade(enrollment.id);
    if (selectedRoster.value) await showRoster(selectedRoster.value);
  } catch (error) {
    rosterError.value = error instanceof Error ? error.message : 'Grade could not be released.';
  } finally {
    savingEnrollmentId.value = null;
  }
}

async function requestEnrollment(section: AcademicSection) {
  saving.value = true;
  errorMessage.value = '';
  try {
    await portalApi.enrollInSection(section.id);
    showToast('success', 'Enrollment request recorded', `${section.section_code} has been added to your section roster.`);
    await loadWorkspace();
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Enrollment could not be completed.';
  } finally {
    saving.value = false;
  }
}

onMounted(() => { void loadWorkspace(); });
</script>
