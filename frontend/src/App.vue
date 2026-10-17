<template>
  <div class="flex min-h-screen flex-col font-sans text-slate-900 antialiased">
    <!-- Top navbar: signed-out header, and the sticky tablet/mobile navbar when signed in -->
    <Navbar :standalone="!!legalPage" />

    <div class="flex min-h-0 w-full flex-1 items-stretch">
      <!-- Sticky aside (desktop) -->
      <Sidebar v-if="authState.user && !authState.user.must_change_password && !authState.serviceUnavailable && !routeError && !legalPage" />

      <main class="min-w-0 flex-1 overflow-x-clip p-4 sm:p-6 lg:p-8">
        <LegalPage v-if="legalPage" :kind="legalPage" />
        <PortalErrorView v-else-if="authState.serviceUnavailable" kind="service-unavailable" @retry="retryConnection" />
        <PortalErrorView v-else-if="routeError" :kind="routeError" @home="goHome" @signout="portalApi.logout()" />
        <template v-else>
          <div v-if="authState.user?.must_change_password" class="mx-auto w-full max-w-[90rem]"><SetPassword /></div>
          <PublicEnrollment v-else-if="pathname === '/enroll' && !authState.user" />
          <div v-else-if="pathname === '/admin/enrollees' && authState.user && ['registrar','admin'].includes(authState.user.role)" class="mx-auto w-full max-w-[90rem]">
            <header class="mb-6">
              <p class="text-sm text-slate-500">{{ greeting }}, {{ firstName }}</p>
              <h1 class="page-title">{{ currentPageLabel }}</h1>
            </header>
            <EnrolleeReview />
          </div>
          <div v-else-if="authState.user" class="mx-auto w-full max-w-[90rem]">
            <header class="mb-6 flex flex-wrap items-end justify-between gap-3">
              <div>
                <p class="text-sm text-slate-500">{{ greeting }}, {{ firstName }}</p>
                <h1 class="page-title">{{ currentPageLabel }}</h1>
              </div>
              <span class="hidden rounded-full bg-white px-4 py-2 text-xs font-medium text-slate-600 ring-1 ring-slate-900/5 lg:inline-block">First Semester · AY 2026–2027</span>
            </header>
            <StudentDashboard v-if="authState.user.role === 'student'" />
            <RegistrarDashboard v-else-if="authState.user.role === 'registrar'" />
            <CashierDashboard v-else-if="authState.user.role === 'cashier'" />
            <DeanDashboard v-else-if="authState.user.role === 'department_head'" />
            <FacultyDashboard v-else-if="authState.user.role === 'faculty'" />
            <AdminDashboard v-else-if="authState.user.role === 'admin'" />
          </div>

          <!-- Public landing, laid out as a bento grid like the reference dashboard -->
          <div v-else class="relative isolate -mx-4 -mt-4 min-h-[calc(100dvh-5.5rem)] overflow-hidden sm:-mx-6 sm:-mt-6 lg:-mx-8 lg:-mt-8">
            <CampusScene />
            <section
              class="relative z-10 mx-auto grid w-full max-w-[90rem] content-start gap-6 px-4 pt-4 pb-[16.5rem] sm:px-6 sm:pt-6 lg:grid-rows-[max(37rem,calc(100dvh-22.25rem))] lg:px-8 lg:pt-8 lg:transition-[grid-template-columns,column-gap] lg:duration-500 lg:ease-in-out motion-reduce:transition-none"
              :class="'lg:grid-cols-[minmax(0,1.5fr)_minmax(22rem,0.7fr)]'"
            >
            <!-- Services column: slides away while the new-student form takes the full width -->
            <div
              class="order-2 min-w-0 overflow-hidden lg:h-full transition-[opacity,translate,max-height] duration-500 ease-in-out motion-reduce:transition-none lg:order-1 lg:max-h-none"
              :class="'max-h-[70rem]'"
            >
              <div class="grid content-start gap-6 lg:h-full lg:min-w-[30rem] lg:grid-rows-[auto_minmax(0,1fr)]">
                <div>
                  <p class="text-sm text-slate-500">Welcome to the University Portal</p>
                  <h1 class="page-title">Academic services</h1>
                </div>

                <LandingCarousel class="h-[30rem] lg:h-full lg:min-h-0" />
              </div>
            </div>
            <div class="order-1 min-w-0 lg:order-2">
              <LoginModal :model-value="true" inline />
            </div>
            </section>
          </div>

          <!-- Disclaimer: its own full-width block, below the 100dvh portal section -->
          <section v-if="!authState.user && pathname !== '/enroll'" class="mx-auto mt-6 w-full max-w-[90rem] rounded-3xl bg-white p-6 ring-1 ring-slate-900/5 sm:p-10 lg:mt-10" aria-labelledby="disclaimer-title">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center lg:gap-12">
              <div class="min-w-0">
                <div class="flex items-center gap-3">
                  <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-coral/10 text-coral">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" /></svg>
                  </span>
                  <h2 id="disclaimer-title" class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Disclaimer</h2>
                </div>
                <p class="mt-5 max-w-4xl text-base leading-relaxed text-slate-600 sm:text-lg">
                  PinaCall is an independent student project developed for academic purposes. It is not an official system of Pamantasan ng Cabuyao (University of Cabuyao) and is not affiliated with, endorsed by, or maintained by the University or its administration. Information, records, and services shown here are for demonstration only and carry no official standing.
                </p>
                <p class="mt-3 max-w-4xl text-base leading-relaxed text-slate-600 sm:text-lg">
                  Students and personnel who need to enroll, view official grades, or request documents should use the University’s official portal.
                </p>
              </div>
              <a
                href="https://pinnacle.pnc.edu.ph"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex w-full items-center justify-center gap-3 rounded-2xl bg-navy px-8 py-4 text-sm font-semibold text-white transition hover:brightness-110 dark:bg-[#34457f] lg:w-auto"
              >
                Go to the official portal
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9" /></svg>
                <span class="sr-only">(opens in a new tab)</span>
              </a>
            </div>
          </section>
        </template>
        <SiteFooter />
      </main>
    </div>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import { authState, formatDisplayName, portalApi } from './services/api';
import { activeSidebarSection, navItemsForUser } from './services/navigation';
import SetPassword from './components/auth/SetPassword.vue';
import EnrolleeReview from './components/registrar/EnrolleeReview.vue';
import PublicEnrollment from './components/enrollment/PublicEnrollment.vue';
import Navbar from './components/Navbar.vue';
import Sidebar from './components/Sidebar.vue';
import SiteFooter from './components/SiteFooter.vue';
import LegalPage from './components/LegalPage.vue';
import LandingCarousel from './components/LandingCarousel.vue';
import CampusScene from './components/scene/CampusScene.vue';
import Toast from './components/Toast.vue';
import LoginModal from './components/auth/LoginModal.vue';
import PortalErrorView from './components/PortalErrorView.vue';
import StudentDashboard from './components/student/StudentDashboard.vue';
import RegistrarDashboard from './components/registrar/RegistrarDashboard.vue';
import CashierDashboard from './components/cashier/CashierDashboard.vue';
import DeanDashboard from './components/dean/DeanDashboard.vue';
import FacultyDashboard from './components/faculty/FacultyDashboard.vue';
import AdminDashboard from './components/admin/AdminDashboard.vue';

const firstName = computed(() => formatDisplayName(authState.user?.name).split(/\s+/)[0] || '');
const greeting = computed(() => {
  const h = new Date().getHours();
  return h < 12 ? 'Good morning' : h < 18 ? 'Good afternoon' : 'Good evening';
});
const currentPageLabel = computed(() => {
  if (pathname === '/admin/enrollees') return 'Guest enrollment applications';
  return navItemsForUser(authState.user).find((i) => i.page === activeSidebarSection.value)?.label || 'Dashboard';
});

const pathname = window.location.pathname.replace(/\/+$/, '') || '/';
const routeError = ref<'not-found' | 'forbidden' | null>(null);
const legalPage = ref<'terms' | 'privacy' | null>(null);

function goHome() {
  window.location.assign('/');
}

function retryConnection() {
  window.location.reload();
}

onMounted(async () => {
  if (pathname === '/terms') legalPage.value = 'terms';
  else if (pathname === '/privacy') legalPage.value = 'privacy';
  await portalApi.restoreSession();
  if (legalPage.value || authState.user?.must_change_password) return;
  if (pathname === '/admin/enrollees' && !['registrar', 'admin'].includes(authState.user?.role || '')) routeError.value = 'forbidden';
  else if (pathname === '/set-password') window.location.assign('/');
  else if (pathname === '/403' || pathname === '/forbidden') routeError.value = 'forbidden';
  else if (!['/', '/index.html', '/enroll', '/admin/enrollees'].includes(pathname)) routeError.value = 'not-found';
});
</script>
