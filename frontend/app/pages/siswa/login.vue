<script setup lang="ts">
useHead({ title: 'Login Siswa · Absensi Digital' })

const router = useRouter()

const form = reactive({ identitas: '', password: '' })
const showPassword = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')

const bisaSubmit = computed(
  () => form.identitas.trim().length > 0 && form.password.length > 0,
)

function onSubmit() {
  errorMessage.value = ''
  if (!bisaSubmit.value || isLoading.value) return

  isLoading.value = true
  // TODO: ganti dengan panggilan API auth (backend belum tersedia).
  setTimeout(() => router.push('/siswa'), 500)
}
</script>

<template>
  <div class="min-h-dvh bg-white">
    <!-- ── Area hijau (dua warna: hijau tua di atas, putih di bawah) ── -->
    <div class="bg-brand-700 px-6 pt-[calc(env(safe-area-inset-top)+2.5rem)] pb-28">
      <!-- Header: ikon scan wajah + "absensi digital" -->
      <div class="flex items-center gap-2.5">
        <span
          class="flex size-9 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20 ring-inset"
        >
          <AppIcon name="scan-face" :size="19" class="text-white" />
        </span>
        <span class="text-sm font-semibold tracking-wide text-white/90 uppercase">
          Absensi Digital
        </span>
      </div>

      <!-- Sapaan -->
      <h1 class="mt-8 text-3xl font-extrabold tracking-tight text-white">Selamat Datang</h1>
      <p class="mt-2 max-w-[18rem] text-sm leading-relaxed text-white/70">
        Silakan masuk untuk melakukan absensi harian menggunakan pemindaian wajah.
      </p>
    </div>

    <!-- ── Kartu form yang menimpa area hijau & putih (-mt) ── -->
    <div class="-mt-20 px-5 pb-10">
      <div class="rounded-2xl bg-white p-6 shadow-lg shadow-slate-900/10">
        <h2 class="text-lg font-bold text-slate-900">Login Siswa</h2>
        <p class="mt-1 text-sm text-slate-400">Gunakan akun siswa yang terdaftar di sekolah.</p>

        <form class="mt-6 space-y-4" novalidate @submit.prevent="onSubmit">
          <!-- Email / NIS -->
          <div>
            <label for="identitas" class="mb-1.5 block text-sm font-semibold text-slate-700">
              Email / NIS
            </label>
            <div class="relative">
              <AppIcon
                name="mail"
                :size="20"
                class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-slate-400"
              />
              <input
                id="identitas"
                v-model="form.identitas"
                type="text"
                inputmode="text"
                autocomplete="username"
                autocapitalize="none"
                spellcheck="false"
                placeholder="email atau nis siswa"
                class="field"
              />
            </div>
          </div>

          <!-- Password -->
          <div>
            <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">
              Password
            </label>
            <div class="relative">
              <AppIcon
                name="lock"
                :size="20"
                class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-slate-400"
              />
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                placeholder="••••••••"
                class="field pr-12"
              />
              <!-- Tombol lihat/sembunyikan password (ikon di dalam input) -->
              <button
                type="button"
                class="absolute top-1/2 right-2 flex size-9 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition active:bg-slate-100"
                :aria-label="showPassword ? 'Sembunyikan password' : 'Lihat password'"
                @click="showPassword = !showPassword"
              >
                <AppIcon :name="showPassword ? 'eye-off' : 'eye'" :size="20" />
              </button>
            </div>
            <p class="mt-2 text-right">
              <!-- TODO: arahkan ke halaman lupa password -->
              <a
                href="#"
                class="text-sm font-semibold text-brand-700 active:text-brand-800"
                @click.prevent
              >
                Lupa password?
              </a>
            </p>
          </div>

          <p v-if="errorMessage" class="text-sm font-medium text-danger-600">
            {{ errorMessage }}
          </p>

          <!-- Tombol masuk -->
          <button type="submit" class="btn-primary mt-2" :disabled="isLoading">
            <span>{{ isLoading ? 'Memproses…' : 'Masuk' }}</span>
            <AppIcon v-if="!isLoading" name="arrow-right" :size="20" />
            <span
              v-else
              class="size-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
            />
          </button>
        </form>
      </div>

      <p class="mt-8 text-center text-xs text-slate-400">
        Belum bisa masuk? Hubungi admin sekolah.
      </p>
    </div>
  </div>
</template>
