<template>
  <div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-[#161f3d] via-[#1b2750] to-[#2a3b6f] rounded-3xl p-6 text-white shadow-lg border border-emerald-800/40">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="text-emerald-400 text-xs font-bold uppercase tracking-wider mb-1">Office of the University Registrar</div>
          <h2 class="text-2xl font-bold tracking-tight text-white">Admissions & Academic Records Command Center</h2>
          <p class="text-xs text-slate-300 mt-1">Manage student enrollment applications, evaluate transfer credentials, and fulfill document requests.</p>
        </div>
        <div class="flex items-center gap-3">
          <button
            @click="loadData"
            class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white font-semibold text-xs rounded-2xl border border-white/20 transition"
          >
            Refresh Queue
          </button>
        </div>
      </div>

      <!-- Metric KPI Counter Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-5 border-t border-emerald-800/30">
        <div class="bg-black/30 rounded-2xl p-3 border border-emerald-500/10">
          <span class="text-xs text-emerald-300/80 uppercase tracking-wider font-semibold">Pending Applications</span>
          <p class="text-2xl font-bold text-amber-300 mt-0.5">{{ pendingAppsCount }}</p>
        </div>
        <div class="bg-black/30 rounded-2xl p-3 border border-emerald-500/10">
          <span class="text-xs text-emerald-300/80 uppercase tracking-wider font-semibold">Total Approved</span>
          <p class="text-2xl font-bold text-emerald-300 mt-0.5">{{ approvedAppsCount }}</p>
        </div>
        <div class="bg-black/30 rounded-2xl p-3 border border-emerald-500/10">
          <span class="text-xs text-emerald-300/80 uppercase tracking-wider font-semibold">Enrolled Students</span>
          <p class="text-2xl font-bold text-white mt-0.5">{{ studentsList.length }}</p>
        </div>
        <div class="bg-black/30 rounded-2xl p-3 border border-emerald-500/10">
          <span class="text-xs text-emerald-300/80 uppercase tracking-wider font-semibold">Pending Documents</span>
          <p class="text-2xl font-bold text-teal-300 mt-0.5">{{ pendingDocsCount }}</p>
        </div>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-1 overflow-x-auto">
      <button
        @click="activeTab = 'applications'"
        class="px-4 py-2.5 rounded-2xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'applications' ? 'bg-[#2a3b6f] text-white shadow-md' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
      >
        <span>Enrollment Applications</span>
        <span class="px-1.5 py-0.5 rounded-full text-xs font-bold bg-amber-400 text-slate-950">{{ pendingAppsCount }}</span>
      </button>

      <button
        @click="activeTab = 'students'"
        class="px-4 py-2.5 rounded-2xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'students' ? 'bg-[#2a3b6f] text-white shadow-md' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
      >
        <span>Student Registry</span>
      </button>

      <button
        @click="activeTab = 'documents'"
        class="px-4 py-2.5 rounded-2xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'documents' ? 'bg-[#2a3b6f] text-white shadow-md' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
      >
        <span>Document Request Fulfillment</span>
        <span class="px-1.5 py-0.5 rounded-full text-xs font-bold bg-emerald-400 text-slate-950">{{ pendingDocsCount }}</span>
      </button>
    </div>

    <!-- TAB 1: APPLICATIONS QUEUE -->
    <div v-if="activeTab === 'applications'" class="space-y-4">
      <!-- Filters and Search Toolbar -->
      <div class="bg-white rounded-3xl p-4 shadow-sm border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
          <!-- Student Type Filter -->
          <select
            v-model="filterType"
            class="text-xs bg-slate-50 border border-slate-300 rounded-2xl px-3 py-2 font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-600 focus:outline-none"
          >
            <option value="all">All Enrollee Categories</option>
            <option value="new">Freshman / New</option>
            <option value="transferee">Transferee</option>
            <option value="irregular">Irregular</option>
            <option value="old">Old / Continuing</option>
            <option value="shiftee">Shiftee</option>
            <option value="returnee">Returnee</option>
          </select>

          <!-- Status Filter -->
          <select
            v-model="filterStatus"
            class="text-xs bg-slate-50 border border-slate-300 rounded-2xl px-3 py-2 font-semibold text-slate-800 focus:ring-2 focus:ring-blue-600 focus:outline-none"
          >
            <option value="all">All Statuses</option>
            <option value="pending">Pending Evaluation</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>

        <!-- Search Input -->
        <div class="relative min-w-[240px]">
          <input
            v-model="searchTerm"
            type="text"
            placeholder="Search student or school..."
            class="w-full text-xs bg-slate-50 border border-slate-300 rounded-2xl pl-9 pr-3.5 py-2 font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-600 focus:outline-none"
          />
          <svg class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
        </div>
      </div>

      <!-- Applications Data Table -->
      <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                <th class="py-3.5 px-4">Applicant Name & Email</th>
                <th class="py-3.5 px-4">Category</th>
                <th class="py-3.5 px-4">Target Program</th>
                <th class="py-3.5 px-4">Previous School</th>
                <th class="py-3.5 px-4 text-center">Status</th>
                <th class="py-3.5 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
              <tr v-for="app in filteredApplications" :key="app.id" class="hover:bg-slate-50/70 transition">
                <td class="py-3.5 px-4">
                  <p class="font-bold text-slate-900">{{ app.user?.name || 'Applicant' }}</p>
                  <p class="text-xs text-slate-500">{{ app.user?.email }}</p>
                </td>
                <td class="py-3.5 px-4">
                  <span
                    class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase"
                    :class="formatCategoryClass(app.student_type)"
                  >
                    {{ app.student_type }}
                  </span>
                </td>
                <td class="py-3.5 px-4 font-medium text-slate-800">
                  {{ app.target_program }} (Year {{ app.year_level }})
                </td>
                <td class="py-3.5 px-4 text-slate-600">{{ app.previous_school || 'N/A' }}</td>
                <td class="py-3.5 px-4 text-center">
                  <span
                    class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase"
                    :class="app.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : (app.status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800')"
                  >
                    {{ app.status }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-right">
                  <button
                    @click="openReviewModal(app)"
                    class="px-3 py-1.5 rounded-2xl bg-emerald-50 text-emerald-800 hover:bg-emerald-100 font-bold text-xs transition border border-emerald-300"
                  >
                    Evaluate
                  </button>
                </td>
              </tr>
              <tr v-if="filteredApplications.length === 0">
                <td colspan="6" class="text-center py-8 text-slate-400 text-xs font-medium">
                  No applications found matching the selected filters.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 2: STUDENT MASTERLIST -->
    <div v-if="activeTab === 'students'" class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="p-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-900">Enrolled Student Registry</h3>
        <span class="text-xs text-slate-500">{{ studentsList.length }} Registered Students</span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
              <th class="py-3.5 px-4">Student ID</th>
              <th class="py-3.5 px-4">Student Name</th>
              <th class="py-3.5 px-4">Category</th>
              <th class="py-3.5 px-4">Degree Program</th>
              <th class="py-3.5 px-4 text-center">Year Level</th>
              <th class="py-3.5 px-4 text-center">Clearance Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
            <tr v-for="st in studentsList" :key="st.id" class="hover:bg-slate-50/70">
              <td class="py-3.5 px-4 font-mono font-bold text-blue-900">{{ st.student_number }}</td>
              <td class="py-3.5 px-4 font-bold text-slate-900">{{ st.name }}</td>
              <td class="py-3.5 px-4">
                <span class="px-2 py-0.5 rounded-full text-xs font-bold uppercase" :class="formatCategoryClass(st.student_type)">
                  {{ st.student_type }}
                </span>
              </td>
              <td class="py-3.5 px-4 font-medium">{{ st.course_program }}</td>
              <td class="py-3.5 px-4 text-center font-bold">Year {{ st.year_level }}</td>
              <td class="py-3.5 px-4 text-center">
                <span
                  class="px-2 py-0.5 rounded-full text-xs font-bold uppercase"
                  :class="st.clearance?.status === 'cleared' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                >
                  {{ st.clearance?.status === 'cleared' ? 'CLEARED' : 'PENDING' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- TAB 3: DOCUMENT REQUEST FULFILLMENT -->
    <div v-if="activeTab === 'documents'" class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="p-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-900">Official Document Fulfillment Queue</h3>
        <span class="text-xs text-slate-500">Release documents upon payment confirmation</span>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
              <th class="py-3.5 px-4">Student</th>
              <th class="py-3.5 px-4">Requested Document</th>
              <th class="py-3.5 px-4">Purpose</th>
              <th class="py-3.5 px-4 text-center">Payment</th>
              <th class="py-3.5 px-4 text-center">Status</th>
              <th class="py-3.5 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
            <tr v-for="doc in documentsList" :key="doc.id" class="hover:bg-slate-50/70">
              <td class="py-3.5 px-4 font-bold text-slate-900">{{ doc.student?.name || 'Student' }}</td>
              <td class="py-3.5 px-4 font-bold text-blue-900">{{ doc.document_type || doc.type }} (x{{ doc.copies }})</td>
              <td class="py-3.5 px-4 text-slate-600">{{ doc.purpose }}</td>
              <td class="py-3.5 px-4 text-center">
                <span class="px-2 py-0.5 rounded-full text-xs font-bold uppercase" :class="doc.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'">
                  {{ doc.payment_status }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-center">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase" :class="formatDocStatusClass(doc.status)">
                  {{ doc.status }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-right space-x-1">
                <button
                  v-if="doc.status === 'processing'"
                  @click="updateDocStatus(doc.id, 'ready_for_pickup')"
                  class="px-2.5 py-1 bg-purple-50 text-purple-700 hover:bg-purple-100 rounded-lg text-xs font-bold border border-purple-200"
                >
                  Mark Ready
                </button>
                <button
                  v-if="doc.status === 'ready_for_pickup'"
                  @click="updateDocStatus(doc.id, 'released')"
                  class="px-2.5 py-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg text-xs font-bold border border-emerald-200"
                >
                  Release Document
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Review Application Modal -->
    <Modal v-model="showReviewModal" title="Evaluate Enrollment Application" subtitle="Review applicant credentials and record registrar decision">
      <div v-if="selectedApp" class="space-y-4">
        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 grid grid-cols-2 gap-3 text-xs">
          <div>
            <span class="text-slate-400 uppercase font-bold text-xs">Applicant Name</span>
            <p class="font-bold text-slate-900 mt-0.5">{{ selectedApp.user?.name }}</p>
          </div>
          <div>
            <span class="text-slate-400 uppercase font-bold text-xs">Enrollee Category</span>
            <p class="font-bold text-blue-900 mt-0.5 uppercase">{{ selectedApp.student_type }}</p>
          </div>
          <div>
            <span class="text-slate-400 uppercase font-bold text-xs">Degree Program</span>
            <p class="font-semibold text-slate-800 mt-0.5">{{ selectedApp.target_program }} (Year {{ selectedApp.year_level }})</p>
          </div>
          <div>
            <span class="text-slate-400 uppercase font-bold text-xs">Previous School / Dept</span>
            <p class="font-semibold text-slate-800 mt-0.5">{{ selectedApp.previous_school }}</p>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Educational Background & Credentials</label>
          <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 text-xs text-slate-700">
            {{ selectedApp.educational_background }}
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Assign Course Section</label>
            <select
              v-model="reviewForm.assigned_section_id"
              class="w-full text-xs bg-slate-50 border border-slate-300 rounded-2xl px-3 py-2 font-semibold text-slate-800 focus:ring-2 focus:ring-blue-600 focus:outline-none"
            >
              <option :value="null">Assign later</option>
              <option v-for="section in compatibleSections" :key="section.id" :value="section.id">
                {{ section.section_code }} · {{ section.course?.code }} {{ section.course?.title }} ({{ section.enrolled_count || 0 }}/{{ section.capacity }})
              </option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Evaluation Decision</label>
            <select
              v-model="reviewForm.status"
              class="w-full text-xs bg-slate-50 border border-slate-300 rounded-2xl px-3 py-2 font-bold text-slate-800 focus:ring-2 focus:ring-blue-600 focus:outline-none"
            >
              <option value="approved">Approve Application</option>
              <option value="pending">Keep Pending / Hold</option>
              <option value="rejected">Reject Application</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Registrar Evaluation Notes</label>
          <textarea
            v-model="reviewForm.evaluation_notes"
            rows="3"
            placeholder="Enter notes on credentials, prerequisite waivers, or admissions requirements..."
            class="w-full text-xs bg-slate-50 border border-slate-300 rounded-2xl px-3 py-2 font-medium text-slate-800 focus:ring-2 focus:ring-blue-600 focus:outline-none"
          ></textarea>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
          <button
            type="button"
            @click="showReviewModal = false"
            class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-2xl transition"
          >
            Cancel
          </button>
          <button
            type="button"
            @click="saveApplicationReview"
            class="px-5 py-2 text-xs font-bold bg-[#2a3b6f] hover:bg-[#1b2750] text-white rounded-2xl shadow transition"
          >
            Record Registrar Decision
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { portalApi } from '../../services/api';
import { activeSidebarSection } from '../../services/navigation';
import Modal from '../Modal.vue';
import type { StudentApplication, Student, DocumentRequest, AcademicSection } from '../../types';

const activeTab = ref<'applications' | 'students' | 'documents'>('applications');
watch(activeSidebarSection, (page) => {
  if (page === 'applications' || page === 'students' || page === 'documents') activeTab.value = page;
});
const filterType = ref('all');
const filterStatus = ref('all');
const searchTerm = ref('');

const applicationsList = ref<StudentApplication[]>([]);
const studentsList = ref<Student[]>([]);
const documentsList = ref<DocumentRequest[]>([]);
const sectionsList = ref<AcademicSection[]>([]);

const showReviewModal = ref(false);
const selectedApp = ref<StudentApplication | null>(null);
const reviewForm = ref({
  status: 'approved' as 'approved' | 'pending' | 'rejected',
  assigned_section_id: null as number | null,
  evaluation_notes: '',
});

const pendingAppsCount = computed(() => applicationsList.value.filter((a) => a.status === 'pending').length);
const approvedAppsCount = computed(() => applicationsList.value.filter((a) => a.status === 'approved').length);
const pendingDocsCount = computed(() => documentsList.value.filter((d) => d.status === 'submitted' || d.status === 'processing').length);
const compatibleSections = computed(() => sectionsList.value.filter((section) =>
  section.status === 'open'
  && section.program === selectedApp.value?.target_program
  && section.year_level === selectedApp.value?.year_level
  && section.academic_year === selectedApp.value?.academic_year
  && section.semester === selectedApp.value?.semester
  && (section.enrolled_count || 0) < section.capacity
));

const filteredApplications = computed(() => {
  return applicationsList.value.filter((app) => {
    const matchesType = filterType.value === 'all' || app.student_type === filterType.value;
    const matchesStatus = filterStatus.value === 'all' || app.status === filterStatus.value;
    const term = searchTerm.value.toLowerCase();
    const matchesSearch = !term ||
      app.user?.name.toLowerCase().includes(term) ||
      app.previous_school?.toLowerCase().includes(term) ||
      app.target_program.toLowerCase().includes(term);
    return matchesType && matchesStatus && matchesSearch;
  });
});

async function loadData() {
  try {
    const [apps, docs, students, sections] = await Promise.all([
      portalApi.getApplications(),
      portalApi.getDocuments(),
      portalApi.getStudents(),
      portalApi.getSections(),
    ]);
    applicationsList.value = apps;
    documentsList.value = docs;
    studentsList.value = students;
    sectionsList.value = sections;
  } catch (err) {
    console.error('Error loading registrar data:', err);
  }
}

function openReviewModal(app: StudentApplication) {
  selectedApp.value = app;
  reviewForm.value.status = app.status;
  reviewForm.value.assigned_section_id = app.assigned_section_id
    ?? sectionsList.value.find((section) =>
      section.section_code === app.assigned_section
      && section.program === app.target_program
      && section.year_level === app.year_level
      && section.academic_year === app.academic_year
      && section.semester === app.semester
    )?.id
    ?? null;
  reviewForm.value.evaluation_notes = app.evaluation_notes || 'Credentials verified. Eligible for enrollment.';
  showReviewModal.value = true;
}

async function saveApplicationReview() {
  if (!selectedApp.value) return;
  try {
    await portalApi.reviewApplication(selectedApp.value.id, {
      status: reviewForm.value.status,
      assigned_section_id: reviewForm.value.assigned_section_id ?? undefined,
      evaluation_notes: reviewForm.value.evaluation_notes,
    });
    showReviewModal.value = false;
    await loadData();
  } catch (err) {
    console.error(err);
  }
}

async function updateDocStatus(id: number, status: string) {
  try {
    await portalApi.updateDocumentStatus(id, { status });
    await loadData();
  } catch (err) {
    console.error(err);
  }
}

function formatCategoryClass(type?: string) {
  switch (type) {
    case 'new': return 'bg-emerald-100 text-emerald-800';
    case 'transferee': return 'bg-purple-100 text-purple-800';
    case 'irregular': return 'bg-amber-100 text-amber-800';
    case 'old': return 'bg-blue-100 text-blue-800';
    case 'shiftee': return 'bg-pink-100 text-pink-800';
    case 'returnee': return 'bg-orange-100 text-orange-800';
    default: return 'bg-slate-100 text-slate-800';
  }
}

function formatDocStatusClass(status: string) {
  switch (status) {
    case 'submitted': return 'bg-amber-100 text-amber-800';
    case 'processing': return 'bg-blue-100 text-blue-800';
    case 'ready_for_pickup': return 'bg-purple-100 text-purple-800';
    case 'released': return 'bg-emerald-100 text-emerald-800';
    default: return 'bg-slate-100 text-slate-800';
  }
}

onMounted(() => {
  loadData();
});
</script>
