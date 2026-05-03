/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: [
    "./templates/**/*.{html,twig}",
    "./public/build/assets/**/*.js",
    "./resources/js/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#fef3c7',
          100: '#fde68a',
          200: '#fcd34d',
          300: '#fbbf24',
          400: '#f59e0b',
          500: '#f97316', // Orange primary
          600: '#ea580c',
          700: '#c2410c',
          800: '#9a3412',
          900: '#7c2d12',
        }
      }
    },
  },
  plugins: [],
}