<template>
  <div class="min-h-screen bg-[#f8fafc] flex flex-col text-slate-900 font-sans antialiased selection:bg-emerald-400 selection:text-slate-950">
    <!-- Top Header -->
    <Navbar />

    <!-- Main App Layout -->
    <div class="flex min-h-0 flex-1 w-full items-stretch">
      <!-- Role Sidebar -->
      <Sidebar v-if="authState.user && !authState.serviceUnavailable && !routeError" />

      <!-- Content Workspace Area -->
      <main class="min-w-0 flex-1 overflow-x-hidden p-4 sm:p-8 lg:p-10">
        <PortalErrorView v-if="authState.serviceUnavailable" kind="service-unavailable" @retry="retryConnection" />
        <PortalErrorView v-else-if="routeError" :kind="routeError" @home="goHome" @signout="portalApi.logout()" />
        <template v-else>
          <!-- Logged In State by Role -->
          <template v-if="authState.user">
            <StudentDashboard v-if="authState.user.role === 'student'" />
            <RegistrarDashboard v-else-if="authState.user.role === 'registrar'" />
            <CashierDashboard v-else-if="authState.user.role === 'cashier'" />
            <DeanDashboard v-else-if="authState.user.role === 'department_head'" />
            <FacultyDashboard v-else-if="authState.user.role === 'faculty'" />
            <AdminDashboard v-else-if="authState.user.role === 'admin'" />
          </template>

          <!-- Public institutional landing page -->
          <section v-else class="mx-auto grid w-full max-w-[100rem] gap-6 py-5 lg:grid-cols-[minmax(0,1.35fr)_minmax(20rem,0.65fr)] lg:gap-8 lg:py-10">
            <div class="order-2 min-w-0 lg:order-1">
            <div class="flex min-h-[24rem] flex-col justify-center overflow-hidden rounded-3xl bg-gradient-to-br from-zinc-950 via-emerald-950 to-emerald-900 px-6 py-10 text-white shadow-2xl sm:px-12 sm:py-14">
              <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-400">PINACALL University Services</p>
              <h1 class="mt-4 max-w-3xl text-4xl font-black tracking-tight sm:text-6xl">Academic services built around every student journey.</h1>
              <p class="mt-5 max-w-2xl text-base leading-relaxed text-emerald-50/80">A single institutional platform for admission and enrollment, academic records, multi-office clearance, grade release, and official document requests.</p>
            </div>

            <div id="services" class="mt-6 grid gap-5 md:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-xs font-bold uppercase tracking-wider text-emerald-700">01 / Enrollment</p><h2 class="mt-2 text-xl font-black text-slate-950">Know your stage.</h2><p class="mt-2 text-sm leading-relaxed text-slate-600">Follow credential review, academic evaluation, payment clearance, and official enrollment through one transparent journey.</p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-xs font-bold uppercase tracking-wider text-emerald-700">02 / Academic records</p><h2 class="mt-2 text-xl font-black text-slate-950">Grades with accountability.</h2><p class="mt-2 text-sm leading-relaxed text-slate-600">Faculty submit academic ratings; students view released outcomes, course loads, and grade history from their own portal.</p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-xs font-bold uppercase tracking-wider text-emerald-700">03 / Student services</p><h2 class="mt-2 text-xl font-black text-slate-950">Fewer office visits.</h2><p class="mt-2 text-sm leading-relaxed text-slate-600">Request official documents and monitor every payment, processing, ready-for-pickup, and release status online.</p></article>
            </div>
            </div>
            <div class="order-1 min-w-0 lg:order-2">
              <LoginModal :model-value="true" inline />
            </div>
          </section>
        </template>
      </main>
    </div>

    <!-- Notifications -->
    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { authState, portalApi } from './services/api';
import Navbar from './components/Navbar.vue';
import Sidebar from './components/Sidebar.vue';
import Toast from './components/Toast.vue';
import LoginModal from './components/auth/LoginModal.vue';
import PortalErrorView from './components/PortalErrorView.vue';
import StudentDashboard from './components/student/StudentDashboard.vue';
import RegistrarDashboard from './components/registrar/RegistrarDashboard.vue';
import CashierDashboard from './components/cashier/CashierDashboard.vue';
import DeanDashboard from './components/dean/DeanDashboard.vue';
import FacultyDashboard from './components/faculty/FacultyDashboard.vue';
import AdminDashboard from './components/admin/AdminDashboard.vue';

const routeError = ref<'not-found' | 'forbidden' | null>(null);

function goHome() {
  window.location.assign('/');
}

function retryConnection() {
  window.location.reload();
}

onMounted(() => {
  const pathname = window.location.pathname.replace(/\/+$/, '') || '/';
  if (pathname === '/403' || pathname === '/forbidden') routeError.value = 'forbidden';
  else if (pathname !== '/' && pathname !== '/index.html') routeError.value = 'not-found';
  void portalApi.restoreSession();
});
</script>
