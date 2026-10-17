import { ref } from 'vue';

export type Theme = 'light' | 'dark';
const KEY = 'pinacall_theme';

function stored(): Theme | null {
  try {
    const v = localStorage.getItem(KEY);
    return v === 'light' || v === 'dark' ? v : null;
  } catch { return null; }
}

function preferred(): Theme {
  return stored() ?? (window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
}

export const theme = ref<Theme>('light');

function apply(t: Theme) {
  theme.value = t;
  document.documentElement.classList.toggle('dark', t === 'dark');
  document.documentElement.style.colorScheme = t;
}

export function initTheme() {
  apply(preferred());
  // Follow the OS only while the user hasn't chosen explicitly.
  window.matchMedia?.('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    if (!stored()) apply(e.matches ? 'dark' : 'light');
  });
}

export function toggleTheme() {
  const next: Theme = theme.value === 'dark' ? 'light' : 'dark';
  apply(next);
  try { localStorage.setItem(KEY, next); } catch { /* storage unavailable */ }
}
