import tailwindcss from '@tailwindcss/vite'

// Alamat backend Laravel. Bawaan: server lokal `php artisan serve`.
// Untuk server lain, jalankan Nuxt dengan variabel BACKEND_URL.
const backendUrl = process.env.BACKEND_URL ?? 'http://127.0.0.1:8000'

// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  css: ['~/assets/css/main.css'],

  vite: {
    plugins: [tailwindcss()],
  },

  // Semua permintaan /api/... diteruskan ke backend Laravel, sehingga browser
  // hanya berhubungan dengan satu alamat dan tidak butuh pengaturan CORS.
  routeRules: {
    '/api/**': { proxy: `${backendUrl}/api/**` },
  },

  app: {
    head: {
      title: 'Absensi Digital',
      meta: [
        { name: 'viewport', content: 'width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover' },
        { name: 'theme-color', content: '#005a3f' },
        { name: 'description', content: 'Aplikasi absensi siswa berbasis face recognition & fingerprint.' },
      ],
      link: [
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
        },
      ],
    },
  },
})