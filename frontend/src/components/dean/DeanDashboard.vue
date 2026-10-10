<template>
  <div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-[#09090b] via-[#064e3b] to-[#022c22] rounded-2xl p-6 text-white shadow-2xl border border-emerald-900/60">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="text-emerald-400 text-xs font-extrabold uppercase tracking-wider mb-1">
            Office of the College Dean & Department Chair
          </div>
          <h2 class="text-2xl font-black tracking-tight text-white">College Academic Operations & Advising Desk</h2>
          <p class="text-xs text-slate-300 mt-1">Curriculum advising for irregulars, transferees, and shiftees; grade encoding; and departmental clearance sign-offs.</p>
        </div>
        <button
          @click="loadData"
          class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white font-semibold text-xs rounded-xl border border-white/20 transition"
        >
          Refresh Operations
        </button>
      </div>

      <!-- Quick Metrics -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-5 border-t border-emerald-800/30">
        <div class="bg-black/30 rounded-xl p-3 border border-emerald-500/10">
          <span class="text-[11px] text-emerald-300/80 uppercase tracking-wider font-semibold">Special Enrollees</span>
          <p class="text-2xl font-black text-amber-300 mt-0.5">{{ specialEnrolleesCount }} Students</p>
        </div>
        <div class="bg-black/30 rounded-xl p-3 border border-emerald-500/10">
          <span class="text-[11px] text-emerald-300/80 uppercase tracking-wider font-semibold">Department Clearances</span>
          <p class="text-2xl font-black text-emerald-400 mt-0.5">{{ deptClearedCount }} Cleared</p>
        </div>
        <div class="bg-black/30 rounded-xl p-3 border border-emerald-500/10">
          <span class="text-[11px] text-emerald-300/80 uppercase tracking-wider font-semibold">Dean Endorsements</span>
          <p class="text-2xl font-black text-teal-300 mt-0.5">{{ deanClearedCount }} Endorsed</p>
        </div>
        <div class="bg-black/30 rounded-xl p-3 border border-emerald-500/10">
          <span class="text-[11px] text-emerald-300/80 uppercase tracking-wider font-semibold">Active Courses</span>
          <p class="text-2xl font-black text-white mt-0.5">{{ activeSectionCount }} Offerings</p>
        </div>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-1 overflow-x-auto">
      <button
        @click="activeTab = 'clearances'"
        class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'clearances' ? 'bg-[#064e3b] text-white shadow-md' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
      >
        <span>Department & Dean Clearance</span>
      </button>

      <button
        @click="activeTab = 'grades'"
        class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'grades' ? 'bg-[#064e3b] text-white shadow-md' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
      >
        <span>Academic Records & Approvals</span>
      </button>
      <button
        @click="activeTab = 'sections'"
        class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap"
        :class="activeTab === 'sections' ? 'bg-[#064e3b] text-white shadow-md' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
      >
        Subjects, sections, and rosters
      </button>
    </div>

    <SectionWorkspace v-if="activeTab === 'sections'" mode="department" />

    <!-- TAB 1: DEPARTMENT CLEARANCE SIGN-OFF -->
    <div v-if="activeTab === 'clearances'" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="p-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-900">Department Head & Dean Clearance Approvals</h3>
        <span class="text-xs text-slate-500">Advising verification for curriculum and prerequisite compliance</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
              <th class="py-3.5 px-4">Student</th>
              <th class="py-3.5 px-4">Category</th>
              <th class="py-3.5 px-4">Dept Head Status</th>
              <th class="py-3.5 px-4">Dean Status</th>
              <th class="py-3.5 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
            <tr v-for="cl in clearancesList" :key="cl.id" class="hover:bg-slate-50/70">
              <td class="py-3.5 px-4">
                <p class="font-bold text-slate-900">{{ cl.student?.name || cl.student?.user?.name }}</p>
                <p class="font-mono text-[11px] text-blue-900">{{ cl.student?.student_number }} • {{ cl.student?.course_program }}</p>
              </td>
              <td class="py-3.5 px-4">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase" :class="formatCategoryClass(cl.student?.student_type)">
                  {{ cl.student?.student_type || 'regular' }}
                </span>
              </td>
              <td class="py-3.5 px-4">
                <span
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase"
                  :class="cl.dept_status ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                >
                  {{ cl.dept_status ? '✓ CLEARED' : 'PENDING ADVISING' }}
                </span>
              </td>
              <td class="py-3.5 px-4">
                <span
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase"
                  :class="cl.dean_status ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                >
                  {{ cl.dean_status ? '✓ ENDORSED' : 'PENDING' }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-right space-x-1.5">
                <button
                  @click="toggleClearance(cl.id, 'dept', !cl.dept_status)"
                  class="px-2.5 py-1 text-xs font-bold rounded-lg transition"
                  :class="cl.dept_status ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-emerald-800 hover:bg-emerald-900 text-white'"
                >
                  {{ cl.dept_status ? 'Revoke Dept' : 'Sign Dept' }}
                </button>
                <button
                  @click="toggleClearance(cl.id, 'dean', !cl.dean_status)"
                  class="px-2.5 py-1 text-xs font-bold rounded-lg transition"
                  :class="cl.dean_status ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'bg-emerald-700 hover:bg-emerald-600 text-white'"
                >
                  {{ cl.dean_status ? 'Revoke Dean' : 'Endorse Dean' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- TAB 2: GRADE ENCODING -->
    <div v-if="activeTab === 'grades'" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden space-y-4">
      <div class="p-4 border-b border-slate-100 flex items-center justify-between">
        <div>
          <h3 class="text-sm font-bold text-slate-900">Faculty Grade Encoding & Academic Ratings</h3>
          <p class="text-xs text-slate-500">Edit midterm and final grades. Final rating and remarks auto-calculate.</p>
        </div>
      </div>

      <div class="overflow-x-auto p-4 pt-0">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50 border-y border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
              <th class="py-3.5 px-4">Student</th>
              <th class="py-3.5 px-4">Course</th>
              <th class="py-3.5 px-4 text-center">Midterm (1.0-5.0)</th>
              <th class="py-3.5 px-4 text-center">Final (1.0-5.0)</th>
              <th class="py-3.5 px-4 text-center">Final Rating</th>
              <th class="py-3.5 px-4 text-center">Remarks</th>
              <th class="py-3.5 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
            <tr v-for="g in gradesList" :key="g.id" class="hover:bg-slate-50/70">
              <td class="py-3 px-4 font-bold text-slate-900">{{ g.student?.name || g.student?.user?.name }}</td>
              <td class="py-3 px-4">
                <span class="font-bold text-blue-900">{{ g.course?.code }}</span> - {{ g.course?.title }}
              </td>
              <td class="py-3 px-4 text-center">
                <input
                  type="number"
                  step="0.25"
                  min="1.0"
                  max="5.0"
                  v-model.number="g.midterm_grade"
                  class="w-16 text-center bg-slate-50 border border-slate-300 rounded-lg py-1 text-xs font-bold text-slate-900 focus:ring-2 focus:ring-blue-600 focus:outline-none"
                />
              </td>
              <td class="py-3 px-4 text-center">
                <input
                  type="number"
                  step="0.25"
                  min="1.0"
                  max="5.0"
                  v-model.number="g.final_grade"
                  class="w-16 text-center bg-slate-50 border border-slate-300 rounded-lg py-1 text-xs font-bold text-slate-900 focus:ring-2 focus:ring-blue-600 focus:outline-none"
                />
              </td>
              <td class="py-3 px-4 text-center font-black text-blue-900">{{ g.grade || '-' }}</td>
              <td class="py-3 px-4 text-center">
                <span
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                  :class="g.remarks === 'Passed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                >
                  {{ g.remarks || 'Enrolled' }}
                </span>
              </td>
              <td class="py-3 px-4 text-right">
                <button
                  @click="saveGrade(g)"
                  class="px-3 py-1 bg-[#064e3b] hover:bg-[#022c22] text-white rounded-lg text-xs font-bold shadow-sm transition"
                >
                  Save Grade
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { portalApi } from '../../services/api';
import type { AcademicSection, Clearance, Enrollment } from '../../types';
import { activeSidebarSection } from '../../services/navigation';
import SectionWorkspace from '../sections/SectionWorkspace.vue';

const activeTab = ref<'clearances' | 'grades' | 'sections'>('clearances');
watch(activeSidebarSection, (page) => {
  if (page === 'clearances' || page === 'grades' || page === 'sections') activeTab.value = page;
});
const clearancesList = ref<Clearance[]>([]);
const gradesList = ref<Enrollment[]>([]);
const sectionsList = ref<AcademicSection[]>([]);

const deptClearedCount = computed(() => clearancesList.value.filter((c) => c.dept_status).length);
const deanClearedCount = computed(() => clearancesList.value.filter((c) => c.dean_status).length);
const activeSectionCount = computed(() => sectionsList.value.filter((section) => section.status === 'open').length);
const specialEnrolleesCount = computed(() => {
  return clearancesList.value.filter((c) => ['transferee', 'irregular', 'shiftee', 'returnee'].includes(c.student?.student_type || '')).length;
});

async function loadData() {
  try {
    const [clearances, grades, sections] = await Promise.all([
      portalApi.getClearances(),
      portalApi.getGrades(),
      portalApi.getSections(),
    ]);
    clearancesList.value = clearances;
    gradesList.value = grades;
    sectionsList.value = sections;
  } catch (err) {
    console.error(err);
  }
}

async function toggleClearance(id: number, department: string, approved: boolean) {
  try {
    const remarks = approved ? 'Academic requirement & standing validated' : 'Pending curriculum advising';
    await portalApi.updateClearanceStep(id, department, approved, remarks);
    await loadData();
  } catch (err) {
    console.error(err);
  }
}

async function saveGrade(enrollment: Enrollment) {
  try {
    await portalApi.updateGrade(enrollment.id, {
      midterm_grade: enrollment.midterm_grade,
      final_grade: enrollment.final_grade,
    });
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

onMounted(() => {
  loadData();
});
</script>
