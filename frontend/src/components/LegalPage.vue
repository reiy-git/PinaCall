<template>
  <article class="mx-auto w-full max-w-5xl">
    <a href="/" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-navy dark:hover:text-lime">
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
      Back to portal
    </a>

    <header class="mt-6 rounded-3xl bg-navy p-6 text-white sm:p-10">
      <span class="rounded-full bg-lime px-3 py-1 text-xs font-semibold text-navy">Legal</span>
      <h1 class="mt-4 text-3xl font-bold tracking-tight">{{ doc.title }}</h1>
      <p class="mt-2 text-sm text-fog">{{ doc.summary }}</p>
      <p class="mt-4 text-xs text-fog">Effective {{ effective }} · PinaCall University Portal, Pamantasan ng Cabuyao</p>
    </header>

    <!-- Mobile / tablet: sticky chip rail that follows the reader -->
    <nav class="sticky top-[5.5rem] z-30 -mx-4 mt-4 border-b border-slate-900/5 bg-white/80 px-4 py-2 backdrop-blur sm:-mx-6 sm:px-6 lg:hidden" aria-label="On this page">
      <ol ref="rail" class="scrollbar-none flex gap-2 overflow-x-auto">
        <li v-for="(s, i) in doc.sections" :key="s.heading" class="shrink-0">
          <a
            :href="`#s${i + 1}`"
            :data-rail="i"
            class="block rounded-full px-3 py-1.5 text-xs font-medium transition-colors"
            :class="active === i ? 'bg-navy text-lime' : 'bg-white text-slate-600 ring-1 ring-slate-900/5'"
            :aria-current="active === i ? 'location' : undefined"
            @click.prevent="jump(i)"
          >{{ i + 1 }}. {{ s.heading }}</a>
        </li>
      </ol>
    </nav>

    <div class="mt-6 grid gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]">
      <!-- Desktop: sticky table of contents with scroll-spy -->
      <nav class="hidden lg:block" aria-label="On this page">
        <div class="sticky top-28">
          <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">On this page</p>
          <div class="mx-3 mb-3 h-1 overflow-hidden rounded-full bg-slate-200">
            <div class="h-full rounded-full bg-coral transition-[width] duration-150" :style="{ width: `${progress}%` }"></div>
          </div>
          <ol ref="toc" class="scrollbar-none max-h-[calc(100dvh-12rem)] space-y-0.5 overflow-y-auto text-sm">
            <li v-for="(s, i) in doc.sections" :key="s.heading">
              <a
                :href="`#s${i + 1}`"
                :data-toc="i"
                class="relative block rounded-xl py-2 pl-4 pr-3 transition-colors"
                :class="active === i
                  ? 'bg-white font-semibold text-navy shadow-sm ring-1 ring-slate-900/5 dark:text-lime'
                  : 'text-slate-600 hover:bg-slate-900/5 hover:text-slate-900 dark:hover:bg-white/10 dark:hover:text-white'"
                :aria-current="active === i ? 'location' : undefined"
                @click.prevent="jump(i)"
              >
                <span class="absolute left-1 top-2 bottom-2 w-1 rounded-full bg-lime transition-opacity" :class="active === i ? 'opacity-100' : 'opacity-0'" aria-hidden="true"></span>
                {{ i + 1 }}. {{ s.heading }}
              </a>
            </li>
          </ol>
        </div>
      </nav>

      <div class="space-y-4">
        <section
          v-for="(s, i) in doc.sections"
          :id="`s${i + 1}`"
          :key="s.heading"
          class="scroll-mt-32 rounded-3xl bg-white p-6 ring-1 transition-shadow sm:p-8 lg:scroll-mt-24"
          :class="active === i ? 'ring-navy/25 dark:ring-lime/30' : 'ring-slate-900/5'"
        >
          <h2 class="text-xl font-semibold text-slate-900">{{ i + 1 }}. {{ s.heading }}</h2>
          <p v-for="p in s.paragraphs ?? []" :key="p" class="mt-3 text-sm leading-relaxed text-slate-600">{{ p }}</p>
          <ul v-if="s.items" class="mt-3 space-y-2 text-sm leading-relaxed text-slate-600">
            <li v-for="it in s.items" :key="it" class="flex gap-3"><span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-lime ring-1 ring-navy/20"></span><span>{{ it }}</span></li>
          </ul>
        </section>

        <p class="px-2 text-xs text-slate-500">
          Questions about this document? Write to
          <a class="font-medium text-navy underline underline-offset-4 dark:text-lime" href="mailto:support@pinacall.edu">support@pinacall.edu</a>
          or read our
          <a class="font-medium text-navy underline underline-offset-4 dark:text-lime" :href="kind === 'terms' ? '/privacy' : '/terms'">{{ kind === 'terms' ? 'Privacy Policy' : 'Terms of Service' }}</a>.
        </p>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { effective, privacy, terms } from '../services/legalDocs';

