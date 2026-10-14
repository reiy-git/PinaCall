<template>
  <section class="mx-auto max-w-3xl rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8" aria-labelledby="application-title">
    <p class="text-xs font-bold uppercase tracking-widest text-emerald-700">PinaCall admissions</p>
    <h1 id="application-title" class="mt-2 text-3xl font-bold">Student application</h1>
    <div v-if="reference" ref="confirmation" role="status" tabindex="-1" class="mt-6 rounded-xl bg-emerald-50 p-6 focus:outline-emerald-700">
      <h2 class="text-xl font-bold text-emerald-900">Application received</h2>
      <p class="mt-3">Your application reference is <strong class="break-all">{{ reference }}</strong>.</p>
      <p class="mt-3">Your application is pending Registrar review. Submitting did not create an account. If approved, you will receive account instructions by email, including how to set your password.</p>
      <a href="/" class="mt-5 inline-block font-bold text-emerald-800 underline">Return home / sign in</a>
    </div>
    <template v-else>
      <p class="mt-3 text-sm text-slate-600">Complete your details and upload supporting documents before Registrar review. An account is issued only after approval.</p>
      <ol aria-label="Application steps" class="my-6 grid grid-cols-2 gap-2 sm:grid-cols-4">
        <li v-for="(label, index) in steps" :key="label" :aria-current="step === index ? 'step' : undefined" class="rounded-xl border p-3 text-sm" :class="step === index ? 'border-emerald-700 bg-emerald-50 font-bold text-emerald-900' : 'border-slate-200 text-slate-600'">
          <span class="block text-xs">Step {{ index + 1 }} {{ index < step ? '— Complete' : '' }}</span>{{ label }}
        </li>
      </ol>
      <p v-if="optionsError" role="alert" class="mb-4 text-red-700">{{ optionsError }} <button type="button" class="underline" @click="loadOptions">Retry</button></p>
      <form ref="formElement" novalidate @submit.prevent="advance" :aria-busy="busy">
        <h2 ref="heading" tabindex="-1" class="mb-4 text-xl font-bold focus:outline-none">{{ steps[step] }}</h2>
        <fieldset :disabled="busy" class="grid gap-5">
          <template v-if="step < 2">
            <div v-for="field in visibleFields" :key="field.key">
              <label :for="field.key" class="text-sm font-semibold">{{ field.label }}{{ field.required ? ' *' : ' (optional)' }}</label>
              <select v-if="field.key === 'program' || field.key === 'student_type'" :id="field.key" v-model="form[field.key]" class="field" :aria-required="field.required" :aria-invalid="!!errors[field.key]" :aria-describedby="`${field.key}-error`">
                <option value="" disabled>Select an option</option>
                <option v-for="option in field.key === 'program' ? programs : categories" :key="option" :value="option">{{ categoryLabels[option] || option }}</option>
              </select>
              <textarea v-else-if="field.key === 'educational_background'" :id="field.key" v-model.trim="form[field.key]" rows="4" maxlength="5000" class="field" :aria-required="field.required" :aria-invalid="!!errors[field.key]" :aria-describedby="`${field.key}-error`"></textarea>
              <input v-else :id="field.key" v-model.trim="form[field.key]" :type="field.type || 'text'" :autocomplete="field.autocomplete" :maxlength="field.max" :min="field.key === 'year_level' ? 1 : undefined" :max="field.key === 'year_level' ? 6 : undefined" class="field" :aria-required="field.required" :aria-invalid="!!errors[field.key]" :aria-describedby="`${field.key}-error`">
              <p :id="`${field.key}-error`" class="mt-1 text-sm text-red-700">{{ errors[field.key] }}</p>
            </div>
          </template>
          <div v-if="step === 2">
            <p class="mb-3 text-sm text-slate-600">{{ documentGuidance }}</p>
            <label for="documents" class="text-sm font-semibold">Supporting documents *</label>
            <p id="document-help" class="my-2 text-sm text-slate-600">Choose 1–5 PDF, JPEG or PNG files, up to 5 MB each. Add files below; remove a file before adding its replacement.</p>
            <input id="documents" aria-required="true" type="file" accept=".pdf,.jpg,.jpeg,.png" multiple class="field" :aria-invalid="!!errors.documents" aria-describedby="document-help documents-error" @change="selectFiles">
            <p id="documents-error" class="mt-2 text-sm text-red-700">{{ errors.documents }}</p>
            <ul class="mt-4 space-y-2">
              <li v-for="(file, index) in files" :key="index" class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 p-3 text-sm">
                <span class="min-w-0 break-all">{{ file.name }} <span class="text-slate-500">({{ formatSize(file.size) }})</span></span>
                <button type="button" :aria-label="`Remove ${file.name}`" class="shrink-0 font-semibold text-red-700 underline" @click="removeFile(index)">Remove</button>
              </li>
            </ul>
          </div>
          <div v-if="step === 3" class="space-y-5">
            <p class="text-sm text-slate-600">Review your details. Submitting sends an application for review and does not create an account.</p>
            <div v-for="(group, index) in fieldGroups" :key="index" class="rounded-xl border border-slate-200 p-4">
              <div class="mb-3 flex justify-between gap-3"><h3 class="font-bold">{{ steps[index] }}</h3><button type="button" class="text-emerald-800 underline" :aria-label="`Edit ${steps[index]}`" @click="goTo(index)">Edit</button></div>
              <dl class="space-y-3"><div v-for="field in group" :key="field.key"><dt class="text-xs font-semibold text-slate-500">{{ field.label }}</dt><dd class="whitespace-pre-wrap break-words text-sm">{{ categoryLabels[String(form[field.key])] || form[field.key] || 'Not provided' }}</dd></div></dl>
            </div>
            <div class="rounded-xl border border-slate-200 p-4"><div class="flex justify-between"><h3 class="font-bold">Documents</h3><button type="button" class="text-emerald-800 underline" @click="goTo(2)">Edit documents</button></div><ul class="mt-3 space-y-2 text-sm"><li v-for="(file, index) in files" :key="index" class="break-all">{{ file.name }} ({{ formatSize(file.size) }})</li></ul></div>
          </div>
          <p v-if="error" role="alert" class="rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ error }}</p>
          <div class="flex flex-wrap justify-between gap-3 border-t border-slate-100 pt-5">
            <button v-if="step > 0" type="button" class="rounded-xl border border-slate-300 px-5 py-3 font-semibold" @click="goTo(step - 1)">Previous</button>
            <button type="submit" :disabled="busy || !programs.length" class="ml-auto rounded-xl bg-emerald-900 px-5 py-3 font-bold text-white disabled:opacity-50">{{ busy ? 'Submitting…' : step === 3 ? 'Submit application' : 'Continue' }}</button>
          </div>
        </fieldset>
      </form>
      <a href="/" class="mt-6 inline-block text-sm text-emerald-800 underline">Return home / sign in</a>
    </template>
  </section>
