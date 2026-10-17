<template>
  <section
    class="group/carousel relative isolate overflow-hidden rounded-3xl ring-1 ring-slate-900/5 dark:ring-white/10"
    role="region"
    aria-roledescription="carousel"
    aria-label="PinaCall services"
    @mouseenter="hovering = true"
    @mouseleave="hovering = false"
    @focusin="focused = true"
    @focusout="focused = false"
    @keydown.left.prevent="prev"
    @keydown.right.prevent="next"
    @pointerdown="onDown"
    @pointerup="onUp"
    @pointercancel="dragX = null"
  >
    <div class="flex h-full transition-transform duration-500 ease-out motion-reduce:transition-none" :style="{ transform: `translateX(-${index * 100}%)` }">
      <!-- 1 · Hero -->
      <article class="slide relative isolate bg-navy text-white" :aria-hidden="index !== 0" :inert="index !== 0" role="group" aria-roledescription="slide" aria-label="1 of 6">
        <img src="/assets/images/campus.webp" alt="Pamantasan ng Cabuyao main building" class="absolute inset-0 -z-10 h-full w-full object-cover opacity-40 mix-blend-luminosity" width="500" height="332" fetchpriority="high" draggable="false">
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-navy via-navy/80 to-transparent"></div>
        <SlideArt variant="hero" class="-z-10 text-white" />
        <div class="flex h-full max-w-2xl flex-col justify-end gap-4 p-6 pb-20 sm:p-10 sm:pb-20">
          <span class="w-fit rounded-full bg-lime px-3 py-1 text-xs font-semibold text-navy">PinaCall University Services</span>
          <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Academic services built around every student journey.</h2>
          <p class="text-sm text-fog">A single institutional platform for admission and enrollment, academic records, multi-office clearance, grade release, and official document requests.</p>
        </div>
      </article>

      <!-- 2–4 · Services -->
      <article
        v-for="(s, i) in services"
        :key="s.tag"
        class="slide relative overflow-hidden text-white"
        :class="s.bg"
        :aria-hidden="index !== i + 1"
        :inert="index !== i + 1"
        role="group"
        aria-roledescription="slide"
        :aria-label="`${i + 2} of 6`"
      >
        <img :src="s.img" :alt="s.alt" class="absolute inset-0 h-full w-full object-cover opacity-40 mix-blend-luminosity" :class="s.pos" width="800" height="600" loading="lazy" draggable="false">
        <div class="absolute inset-0 bg-gradient-to-r to-transparent" :class="s.fade"></div>
        <SlideArt :variant="s.art" class="text-white" />
        <div class="relative flex h-full max-w-xl flex-col justify-center gap-4 p-6 pb-20 sm:p-10 sm:pb-20">
          <span class="w-fit rounded-lg px-2 py-1 text-xs font-semibold" :class="s.chip">{{ s.num }} · {{ s.tag }}</span>
          <h3 class="text-3xl font-bold tracking-tight">{{ s.title }}</h3>
          <p class="text-base" :class="s.copy">{{ s.body }}</p>
        </div>
      </article>

      <!-- 5 · Journey -->
      <article class="slide relative overflow-hidden bg-white dark:bg-slate-50" :aria-hidden="index !== 4" :inert="index !== 4" role="group" aria-roledescription="slide" aria-label="5 of 6">
        <SlideArt variant="journey" class="text-navy" :opacity="0.04" />
        <div class="relative flex h-full flex-col p-6 pb-20 sm:p-10 sm:pb-20">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-semibold text-slate-900">Your journey</h3>
            <span class="text-xs font-semibold text-coral">4 stages</span>
          </div>
          <ul class="mt-4 flex-1 divide-y divide-slate-100 overflow-y-auto">
            <li v-for="(step, i) in journey" :key="step.name" class="flex items-center gap-4 py-3">
              <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-navy dark:text-lime">{{ i + 1 }}</span>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-slate-900">{{ step.name }}</p>
                <p class="text-xs text-slate-500">{{ step.detail }}</p>
              </div>
              <span class="text-xs font-medium text-slate-500">{{ step.owner }}</span>
            </li>
          </ul>
        </div>
      </article>

      <!-- 6 · New student -->
      <article class="slide relative overflow-hidden bg-lime text-navy" :aria-hidden="index !== 5" :inert="index !== 5" role="group" aria-roledescription="slide" aria-label="6 of 6">
        <SlideArt variant="join" class="text-navy" />
        <div class="relative flex h-full max-w-xl flex-col justify-center gap-4 p-6 pb-20 sm:p-10 sm:pb-20">
          <span class="w-fit rounded-lg bg-navy px-2 py-1 text-xs font-semibold text-lime">New student</span>
          <h3 class="text-3xl font-bold tracking-tight">Start your registration online.</h3>
          <p class="text-base text-navy/80">Create a student account and submit your credentials without queuing at the registrar.</p>
          <a href="#sign-in" class="mt-2 w-fit rounded-2xl bg-coral px-5 py-3 text-sm font-semibold text-white transition hover:brightness-105">Create account</a>
        </div>
      </article>
    </div>

    <!-- Minimal controls -->
    <div class="pointer-events-none absolute inset-x-0 bottom-0 flex items-center justify-between p-5 sm:px-10" :class="tone.text">
      <div class="pointer-events-auto flex items-center gap-1.5" role="tablist" aria-label="Choose slide">
        <button
          v-for="(_, i) in total"
          :key="i"
          type="button"
          role="tab"
          :aria-selected="index === i"
          :aria-label="`Go to slide ${i + 1}`"
          class="h-1.5 rounded-full bg-current transition-all duration-300"
          :class="index === i ? 'w-6 opacity-100' : 'w-1.5 opacity-30 hover:opacity-60'"
          @click="go(i)"
        />
      </div>
      <div class="pointer-events-auto flex items-center gap-2">
        <button type="button" class="flex h-9 w-9 items-center justify-center rounded-full transition" :class="tone.btn" aria-label="Previous slide" @click="prev">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
        </button>
        <button type="button" class="flex h-9 w-9 items-center justify-center rounded-full transition" :class="tone.btn" aria-label="Next slide" @click="next">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
        </button>
      </div>
    </div>
    <p class="sr-only" aria-live="polite">Slide {{ index + 1 }} of {{ total }}</p>
  </section>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import SlideArt from './SlideArt.vue';