const props = defineProps<{ kind: 'terms' | 'privacy' }>();
const doc = computed(() => props.kind === 'terms' ? terms : privacy);

// ---- Scroll-spy: highlight the section being read and keep the nav in view ----
const active = ref(0);
const progress = ref(0);
const toc = ref<HTMLElement | null>(null);
const rail = ref<HTMLElement | null>(null);
const reduce = () => window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
let raf = 0;
let lockUntil = 0;

function offset() {
  // sticky navbar (4rem) + breathing room; rail adds ~3rem below lg
  return window.innerWidth >= 1024 ? 128 : 170;
}

function compute() {
  raf = 0;
  const doc = document.documentElement;
  const max = doc.scrollHeight - window.innerHeight;
  progress.value = max > 0 ? Math.min(100, Math.max(0, (window.scrollY / max) * 100)) : 0;
  if (performance.now() < lockUntil) return;
  const count = props.kind === 'terms' ? terms.sections.length : privacy.sections.length;
  let current = 0;
  for (let i = 0; i < count; i++) {
    const el = document.getElementById(`s${i + 1}`);
    if (el && el.getBoundingClientRect().top - offset() <= 8) current = i;
  }
  if (max > 0 && window.scrollY >= max - 4) current = count - 1;
  active.value = current;
}

function onScroll() { if (!raf) raf = requestAnimationFrame(compute); }

function follow(container: HTMLElement | null, attr: string, horizontal: boolean) {
  const link = container?.querySelector<HTMLElement>(`[${attr}="${active.value}"]`);
  if (!container || !link) return;
  if (horizontal) {
    const left = link.offsetLeft - (container.clientWidth - link.offsetWidth) / 2;
    container.scrollTo({ left, behavior: reduce() ? 'auto' : 'smooth' });
  } else {
    const top = link.offsetTop - (container.clientHeight - link.offsetHeight) / 2;
    container.scrollTo({ top, behavior: reduce() ? 'auto' : 'smooth' });
  }
}

watch(active, () => { follow(toc.value, 'data-toc', false); follow(rail.value, 'data-rail', true); });

function jump(i: number) {
  const el = document.getElementById(`s${i + 1}`);
  if (!el) return;
  active.value = i;
  lockUntil = performance.now() + 700; // don't flicker through sections while smooth-scrolling
  el.scrollIntoView({ behavior: reduce() ? 'auto' : 'smooth', block: 'start' });
  history.replaceState(null, '', `#s${i + 1}`);
}

onMounted(async () => {
  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll);
  await nextTick();
  const m = /^#s(\d+)$/.exec(window.location.hash);
  if (m) document.getElementById(`s${m[1]}`)?.scrollIntoView();
  compute();
});
onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll);
  window.removeEventListener('resize', onScroll);
  if (raf) cancelAnimationFrame(raf);
});
</script>
