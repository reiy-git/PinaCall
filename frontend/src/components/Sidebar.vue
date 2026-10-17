<template>
  <!-- Desktop (lg+) sticky aside. Below lg the top Navbar takes over. -->
  <aside
    v-if="authState.user"
    id="portal-navigation"
    class="sticky top-3 ml-3 mt-3 hidden h-[calc(100dvh-1.5rem)] w-72 shrink-0 flex-col gap-6 self-start overflow-y-auto rounded-3xl bg-white p-5 shadow-xl shadow-slate-900/10 ring-1 ring-slate-900/5 lg:flex"
    aria-label="Portal navigation"
  >
    <div class="flex items-center justify-between gap-2">
    <a href="/" class="flex items-center gap-3 px-1" aria-label="PinaCall home">
      <img src="/assets/images/seal.webp" alt="" class="h-11 w-11 shrink-0 rounded-full" width="44" height="44">
      <span class="leading-tight">
        <span class="block text-lg font-bold tracking-tight text-navy dark:text-white">Pina<span class="rounded bg-lime px-1 text-navy">Call</span></span>
        <span class="block text-xs text-slate-500">University Portal</span>
      </span>
    </a>
    <ThemeToggle />
    </div>

    <nav class="flex-1 space-y-1" :aria-label="`${roleLabel} pages`">
      <p class="eyebrow px-3 pb-2 text-slate-400">{{ sectionLabel }}</p>
      <button
        v-for="item in navigationItems"
        :key="item.page"
        type="button"
        class="group flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-left text-sm font-medium transition"
        :class="activeSidebarSection === item.page
          ? 'bg-lime font-semibold text-navy'
          : 'text-slate-600 hover:bg-slate-100 hover:text-navy'"
        :aria-current="activeSidebarSection === item.page ? 'page' : undefined"
        @click="navigate(item.page)"
      >
        <span
          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl transition"
          :class="activeSidebarSection === item.page ? 'bg-navy text-lime' : 'bg-slate-100 text-slate-500 group-hover:bg-white'"
        >
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path :d="pageIcons[item.page]" /></svg>
        </span>
        <span class="leading-snug">{{ item.label }}</span>
      </button>
    </nav>

    <div class="space-y-4">
      <div class="rounded-3xl bg-navy p-4 text-white">
        <p class="text-sm font-semibold">Need help?</p>
        <p class="mt-1 text-xs text-fog">Contact the Registrar or ICT Service Desk during office hours.</p>
        <a class="mt-3 inline-block rounded-xl bg-lime px-3 py-1.5 text-xs font-semibold text-navy hover:brightness-95" href="mailto:support@pinacall.edu">support@pinacall.edu</a>
      </div>

      <div class="flex items-center gap-3 rounded-2xl bg-slate-100 p-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-navy text-sm font-semibold text-lime">{{ initials }}</span>
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-semibold text-slate-900">{{ displayName }}</p>
          <p class="truncate text-xs text-slate-500">{{ roleLabel }}</p>
        </div>
        <button type="button" class="rounded-xl p-2 text-slate-500 transition hover:bg-coral/10 hover:text-coral" title="Sign out" aria-label="Sign out" @click="portalApi.logout()">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg>
        </button>
      </div>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue';
import ThemeToggle from './ThemeToggle.vue';
import { authState, formatDisplayName, portalApi } from '../services/api';
import {
  activeSidebarSection, defaultPortalPage, isPortalPageAllowed, navItemsForUser,
  pageIcons, portalSectionLabel, roleDisplayName, setSidebarSection, type PortalPage,
} from '../services/navigation';

const roleLabel = computed(() => roleDisplayName(authState.user?.role));
const displayName = computed(() => formatDisplayName(authState.user?.name));
const initials = computed(() => displayName.value.split(/\s+/).filter(Boolean).slice(0, 2).map((p) => p[0]?.toUpperCase()).join('') || 'U');
const sectionLabel = computed(() => portalSectionLabel(authState.user?.role));
const navigationItems = computed(() => navItemsForUser(authState.user));

function navigate(page: PortalPage) {
  if (!authState.user || !isPortalPageAllowed(authState.user.role, page)) return;
  setSidebarSection(page, authState.user.role);
}

watch(() => authState.user?.role, (role) => {
  if (role && !isPortalPageAllowed(role, activeSidebarSection.value)) {
    setSidebarSection(defaultPortalPage(role), role);
  }
});
</script>