const AUTOPLAY_MS = 5000;

const services = [
  { num: '01', art: 'enroll' as const, img: '/assets/images/hero-3.webp', alt: 'Students walking past the Pamantasan ng Cabuyao campus building', pos: 'object-center', fade: 'from-navy via-navy/80 dark:from-[#34457f] dark:via-[#34457f]/80', tag: 'Enrollment', title: 'Know your stage.', body: 'Follow credential review, academic evaluation, payment clearance, and official enrollment through one transparent journey.', bg: 'bg-navy dark:bg-[#34457f]', chip: 'bg-black/30', copy: 'text-fog' },
  { num: '02', art: 'records' as const, img: '/assets/images/hero-2.webp', alt: 'University mascot posing with students', pos: 'object-[center_25%]', fade: 'from-coral via-coral/80', tag: 'Academic records', title: 'Grades with accountability.', body: 'Faculty submit academic ratings; students view released outcomes, course loads, and grade history from their own portal.', bg: 'bg-coral', chip: 'bg-black/20', copy: 'text-white/85' },
  { num: '03', art: 'services' as const, img: '/assets/images/hero-4.webp', alt: 'I love PNC sign in front of the campus', pos: 'object-center', fade: 'from-slate-950 via-slate-950/80 dark:from-[#232428] dark:via-[#232428]/80', tag: 'Student services', title: 'Fewer office visits.', body: 'Request official documents and monitor every payment, processing, ready-for-pickup, and release status online.', bg: 'bg-slate-950 dark:bg-[#232428]', chip: 'bg-white/10', copy: 'text-fog' },
];

const journey = [
  { name: 'Credential review', detail: 'Submit and verify your documents', owner: 'Registrar' },
  { name: 'Academic evaluation', detail: 'Program placement and load', owner: 'Dean' },
  { name: 'Payment clearance', detail: 'Assessment and settlement', owner: 'Cashier' },
  { name: 'Official enrollment', detail: 'Sections and class schedule', owner: 'Registrar' },
];

const total = 6;
const index = ref(0);
const hovering = ref(false);
const focused = ref(false);
const dragX = ref<number | null>(null);
const reduceMotion = typeof window !== 'undefined' && !!window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

// Controls adapt to the slide underneath them.
const tone = computed(() => {
  if (index.value === 5) return { text: 'text-navy', btn: 'bg-navy/10 hover:bg-navy/20' };
  if (index.value === 4) return { text: 'text-slate-900', btn: 'bg-slate-900/5 hover:bg-slate-900/10 dark:bg-white/10 dark:hover:bg-white/20' };
  return { text: 'text-white', btn: 'bg-white/15 hover:bg-white/25' };
});

const go = (i: number) => { index.value = (i + total) % total; };
const next = () => go(index.value + 1);
const prev = () => go(index.value - 1);

let timer: number | undefined;

function tick() {
  // Hover / keyboard focus pauses the carousel.
  if (hovering.value || focused.value || document.hidden || reduceMotion) return;
  next();
}

function onDown(e: PointerEvent) { dragX.value = e.clientX; }
function onUp(e: PointerEvent) {
  if (dragX.value === null) return;
  const dx = e.clientX - dragX.value;
  dragX.value = null;
  if (Math.abs(dx) > 50) dx < 0 ? next() : prev();
}

onMounted(() => {
  timer = window.setInterval(tick, AUTOPLAY_MS);
});
onBeforeUnmount(() => window.clearInterval(timer));
</script>

<style scoped>
.slide { width: 100%; flex: 0 0 100%; height: 100%; }
</style>
