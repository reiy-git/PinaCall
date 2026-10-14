<template>
  <button
    v-if="authState.user"
    type="button"
    class="fixed bottom-4 left-4 z-40 flex items-center gap-2 rounded-full bg-emerald-900 px-4 py-3 text-sm font-bold text-white shadow-lg ring-1 ring-white/20 lg:hidden"
    :aria-expanded="mobileOpen"
    aria-controls="portal-navigation"
    @click="mobileOpen = !mobileOpen"
  >
    <span aria-hidden="true">{{ mobileOpen ? '×' : '☰' }}</span>
    <span>{{ mobileOpen ? 'Close menu' : 'Menu' }}</span>
  </button>

  <div
    v-if="mobileOpen && authState.user"
    class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden"
    aria-hidden="true"
    @click="mobileOpen = false"
  />

  <aside
    v-if="authState.user"
    id="portal-navigation"
    @keydown.esc.prevent.stop="mobileOpen = false"
    class="fixed inset-y-0 left-0 z-50 flex h-dvh w-[min(20rem,88vw)] shrink-0 flex-col overflow-y-auto border-r border-emerald-950 bg-[#09090b] p-5 pt-20 text-white shadow-xl transition-transform lg:sticky lg:top-16 lg:h-[calc(100vh-4rem)] lg:min-h-[32rem] lg:translate-x-0 lg:w-72 lg:pt-5 lg:shadow-none"
    :class="mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    aria-label="Portal navigation"
  >
    <div class="space-y-6">
      <div class="flex items-center justify-between lg:hidden">
        <span class="text-xs font-bold uppercase tracking-wider text-emerald-300">Portal menu</span>
        <button type="button" class="rounded-lg border border-white/15 px-3 py-2 text-xs font-semibold text-white hover:bg-white/10" @click="mobileOpen = false">Close</button>
      </div>
      <div class="rounded-2xl border border-emerald-900/60 bg-[#18181b] p-3.5">
        <p class="truncate text-sm font-bold">{{ displayName }}</p>
        <p class="mt-1 text-xs capitalize text-emerald-300">{{ roleLabel }}</p>
      </div>

      <nav class="space-y-2" :aria-label="`${roleLabel} pages`">
        <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-emerald-400">{{ sectionLabel }}</p>
        <button
          v-for="item in navigationItems"
          :key="item.page"
          type="button"
          class="w-full rounded-lg border px-3 py-2.5 text-left text-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400"
          :class="activeSidebarSection === item.page
            ? 'border-emerald-700/50 bg-emerald-900/60 font-semibold text-emerald-100'
            : 'border-transparent text-slate-300 hover:bg-white/5 hover:text-white'"
          :aria-current="activeSidebarSection === item.page ? 'page' : undefined"
          @click="navigate(item.page)"
        >
          {{ item.label }}
        </button>
      </nav>
    </div>

    <div class="mt-auto border-t border-emerald-950 pt-4 text-xs text-slate-400">
      <p class="font-bold text-white">Need help?</p>
      <p class="mt-1">Contact the Registrar or ICT Service Desk during office hours.</p>
      <a class="mt-2 inline-block font-medium text-emerald-300" href="mailto:support@pinacall.edu">support@pinacall.edu</a>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { authState, formatDisplayName } from '../services/api';
import { activeSidebarSection, defaultPortalPage, isPortalPageAllowed, setSidebarSection, type PortalPage } from '../services/navigation';
import type { UserRole } from '../types';

const mobileOpen = ref(false);

const itemsByRole: Record<UserRole, { page: PortalPage; label: string }[]> = {
  student: [
    { page: 'overview', label: 'Dashboard overview' },
    { page: 'enrollment', label: 'Enrollment application' },
    { page: 'clearance', label: 'Clearance tracker' },
    { page: 'grades', label: 'Academic records and grades' },
    { page: 'documents', label: 'Document requests' },
  ],
  registrar: [
    { page: 'applications', label: 'Admissions and applications' },
    { page: 'students', label: 'Student registry' },
    { page: 'documents', label: 'Document request fulfillment' },
  ],
  cashier: [
    { page: 'clearances', label: 'Financial clearance ledger' },
    { page: 'documents', label: 'Document payment collection' },
  ],
  department_head: [
    { page: 'clearances', label: 'Department and dean clearance' },
    { page: 'grades', label: 'Academic records and approvals' },
    { page: 'sections', label: 'Subjects, sections, and rosters' },
  ],
  faculty: [
    { page: 'sections', label: 'My assigned sections' },
    { page: 'gradebook', label: 'Class gradebook and ratings' },
  ],
  admin: [
    { page: 'analytics', label: 'Institutional overview' },
    { page: 'users', label: 'User accounts registry' },
    { page: 'courses', label: 'Subject catalog' },
  ],
};

const roleLabel = computed(() => {
  const role = authState.user?.role;
  if (role === 'department_head') return 'Department head';
  return role ? `${role.charAt(0).toUpperCase()}${role.slice(1)}` : '';
});
const displayName = computed(() => formatDisplayName(authState.user?.name));

const sectionLabel = computed(() => authState.user?.role === 'cashier'
  ? 'Financial services'
  : authState.user?.role === 'department_head'
    ? 'Academic affairs'
    : authState.user?.role === 'admin'
      ? 'Administration'
      : authState.user?.role === 'faculty'
        ? 'Faculty services'
        : authState.user?.role === 'registrar'
          ? 'Registrar services'
          : 'Student services');

const navigationItems = computed(() => authState.user ? itemsByRole[authState.user.role].map(item =>
  authState.user?.approved_admission && item.page === 'enrollment'
    ? { ...item, label: 'Subjects & sections' } : item
) : []);

function navigate(page: PortalPage) {
  if (!authState.user || !isPortalPageAllowed(authState.user.role, page)) return;
  setSidebarSection(page, authState.user.role);
  mobileOpen.value = false;
}

watch(() => authState.user?.role, (role) => {
  mobileOpen.value = false;
  if (role && !isPortalPageAllowed(role, activeSidebarSection.value)) {
    setSidebarSection(defaultPortalPage(role), role);
  }
});
</script>
