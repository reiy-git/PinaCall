<template>
  <section class="mx-auto max-w-3xl rounded-2xl bg-white p-6 shadow-sm">
    <h1 class="text-3xl font-bold">Enrollment application</h1>
    <p class="my-4 text-slate-600">Submit your details for Registrar review. An account is issued by email only after approval.</p>
    <div v-if="reference" role="status" class="rounded-xl bg-emerald-50 p-6">
      Application received. Reference: <strong>{{ reference }}</strong>. Please wait for the Registrar's decision. <a href="/" class="underline">Return to sign in</a>
    </div>
    <form v-else @submit.prevent="submit" class="grid gap-4">
      <label>Full legal name<input v-model.trim="form.name" required maxlength="255" autocomplete="name" class="field"></label>
      <label>Email<input v-model.trim="form.email" required type="email" maxlength="255" autocomplete="email" class="field"></label>
      <label>Contact number<input v-model.trim="form.contact_number" required maxlength="30" autocomplete="tel" class="field"></label>
      <label>Program<select v-model="form.program" class="field"><option>BS Information Technology</option><option>BS Computer Science</option><option>BS Information Systems</option></select></label>
      <label>Student category<select v-model="form.student_type" class="field"><option v-for="type in ['new','transferee','old','irregular','shiftee','returnee']" :key="type">{{ type }}</option></select></label>
      <label>Year level<input v-model.number="form.year_level" type="number" min="1" max="6" required class="field"></label>
      <label>Educational background<textarea v-model="form.educational_background" required maxlength="5000" class="field"></textarea></label>
      <label>Supporting documents (1–5 PDF, JPEG or PNG files, up to 5 MB each)<input type="file" accept=".pdf,.jpg,.jpeg,.png" required multiple @change="selectFiles" class="field"></label>
      <p v-if="error" role="alert" class="text-red-700">{{ error }}</p>
      <button :disabled="busy" class="rounded-xl bg-emerald-900 p-3 font-bold text-white disabled:opacity-50">{{ busy ? 'Submitting…' : 'Submit application' }}</button>
      <a href="/" class="text-emerald-800 underline">Already have an account? Sign in</a>
    </form>
  </section>
</template>
<script setup lang="ts">
import { reactive, ref } from 'vue';
import { portalApi } from '../../services/api';
const form = reactive({ name: '', email: '', contact_number: '', program: 'BS Information Technology', student_type: 'new', year_level: 1, educational_background: '' });
const files = ref<File[]>([]); const busy = ref(false); const error = ref(''); const reference = ref('');
function selectFiles(event: Event) { files.value = Array.from((event.target as HTMLInputElement).files || []); }
async function submit() {
  error.value = '';
  if (!files.value.length || files.value.length > 5 || files.value.some(file => file.size > 5 * 1024 * 1024)) { error.value = 'Choose 1–5 documents, each up to 5 MB.'; return; }
  busy.value = true;
  try {
    const payload = new FormData();
    Object.entries(form).forEach(([key,value]) => payload.append(key, String(value)));
    files.value.forEach(file => payload.append('documents[]', file));
    reference.value = (await portalApi.applyEnrollee(payload)).reference;
  } catch (e) { error.value = e instanceof Error ? e.message : 'Submission failed.'; }
  finally { busy.value = false; }
}
</script>
<style scoped>.field { display: block; width: 100%; margin-top: .4rem; padding: .7rem; border: 1px solid #cbd5e1; border-radius: .6rem; }</style>
