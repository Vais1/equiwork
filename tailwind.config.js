/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    './*.php',
    './admin/**/*.php',
    './actions/**/*.php',
    './includes/**/*.php',
    './assets/js/**/*.js'
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Segoe UI', 'Tahoma', 'Geneva', 'Verdana', 'sans-serif'],
        heading: ['Trebuchet MS', 'Segoe UI', 'Tahoma', 'sans-serif']
      },
      colors: {
        bg: 'rgb(var(--color-bg) / <alpha-value>)',
        surface: 'rgb(var(--color-surface) / <alpha-value>)',
        border: 'rgb(var(--color-border) / <alpha-value>)',
        text: 'rgb(var(--color-text) / <alpha-value>)',
        muted: 'rgb(var(--color-muted) / <alpha-value>)',
        accent: 'rgb(var(--color-accent) / <alpha-value>)',
        'accent-hover': 'rgb(var(--color-accent-hover) / <alpha-value>)'
      }
    }
  }
};
