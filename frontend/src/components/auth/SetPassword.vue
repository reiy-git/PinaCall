<template>
  <section class="mx-auto max-w-xl rounded-2xl bg-white p-6 shadow-sm">
    <h1 class="text-2xl font-bold">Set your student password</h1>
    <p class="my-4">Choose your own password before accessing university services. You will sign in again afterward.</p>
    <form @submit.prevent="submit" class="grid gap-4">
      <label>Temporary password<input v-model="form.current_password" type="password" autocomplete="current-password" required maxlength="255" class="field"></label>
      <label>New password<input v-model="form.password" type="password" autocomplete="new-password" required minlength="12" maxlength="255" class="field"></label>
      <label>Confirm new password<input v-model="form.password_confirmation" type="password" autocomplete="new-password" required minlength="12" maxlength="255" class="field"></label>
      <p v-if="error" role="alert" class="text-red-700">{{ error }}</p>
      <button :disabled="busy" class="rounded-xl bg-emerald-900 p-3 text-white disabled:opacity-50">{{ busy ? 'Saving…' : 'Set password' }}</button>
      <button type="button" @click="signOut" class="underline">Sign out</button>
    </form>
  </section>
</template>
<script setup lang="ts">
import { reactive, ref } from 'vue';
import { portalApi } from '../../services/api';
const form = reactive({ current_password: '', password: '', password_confirmation: '' });
const busy = ref(false); const error = ref('');
async function signOut() { await portalApi.logout(); window.location.assign('/'); }
async function submit() {
  error.value = '';
  if (form.password !== form.password_confirmation) { error.value = 'Passwords do not match.'; return; }
  busy.value = true;
  try { await portalApi.firstLoginReset(form); } catch (e) { error.value = e instanceof Error ? e.message : 'Password change failed.'; }
  finally { busy.value = false; }
}
</script>
<style scoped>.field { display: block; width: 100%; margin-top: .4rem; padding: .7rem; border: 1px solid #cbd5e1; border-radius: .6rem; }</style>