</template>
<script setup lang="ts">
import { computed, nextTick, onMounted, reactive, ref } from 'vue';
import { ApiRequestError, portalApi } from '../../services/api';
const steps = ['Applicant details', 'Academic details', 'Supporting documents', 'Review and submit'];
const form = reactive({ name: '', email: '', contact_number: '', program: '', student_type: 'new', year_level: '1', educational_background: '', previous_school: '' });
type FieldKey = keyof typeof form;
type Field = { key: FieldKey; label: string; required?: boolean; type?: string; max?: number; autocomplete?: string };
const fieldGroups: Field[][] = [
  [{ key: 'name', label: 'Full legal name', required: true, max: 255, autocomplete: 'name' }, { key: 'email', label: 'Email', required: true, type: 'email', max: 255, autocomplete: 'email' }, { key: 'contact_number', label: 'Contact number', required: true, type: 'tel', max: 30, autocomplete: 'tel' }],
  [{ key: 'student_type', label: 'Student category', required: true }, { key: 'program', label: 'Program', required: true }, { key: 'year_level', label: 'Year level', required: true, type: 'number' }, { key: 'educational_background', label: 'Educational background', required: true, max: 5000 }, { key: 'previous_school', label: 'Previous school, where relevant', max: 255 }],
];
const categories = ['new', 'transferee', 'old', 'irregular', 'shiftee', 'returnee'];
const categoryLabels: Record<string, string> = { new: 'New student', transferee: 'Transferee', old: 'Continuing student', irregular: 'Irregular student', shiftee: 'Shiftee', returnee: 'Returnee' };
const step = ref(0);
const visibleFields = computed(() => fieldGroups[step.value] || []);
const files = ref<File[]>([]);
const busy = ref(false);
const error = ref('');
const errors = reactive<Record<string, string>>({});
const reference = ref('');
const programs = ref<string[]>([]);
const documentGuidance = ref('Confirm the supporting documents requested for your application with the Registrar.');
const optionsError = ref('');
const formElement = ref<HTMLFormElement>();
const heading = ref<HTMLElement>();
const confirmation = ref<HTMLElement>();
async function loadOptions() {
  optionsError.value = '';
  try { const options = await portalApi.getEnrollmentOptions(); programs.value = options.programs; documentGuidance.value = options.document_guidance; }
  catch { optionsError.value = 'Application options could not be loaded. Please retry.'; }
}
function formatSize(bytes: number) { return bytes >= 1024 * 1024 ? `${(bytes / (1024 * 1024)).toFixed(1)} MB` : `${Math.ceil(bytes / 1024)} KB`; }
function selectFiles(event: Event) {
  const input = event.target as HTMLInputElement;
  files.value.push(...Array.from(input.files || [])); input.value = '';
  delete errors.documents;
  validateDocuments();
}
function removeFile(index: number) { files.value.splice(index, 1); delete errors.documents; validateDocuments(); }
function validateDocuments() {
  if (files.value.length < 1 || files.value.length > 5) errors.documents = 'Choose between 1 and 5 documents.';
  else if (files.value.some(file => file.size > 5 * 1024 * 1024)) errors.documents = 'Each document must be 5 MB or smaller.';
  else if (files.value.some(file => !/\.(pdf|jpe?g|png)$/i.test(file.name) || (file.type && !['application/pdf', 'image/jpeg', 'image/png'].includes(file.type)))) errors.documents = 'Use PDF, JPEG or PNG documents.';
}
function validate(index: number) {
  Object.keys(errors).forEach(key => delete errors[key]);
  for (const field of fieldGroups[index] || []) {
    const value = String(form[field.key]).trim();
    if (field.required && !value) errors[field.key] = `${field.label} is required.`;
    else if (field.max && value.length > field.max) errors[field.key] = `Use no more than ${field.max} characters.`;
    else if (field.key === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) errors.email = 'Enter a valid email address.';
    else if (field.key === 'year_level' && (!Number.isInteger(Number(value)) || Number(value) < 1 || Number(value) > 6)) errors.year_level = 'Choose a whole year level from 1 to 6.';
    else if (field.key === 'program' && !programs.value.includes(value)) errors.program = 'Choose an available program.';
  }
  if (index === 2) validateDocuments();
  return Object.keys(errors).length === 0;
}
async function focusInvalid() { await nextTick(); formElement.value?.querySelector<HTMLElement>('[aria-invalid="true"]')?.focus(); }
async function goTo(index: number) { step.value = index; error.value = ''; await nextTick(); heading.value?.focus(); }
async function advance() {
  if (busy.value || reference.value) return;
  if (step.value < 3) { if (!validate(step.value)) { await focusInvalid(); return; } await goTo(step.value + 1); return; }
  for (let index = 0; index < 3; index++) { if (!validate(index)) { await goTo(index); await focusInvalid(); return; } }
  busy.value = true; error.value = '';
  try {
    const payload = new FormData(); Object.entries(form).forEach(([key, value]) => payload.append(key, String(value)));
    files.value.forEach(file => payload.append('documents[]', file));
    reference.value = (await portalApi.applyEnrollee(payload)).reference;
    await nextTick(); confirmation.value?.focus();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Submission failed. Please try again.';
    if (e instanceof ApiRequestError && Object.keys(e.errors).length) {
      for (const [key, messages] of Object.entries(e.errors)) errors[key.startsWith('documents') ? 'documents' : key] = messages[0] || 'Please check this field.';
      const key = Object.keys(errors)[0];
      step.value = key === 'documents' ? 2 : Math.max(0, fieldGroups.findIndex(group => group.some(field => field.key === key)));
    }
  } finally { busy.value = false; await focusInvalid(); }
}
onMounted(loadOptions);
</script>
<style scoped>
.field { display: block; width: 100%; margin-top: .4rem; padding: .7rem; border: 1px solid #cbd5e1; border-radius: .6rem; background: white; }
.field:focus { outline: 2px solid #047857; outline-offset: 2px; }
.field[aria-invalid="true"] { border-color: #b91c1c; }
button:focus-visible, a:focus-visible { outline: 2px solid #047857; outline-offset: 3px; }
</style>
