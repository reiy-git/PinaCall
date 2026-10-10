<template>
  <section class="mx-auto flex min-h-[65vh] w-full max-w-2xl flex-col items-center justify-center text-center">
    <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-800">PINACALL University Portal</p>
    <p class="mt-6 text-6xl font-black tracking-tight text-emerald-950">{{ statusCode }}</p>
    <h1 class="mt-3 text-2xl font-bold text-slate-950">{{ title }}</h1>
    <p class="mt-2 max-w-lg text-sm leading-relaxed text-slate-600">{{ message }}</p>
    <div class="mt-6 flex flex-wrap justify-center gap-3">
      <button v-if="kind === 'service-unavailable'" type="button" class="rounded-xl bg-emerald-900 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-950" @click="$emit('retry')">Retry connection</button>
      <button v-else type="button" class="rounded-xl bg-emerald-900 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-950" @click="$emit('home')">Return to portal</button>
      <button v-if="kind === 'forbidden'" type="button" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="$emit('signout')">Sign out</button>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{ kind: 'not-found' | 'forbidden' | 'service-unavailable' }>();
defineEmits<{ retry: []; home: []; signout: [] }>();

const statusCode = computed(() => props.kind === 'not-found' ? '404' : props.kind === 'forbidden' ? '403' : '503');
const title = computed(() => props.kind === 'not-found'
  ? 'Page not found'
  : props.kind === 'forbidden'
    ? 'Access not permitted'
    : 'University services are temporarily unavailable');
const message = computed(() => props.kind === 'not-found'
  ? 'The page may have moved, or the address may be incorrect. Return to the portal to continue.'
  : props.kind === 'forbidden'
    ? 'Your account does not have permission to view this page. Contact the appropriate university office if you believe this is an error.'
    : 'We could not reach the portal service. Check your connection and try again. Your session has been kept so you can resume securely.');
</script>
