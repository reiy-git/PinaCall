<template>
  <section class="mx-auto max-w-3xl overflow-hidden rounded-3xl border border-emerald-900/10 bg-white shadow-xl" aria-labelledby="application-title">
    <header class="border-b border-slate-200 px-6 py-4 sm:px-8">
      <p class="text-xs font-bold uppercase tracking-wider text-emerald-800">PinaCall admissions</p>
      <h1 id="application-title" class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Student application</h1>
      <p v-if="!reference" class="mt-1 text-sm text-slate-600">Complete your details and upload supporting documents before Registrar review. An account is issued only after approval.</p>
    </header>

    <div class="px-6 py-5 sm:px-8 sm:py-6">
      <div v-if="reference" ref="confirmation" role="status" tabindex="-1" class="rounded-2xl border border-emerald-900/10 bg-emerald-50/70 p-6 text-emerald-950 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700">
        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-lime text-navy" aria-hidden="true">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-10" /></svg>
        </span>
        <h2 class="mt-4 text-xl font-bold tracking-tight text-emerald-900">Application received</h2>
        <p class="mt-3 text-sm">Your application reference is <strong class="break-all">{{ reference }}</strong>.</p>
        <p class="mt-3 text-sm leading-relaxed text-emerald-900/80">Your application is pending Registrar review. Submitting did not create an account. If approved, you will receive account instructions by email, including how to set your password.</p>
        <a href="/" class="mt-5 inline-block rounded-2xl bg-lime px-5 py-3 text-sm font-bold text-navy shadow-sm transition hover:brightness-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2">Return home / sign in</a>
      </div>

      <template v-else>
        <ol aria-label="Application steps" class="mb-6 grid grid-cols-2 gap-2 sm:grid-cols-4">
          <li v-for="(label, index) in steps" :key="label" :aria-current="step === index ? 'step' : undefined" class="flex items-start gap-3 rounded-2xl border p-3 text-sm transition" :class="step === index ? 'border-emerald-800 bg-emerald-50 font-bold text-emerald-900' : 'border-slate-200 text-slate-600'">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold" :class="index < step ? 'bg-lime text-navy' : step === index ? 'bg-navy text-lime' : 'bg-slate-100 text-slate-600'" aria-hidden="true">{{ index < step ? '✓' : index + 1 }}</span>
            <span class="min-w-0"><span class="block text-xs font-semibold text-slate-500">Step {{ index + 1 }} {{ index < step ? '— Complete' : '' }}</span>{{ label }}</span>
          </li>
        </ol>

        <p v-if="optionsError" role="alert" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">{{ optionsError }} <button type="button" class="font-semibold underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700" @click="loadOptions">Retry</button></p>

        <form ref="formElement" novalidate @submit.prevent="advance" :aria-busy="busy">
          <h2 ref="heading" tabindex="-1" class="mb-4 text-xl font-bold tracking-tight text-slate-900 focus:outline-none">{{ steps[step] }}</h2>
          <fieldset :disabled="busy" class="grid gap-5">
            <template v-if="step < 2">
              <div v-for="field in visibleFields" :key="field.key">
                <label :for="field.key" class="mb-1.5 block text-sm font-semibold text-slate-800">{{ field.label }}{{ field.required ? ' *' : ' (optional)' }}</label>
                <select v-if="field.key === 'program' || field.key === 'student_type'" :id="field.key" v-model="form[field.key]" :class="fieldClass" :aria-required="field.required" :aria-invalid="!!errors[field.key]" :aria-describedby="`${field.key}-error`">
                  <option value="" disabled>Select an option</option>
                  <option v-for="option in field.key === 'program' ? programs : categories" :key="option" :value="option">{{ categoryLabels[option] || option }}</option>
                </select>
                <textarea v-else-if="field.key === 'educational_background'" :id="field.key" v-model.trim="form[field.key]" rows="4" maxlength="5000" :class="fieldClass" :aria-required="field.required" :aria-invalid="!!errors[field.key]" :aria-describedby="`${field.key}-error`"></textarea>
                <input v-else :id="field.key" v-model.trim="form[field.key]" :type="field.type || 'text'" :autocomplete="field.autocomplete" :maxlength="field.max" :min="field.key === 'year_level' ? 1 : undefined" :max="field.key === 'year_level' ? 6 : undefined" :class="fieldClass" :aria-required="field.required" :aria-invalid="!!errors[field.key]" :aria-describedby="`${field.key}-error`">
                <p :id="`${field.key}-error`" class="mt-1 text-sm text-red-700">{{ errors[field.key] }}</p>
              </div>
            </template>

            <div v-if="step === 2">
              <p class="mb-3 text-sm text-slate-600">{{ documentGuidance }}</p>
              <label for="documents" class="mb-1.5 block text-sm font-semibold text-slate-800">Supporting documents *</label>
              <p id="document-help" class="mb-2 text-sm text-slate-600">Choose 1–5 PDF, JPEG or PNG files, up to 5 MB each. Add files below; remove a file before adding its replacement.</p>
              <input id="documents" aria-required="true" type="file" accept=".pdf,.jpg,.jpeg,.png" multiple :class="[fieldClass, 'file:mr-3 file:cursor-pointer file:rounded-xl file:border-0 file:bg-lime file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-navy']" :aria-invalid="!!errors.documents" aria-describedby="document-help documents-error" @change="selectFiles">
              <p id="documents-error" class="mt-2 text-sm text-red-700">{{ errors.documents }}</p>
              <ul class="mt-4 space-y-2">
                <li v-for="(file, index) in files" :key="index" class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3 text-sm">
                  <span class="min-w-0 break-all text-slate-800">{{ file.name }} <span class="text-slate-500">({{ formatSize(file.size) }})</span></span>
                  <button type="button" :aria-label="`Remove ${file.name}`" class="shrink-0 rounded-lg px-1 font-semibold text-red-700 underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700" @click="removeFile(index)">Remove</button>
                </li>
              </ul>
            </div>

            <div v-if="step === 3" class="space-y-5">
              <p class="text-sm text-slate-600">Review your details. Submitting sends an application for review and does not create an account.</p>
              <div v-for="(group, index) in fieldGroups" :key="index" class="rounded-2xl border border-slate-200 p-4">
                <div class="mb-3 flex items-center justify-between gap-3"><h3 class="font-bold text-slate-900">{{ steps[index] }}</h3><button type="button" class="rounded-lg px-1 text-sm font-semibold text-emerald-800 underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700" :aria-label="`Edit ${steps[index]}`" @click="goTo(index)">Edit</button></div>
                <dl class="space-y-3"><div v-for="field in group" :key="field.key"><dt class="text-xs font-semibold text-slate-500">{{ field.label }}</dt><dd class="whitespace-pre-wrap break-words text-sm text-slate-900">{{ categoryLabels[String(form[field.key])] || form[field.key] || 'Not provided' }}</dd></div></dl>
              </div>
              <div class="rounded-2xl border border-slate-200 p-4"><div class="flex items-center justify-between gap-3"><h3 class="font-bold text-slate-900">Documents</h3><button type="button" class="rounded-lg px-1 text-sm font-semibold text-emerald-800 underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700" @click="goTo(2)">Edit documents</button></div><ul class="mt-3 space-y-2 text-sm text-slate-900"><li v-for="(file, index) in files" :key="index" class="break-all">{{ file.name }} ({{ formatSize(file.size) }})</li></ul></div>
            </div>

            <p v-if="error" role="alert" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">{{ error }}</p>
            <div class="flex flex-wrap justify-between gap-3 border-t border-slate-200 pt-5">
              <button v-if="step > 0" type="button" class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2" @click="goTo(step - 1)">Previous</button>
              <button type="submit" :disabled="busy || !programs.length" class="ml-auto rounded-2xl bg-lime px-5 py-3 text-sm font-bold text-navy shadow-sm transition hover:brightness-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">{{ busy ? 'Submitting…' : step === 3 ? 'Submit application' : 'Continue' }}</button>
            </div>
          </fieldset>
        </form>
        <a href="/" class="mt-6 inline-block rounded-lg text-sm font-semibold text-emerald-800 underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700">Return home / sign in</a>
      </template>
    </div>
  </section>
</template>
<script setup lang="ts">
import { computed, nextTick, onMounted, reactive, ref } from 'vue';
import { ApiRequestError, portalApi } from '../../services/api';
// Shared control styling: same tokens as the sign-in panel (rounded-2xl, navy focus ring, theme-aware via the slate/emerald/red remaps)
const fieldClass = 'block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-emerald-800 focus:ring-2 focus:ring-emerald-800/15 aria-[invalid=true]:border-red-700 aria-[invalid=true]:ring-red-700/15';
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