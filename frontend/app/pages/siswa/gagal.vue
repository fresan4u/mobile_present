<script setup lang="ts">
useHead({ title: 'Wajah Belum Dikenali · Absensi Digital' })

const router = useRouter()

const showFingerprintModal = ref(false)

function onFingerprintSuccess() {
  showFingerprintModal.value = false
  router.push('/siswa/berhasil')
}

/** Checklist perbaikan agar wajah terbaca */
const saran = [
  { judul: 'Cari tempat terang', detail: 'Pastikan pencahayaan cukup, hindari cahaya dari belakang.' },
  { judul: 'Hadapkan wajah lurus', detail: 'Lepas masker/kacamata, lalu posisikan wajah Anda di tengah bingkai.' },
]
</script>

<template>
  <div class="flex min-h-dvh flex-col bg-slate-50">
    <!-- ── Ikon + judul ── -->
    <header class="px-6 pt-[calc(env(safe-area-inset-top)+3rem)] pb-8 text-center">
      <div
        class="mx-auto flex size-24 items-center justify-center rounded-full bg-danger-50 text-danger-500"
      >
        <AppIcon name="user-scan" :size="48" :stroke-width="1.8" />
      </div>
      <h1 class="mt-6 text-2xl font-extrabold tracking-tight text-slate-900">
        Wajah Belum Dikenali
      </h1>
      <p class="mt-2 text-sm text-slate-500">
        Sistem tidak dapat mengenali wajah Anda. Coba langkah berikut.
      </p>
    </header>

    <!-- ── Kotak instruksi perbaikan ── -->
    <main class="flex-1 px-5">
      <section class="rounded-2xl bg-white p-5 shadow-sm">
        <h2 class="flex items-center gap-2 text-sm font-bold tracking-wide text-slate-900 uppercase">
          <AppIcon name="alert" :size="18" class="text-danger-500" />
          Apa yang perlu dilakukan
        </h2>

        <ul class="mt-4 space-y-3">
          <li
            v-for="(item, i) in saran"
            :key="item.judul"
            class="flex gap-3 rounded-xl bg-slate-50 p-4"
          >
            <span
              class="flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-700 text-[11px] font-bold text-white"
            >
              {{ i + 1 }}
            </span>
            <div class="min-w-0">
              <p class="text-sm font-semibold text-slate-800">{{ item.judul }}</p>
              <p class="mt-0.5 text-xs leading-relaxed text-slate-500">{{ item.detail }}</p>
            </div>
          </li>
        </ul>
      </section>
    </main>

    <!-- ── Dua tombol vertikal ── -->
    <footer class="space-y-3 px-5 pt-6 pb-[calc(env(safe-area-inset-bottom)+1.5rem)]">
      <button type="button" class="btn-primary" @click="router.push('/siswa/scan')">
        <AppIcon name="scan-face" :size="20" />
        <span>Scan Lagi</span>
      </button>
      <button type="button" class="btn-outline" @click="showFingerprintModal = true">
        <AppIcon name="fingerprint" :size="20" class="text-brand-700" />
        <span>Gunakan Fingerprint</span>
      </button>
    </footer>

    <SiswaFingerprintmodal v-model:open="showFingerprintModal" @success="onFingerprintSuccess" />
  </div>
</template>
