<template>
  <Modal :model-value="!!kind" :title="doc.title" subtitle="PinaCall University Portal" max-width="3xl" @update:model-value="close">
    <p class="text-xs font-medium text-slate-500">Effective {{ effective }}</p>
    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ doc.summary }}</p>

    <section v-for="(s, i) in doc.sections" :key="s.heading" class="mt-6">
      <h4 class="text-sm font-semibold text-slate-900">{{ i + 1 }}. {{ s.heading }}</h4>
      <p v-for="p in s.paragraphs ?? []" :key="p" class="mt-2 text-sm leading-relaxed text-slate-600">{{ p }}</p>
      <ul v-if="s.items" class="mt-2 space-y-2 text-sm leading-relaxed text-slate-600">
        <li v-for="it in s.items" :key="it" class="flex gap-3"><span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-lime ring-1 ring-navy/20"></span><span>{{ it }}</span></li>
      </ul>
    </section>

    <template #footer>
      <button type="button" class="mr-auto text-sm font-medium text-navy underline underline-offset-4 dark:text-lime" @click="emit('update:kind', last === 'terms' ? 'privacy' : 'terms')">
        Read the {{ last === 'terms' ? 'Privacy Policy' : 'Terms of Service' }}
      </button>
      <button type="button" class="rounded-2xl bg-lime px-5 py-2.5 text-sm font-bold text-navy transition hover:brightness-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700" @click="close">Close</button>
    </template>
  </Modal>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import Modal from './Modal.vue';
import { effective, privacy, terms } from '../services/legalDocs';

type Kind = 'terms' | 'privacy';
const props = defineProps<{ kind: Kind | null }>();
const emit = defineEmits<{ 'update:kind': [value: Kind | null] }>();

// Keep the last opened document so the content doesn't blank out during the fade-out
const last = ref<Kind>(props.kind ?? 'terms');
const doc = computed(() => (last.value === 'terms' ? terms : privacy));

function close() { emit('update:kind', null); }
function onKey(e: KeyboardEvent) { if (e.key === 'Escape') close(); }

watch(() => props.kind, (k) => {
  if (k) last.value = k;
  if (k) window.addEventListener('keydown', onKey);
  else window.removeEventListener('keydown', onKey);
}, { immediate: true });
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>
