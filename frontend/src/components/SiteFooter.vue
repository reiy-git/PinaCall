<template>
  <footer class="mt-10 border-t-0 text-slate-700" aria-label="Site footer">
    <div class="mx-auto w-full max-w-[90rem] rounded-3xl bg-white p-6 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 sm:p-10">
      <div class="grid gap-10 pb-0 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)] lg:gap-8">
        <!-- Identity -->
        <div class="min-w-0">
          <a href="/" class="flex items-center gap-3" aria-label="PinaCall home">
            <img src="/assets/images/seal.webp" alt="" class="h-14 w-14 shrink-0 rounded-full" width="56" height="56">
            <span class="leading-tight">
              <span class="block text-xl font-bold tracking-tight text-navy dark:text-white">Pamantasan ng Cabuyao</span>
              <span class="block text-sm text-slate-500">University of Cabuyao · PinaCall Portal</span>
            </span>
          </a>
          <address class="mt-6 space-y-1.5 text-sm not-italic text-slate-600">
            <p>Banay-Banay, Cabuyao City, Laguna, Philippines</p>
            <p><a class="underline decoration-slate-300 underline-offset-4 hover:text-navy dark:hover:text-lime" href="https://pnc.edu.ph" target="_blank" rel="noopener">pnc.edu.ph</a></p>
            <p><a class="underline decoration-slate-300 underline-offset-4 hover:text-navy dark:hover:text-lime" href="mailto:support@pinacall.edu">support@pinacall.edu</a></p>
          </address>
          <p class="mt-4 text-xs font-semibold uppercase tracking-wider text-navy dark:text-lime">Peace · Prosperity · Service</p>
          <p class="mt-1 text-xs text-slate-500">Established 2003</p>
        </div>

        <!-- Actions + links -->
        <div class="min-w-0">
          <div class="grid grid-cols-3 gap-2 sm:gap-3">
            <a href="/" class="rounded-md bg-navy px-3 py-3 text-center text-xs font-semibold uppercase tracking-[0.2em] text-white transition hover:brightness-110 dark:bg-[#34457f]">{{ authState.user ? 'Portal' : 'Sign in' }}</a>
            <a href="https://pnc.edu.ph" target="_blank" rel="noopener" class="rounded-md bg-lime px-3 py-3 text-center text-xs font-semibold uppercase tracking-[0.2em] text-navy transition hover:brightness-95">Visit</a>
            <a href="mailto:support@pinacall.edu" class="rounded-md bg-coral px-3 py-3 text-center text-xs font-semibold uppercase tracking-[0.2em] text-white transition hover:brightness-105">Help</a>
          </div>

          <p class="mt-8 text-xs font-semibold uppercase tracking-wider text-navy dark:text-white">Quicklinks</p>
          <ul class="mt-3 grid grid-cols-2 gap-x-6 gap-y-2.5 text-sm text-slate-600">
            <li><a class="hover:text-navy hover:underline dark:hover:text-lime" href="/#services">Portal services</a></li>
            <li><a class="hover:text-navy hover:underline dark:hover:text-lime" href="/terms">Terms of Service</a></li>
            <li><a class="hover:text-navy hover:underline dark:hover:text-lime" href="/#sign-in">Create an account</a></li>
            <li><a class="hover:text-navy hover:underline dark:hover:text-lime" href="/privacy">Privacy Policy</a></li>
            <li><a class="hover:text-navy hover:underline dark:hover:text-lime" href="https://pnc.edu.ph" target="_blank" rel="noopener">University website</a></li>
            <li><a class="hover:text-navy hover:underline dark:hover:text-lime" href="mailto:support@pinacall.edu">Service desk</a></li>
          </ul>

          <button type="button" class="ml-auto mt-8 flex items-center gap-3 pb-4 text-xs font-medium uppercase tracking-wider text-slate-600 hover:text-navy dark:hover:text-lime" @click="toTop">
            Back to top
            <span class="flex h-10 w-10 items-center justify-center border border-slate-400 text-navy dark:text-white">
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 15 7-7 7 7" /></svg>
            </span>
          </button>
        </div>
      </div>

      <!-- Rule + legal strip -->
      <div class="border-t-2 border-navy dark:border-slate-300"></div>
      <!-- Development team -->
      <div class="mt-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-navy dark:text-white">Built by</p>
        <ul class="mt-3 flex flex-wrap gap-3">
          <li v-for="m in team" :key="m">
            <a
              :href="`https://github.com/${m}`"
              target="_blank"
              rel="noopener"
              class="group flex items-center gap-2.5 rounded-full bg-slate-50 py-1.5 pl-1.5 pr-4 ring-1 ring-slate-900/5 transition hover:ring-navy dark:hover:ring-lime"
            >
              <span class="relative flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-navy text-xs font-semibold uppercase text-lime">
                <span aria-hidden="true">{{ m.slice(0, 2) }}</span>
                <img :src="`https://github.com/${m}.png?size=96`" :alt="`${m} on GitHub`" class="absolute inset-0 h-full w-full object-cover" width="36" height="36" loading="lazy" referrerpolicy="no-referrer" @error="($event.target as HTMLImageElement).remove()">
              </span>
              <span class="text-sm font-medium text-slate-700 group-hover:text-navy dark:group-hover:text-lime">{{ m }}</span>
              <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 .5a11.5 11.5 0 0 0-3.64 22.41c.58.1.79-.25.79-.56v-2c-3.2.7-3.88-1.37-3.88-1.37-.52-1.33-1.28-1.69-1.28-1.69-1.04-.71.08-.7.08-.7 1.15.08 1.76 1.19 1.76 1.19 1.03 1.76 2.69 1.25 3.35.96.1-.74.4-1.25.73-1.54-2.55-.29-5.24-1.28-5.24-5.69 0-1.26.45-2.28 1.19-3.09-.12-.29-.52-1.46.11-3.05 0 0 .97-.31 3.17 1.18a10.9 10.9 0 0 1 5.77 0c2.2-1.49 3.17-1.18 3.17-1.18.63 1.59.23 2.76.11 3.05.74.81 1.19 1.83 1.19 3.09 0 4.42-2.7 5.4-5.27 5.68.41.36.78 1.06.78 2.14v3.17c0 .31.21.67.8.56A11.5 11.5 0 0 0 12 .5Z" /></svg>
            </a>
          </li>
        </ul>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2 py-5 text-xs text-slate-500">
        <nav class="flex flex-wrap gap-x-6 gap-y-2" aria-label="Legal">
          <a class="hover:text-navy hover:underline dark:hover:text-lime" href="/privacy">Privacy Policy</a>
          <a class="hover:text-navy hover:underline dark:hover:text-lime" href="/terms">Terms of Service</a>
          <a class="hover:text-navy hover:underline dark:hover:text-lime" href="/#services">Site Map</a>
        </nav>
        <p>© {{ new Date().getFullYear() }} PinaCall · Pamantasan ng Cabuyao. All rights reserved.</p>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { authState } from '../services/api';

const team = ['cookizuki', 'Dekxisosta', 'SHUBARUUU', 'rjtnchn', 'reiy-git', 'jusw-a'];

function toTop() {
  window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
}
</script>
