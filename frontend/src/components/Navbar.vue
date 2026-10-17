<template>
  <!-- Always shown when signed out; when signed in it is the tablet/mobile (< lg) sticky top navbar. -->
  <header
    class="sticky top-0 z-40 px-3 py-3 sm:px-6"
    :class="authState.user && !standalone ? 'lg:hidden' : ''"
  >
    <div class="mx-auto w-full max-w-[100rem] rounded-3xl bg-white/90 shadow-xl shadow-slate-900/10 ring-1 ring-slate-900/5 backdrop-blur">
    <div class="flex h-16 w-full items-center justify-between gap-3 px-4 sm:px-6">
      <a href="/" class="flex min-w-0 items-center gap-3" aria-label="PinaCall home">
        <img src="/assets/images/seal.webp" alt="" class="h-10 w-10 shrink-0 rounded-full" width="40" height="40">
        <span class="min-w-0 leading-tight">
          <span class="block text-lg font-bold tracking-tight text-navy dark:text-white">Pina<span class="rounded bg-lime px-1 text-navy">Call</span></span>
          <span class="hidden truncate text-xs text-slate-500 sm:block">University Portal</span>
        </span>
      </a>

      <div class="flex items-center gap-3">
        <ThemeToggle />
        <span class="hidden rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600 md:inline-block">First Semester · AY 2026–2027</span>

        <template v-if="authState.user">
          <div class="hidden text-right sm:block">
            <p class="max-w-[10rem] truncate text-sm font-semibold text-slate-900">{{ displayName }}</p>
            <p class="text-xs text-slate-500">{{ roleDisplayName(authState.user.role) }}</p>
          </div>
          <button type="button" class="flex items-center gap-2 rounded-xl bg-coral/10 px-3 py-2 text-sm font-semibold text-coral transition hover:bg-coral/20" title="Sign out" @click="portalApi.logout()">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg>
            <span class="hidden sm:inline">Logout</span>
          </button>
        </template>
        <a v-else href="#sign-in" class="rounded-xl bg-lime px-4 py-2 text-sm font-semibold text-navy transition hover:brightness-95">Sign in</a>
      </div>
    </div>

    <!-- Section links -->
    <nav v-if="authState.user && !standalone" class="scrollbar-none flex w-full gap-2 overflow-x-auto px-4 pb-3 sm:px-6" :aria-label="`${roleDisplayName(authState.user.role)} pages`">
      <button
        v-for="item in items"
        :key="item.page"
        type="button"
        class="flex shrink-0 items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition"
        :class="activeSidebarSection === item.page ? 'bg-lime font-semibold text-navy' : 'bg-slate-100 text-slate-600 hover:text-navy'"
        :aria-current="activeSidebarSection === item.page ? 'page' : undefined"
        @click="go(item.page)"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path :d="pageIcons[item.page]" /></svg>
        {{ item.label }}
      </button>
    </nav>
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed } from 'vue';

defineProps<{ standalone?: boolean }>();
import ThemeToggle from './ThemeToggle.vue';
import { authState, formatDisplayName, portalApi } from '../services/api';
import { activeSidebarSection, isPortalPageAllowed, navItemsByRole, pageIcons, roleDisplayName, setSidebarSection, type PortalPage } from '../services/navigation';

const displayName = computed(() => formatDisplayName(authState.user?.name));
const items = computed(() => authState.user ? navItemsByRole[authState.user.role] : []);

function go(page: PortalPage) {
  if (authState.user && isPortalPageAllowed(authState.user.role, page)) setSidebarSection(page, authState.user.role);
}
</script>
