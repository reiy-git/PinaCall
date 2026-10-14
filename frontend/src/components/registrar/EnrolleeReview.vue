<template>
  <section class="mx-auto max-w-5xl">
    <h1 class="text-3xl font-bold">Enrollee review</h1>
    <p class="my-3">Review guest applications and their documents before issuing a student account.</p>
    <label>Status <select v-model="status" @change="load(1)" :disabled="busy" class="rounded border p-2"><option>PENDING</option><option>APPROVED</option><option>REJECTED</option></select></label>
    <p v-if="error" role="alert" class="my-3 text-red-700">{{ error }}</p>
    <p v-if="loading" role="status" class="my-4">Loading applications…</p>
    <p v-else-if="!page.data.length" class="my-4">No applications in this category.</p>
    <article v-for="a in page.data" :key="a.id" class="my-4 rounded-2xl border bg-white p-5">
      <h2 class="text-xl font-bold">{{ a.name }} · {{ a.status }}</h2>
      <p>{{ a.email }} · {{ a.contact_number }}</p><p>{{ a.program }} · Year {{ a.year_level }} · {{ a.student_type }}</p>
      <p v-if="a.previous_school" class="mt-3">Previous school: {{ a.previous_school }}</p>
      <p class="my-3 whitespace-pre-wrap">{{ a.educational_background }}</p><p class="text-sm">Reference: {{ a.reference }}</p>
      <button v-for="doc in a.documents" :key="doc.index" @click="download(a.id, doc.index)" class="mr-4 my-3 text-emerald-800 underline">Download {{ doc.name }}</button>
      <template v-if="a.status === 'PENDING'">
        <label class="block">Review remarks (optional)<textarea v-model="remarks[a.id]" maxlength="5000" class="my-3 block w-full rounded border p-2"></textarea></label>
        <button :disabled="busy" @click="decide(a.id, 'approve')" class="mr-3 rounded bg-emerald-900 px-4 py-2 text-white disabled:opacity-50">Approve and issue account</button>
        <button :disabled="busy" @click="decide(a.id, 'reject')" class="rounded border border-red-700 px-4 py-2 text-red-700 disabled:opacity-50">Reject</button>
      </template>
      <template v-else><p>Remarks: {{ a.remarks || 'None' }}</p>
        <div v-if="a.status === 'APPROVED'"><p>Email: {{ a.email_sent_at ? 'Accepted by mail transport' : 'Queued or awaiting reissue' }}</p><button :disabled="busy" @click="decide(a.id, 'resend')" class="mt-3 underline">Reissue temporary credentials</button><p class="text-xs">Available only before the student has set their password; revokes previous credentials.</p></div>
      </template>
    </article>
    <div class="flex gap-4"><button :disabled="loading || busy || page.current_page <= 1" @click="load(page.current_page - 1)">Previous</button><span>Page {{ page.current_page }} of {{ page.last_page }}</span><button :disabled="loading || busy || page.current_page >= page.last_page" @click="load(page.current_page + 1)">Next</button></div>
  </section>
</template>
<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { portalApi, type EnrolleePage } from '../../services/api';
const status = ref('PENDING'); const busy = ref(false); const loading = ref(false); const error = ref('');
const remarks = reactive<Record<number, string>>({});
const page = ref<EnrolleePage>({ data: [], current_page: 1, last_page: 1, total: 0 });
async function load(number = 1) { loading.value = true; error.value = ''; try { page.value = await portalApi.getEnrollees(status.value, number); } catch(e) { error.value = e instanceof Error ? e.message : 'Loading failed.'; } finally { loading.value = false; } }
async function download(id: number, index: number) { try { await portalApi.downloadEnrolleeDocument(id, index); } catch(e) { error.value = e instanceof Error ? e.message : 'Download failed.'; } }
async function decide(id: number, decision: 'approve' | 'reject' | 'resend') {
  if (!window.confirm(decision === 'resend' ? 'Reissue credentials and revoke the previous password?' : `${decision === 'approve' ? 'Approve' : 'Reject'} this application?`)) return;
  busy.value = true; error.value = '';
  try { await portalApi.decideEnrollee(id, decision, remarks[id] || ''); await load(page.value.current_page); } catch(e) { error.value = e instanceof Error ? e.message : 'Review failed.'; } finally { busy.value = false; }
}
onMounted(() => load());
</script>
