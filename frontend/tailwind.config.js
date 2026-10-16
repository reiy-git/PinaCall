/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: [
          'Inter var',
          'Inter',
          'system-ui',
          '-apple-system',
          'BlinkMacSystemFont',
          'Segoe UI',
          'Roboto',
          'Helvetica Neue',
          'Arial',
          'sans-serif',
          'Apple Color Emoji',
          'Segoe UI Emoji',
        ],
        display: [
          'Cal Sans',
          'Inter var',
          'Inter',
          'system-ui',
          '-apple-system',
          'sans-serif',
        ],
      },
      fontSize: {
        'xs': ['0.75rem', { lineHeight: '1.5', letterSpacing: '0.01em' }],
        'sm': ['0.875rem', { lineHeight: '1.5714', letterSpacing: '0.005em' }],
        'base': ['1rem', { lineHeight: '1.6', letterSpacing: '0' }],
        'lg': ['1.125rem', { lineHeight: '1.5556', letterSpacing: '-0.005em' }],
        'xl': ['1.25rem', { lineHeight: '1.5', letterSpacing: '-0.01em' }],
        '2xl': ['1.5rem', { lineHeight: '1.4167', letterSpacing: '-0.015em' }],
        '3xl': ['1.875rem', { lineHeight: '1.3333', letterSpacing: '-0.02em' }],
        '4xl': ['2.25rem', { lineHeight: '1.2778', letterSpacing: '-0.025em' }],
        '5xl': ['3rem', { lineHeight: '1.1667', letterSpacing: '-0.03em' }],
        '6xl': ['3.75rem', { lineHeight: '1.1333', letterSpacing: '-0.035em' }],
      },
      letterSpacing: {
        tighter: '-0.04em',
        tight: '-0.02em',
        normal: '0',
        wide: '0.02em',
        wider: '0.05em',
        widest: '0.1em',
        'ultra-wide': '0.2em',
      },
    },
  },
  plugins: [],
}
