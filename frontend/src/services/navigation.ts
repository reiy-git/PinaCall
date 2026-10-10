import { ref, watch } from 'vue';
import type { UserRole } from '../types';

export type PortalPage =
  | 'overview' | 'enrollment' | 'clearance' | 'grades' | 'documents'
  | 'applications' | 'students' | 'clearances' | 'gradebook'
  | 'sections' | 'analytics' | 'users' | 'courses';

const defaultPageByRole: Record<UserRole, PortalPage> = {
  student: 'overview',
  registrar: 'applications',
  cashier: 'clearances',
  department_head: 'clearances',
  faculty: 'sections',
  admin: 'analytics',
};

const pagesByRole: Record<UserRole, PortalPage[]> = {
  student: ['overview', 'enrollment', 'clearance', 'grades', 'documents'],
  registrar: ['applications', 'students', 'documents'],
  cashier: ['clearances', 'documents'],
  department_head: ['clearances', 'grades', 'sections'],
  faculty: ['sections', 'gradebook'],
  admin: ['analytics', 'users', 'courses'],
};

export const activeSidebarSection = ref<PortalPage>('overview');

export function defaultPortalPage(role: UserRole): PortalPage {
  return defaultPageByRole[role];
}

export function isPortalPageAllowed(role: UserRole, page: string): page is PortalPage {
  return pagesByRole[role].includes(page as PortalPage);
}

export function setSidebarSection(section: string, role?: UserRole) {
  if (role) {
    activeSidebarSection.value = isPortalPageAllowed(role, section)
      ? section
      : defaultPortalPage(role);
    return;
  }
  activeSidebarSection.value = section as PortalPage;
}

export function resetPortalPage(role: UserRole | null) {
  activeSidebarSection.value = role ? defaultPortalPage(role) : 'overview';
}

watch(activeSidebarSection, (page) => {
  if (!Object.values(pagesByRole).some((pages) => pages.includes(page))) {
    activeSidebarSection.value = 'overview';
  }
});
