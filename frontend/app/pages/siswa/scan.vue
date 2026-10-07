<script setup lang="ts">
useHead({ title: 'Scan Wajah · Absensi Digital' })

const router = useRouter()

const isScanning = ref(false)
const showFingerprintModal = ref(false)
const showHelp = ref(false)

// Jangan keluar aplikasi jika tidak ada history (mis. dibuka langsung via URL).
function goBack() {
  if (window.history.length > 1) router.back()
  else router.push('/siswa')
}

// Simulasi: setelah "mulai scan", wajah dikenali -> halaman berhasil.
function onMulaiScan() {
  if (isScanning.value) return
  isScanning.value = true
  // TODO: ganti dengan pemrosesan frame kamera / API pengenalan wajah.
  setTimeout(() => {
    isScanning.value = false
    router.push('/siswa/berhasil')
  }, 1800)
}

function onFingerprintSuccess() {
  showFingerprintModal.value = false
  router.push('/siswa/berhasil')
}
</script>

<template>
  <div class="flex min-h-dvh flex-col bg-white">
    <!-- ── Navbar atas ── -->
    <header
      class="sticky top-0 z-20 flex items-center justify-between border-b border-slate-100 bg-white px-4 py-3"
    >
      <button
        type="button"
        class="-ml-1 flex size-10 items-center justify-center rounded-full text-slate-700 transition active:bg-slate-100"
        aria-label="Kembali"
        @click="goBack"
      >
        <AppIcon name="chevron-left" :size="24" :stroke-width="2" />
      </button>

      <h1 class="text-base font-bold text-slate-900">Scan Wajah</h1>

      <button
        type="button"
        class="-mr-1 flex size-10 items-center justify-center rounded-full text-slate-500 transition active:bg-slate-100"
        aria-label="Bantuan"
        @click="showHelp = true"
      >
        <AppIcon name="help" :size="22" />
      </button>
    </header>

    <!-- ── Area kamera pemindai ── -->
    <main class="flex flex-1 flex-col px-5 pt-4">
      <div
        class="relative mx-2 h-[46vh] min-h-[320px] w-full flex-1 overflow-hidden rounded-3xl bg-slate-900 shadow-[0_8px_30px_-12px_rgba(2,6,23,0.45)]"
      >
        <!-- Simulasi feed kamera -->
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_38%,#1f2937_0%,#0b1220_72%)]" />

        <!-- Badge "face detected" -->
        <span
          class="absolute top-4 left-4 z-10 flex items-center gap-1.5 rounded-full bg-black/45 px-3 py-1.5 text-[11px] font-bold tracking-wide text-scan uppercase backdrop-blur-sm"
        >
          <span class="size-1.5 animate-pulse rounded-full bg-scan" />
          Face Detected
        </span>

        <!-- Siluet wajah (placeholder kamera) -->
        <svg
          viewBox="0 0 100 100"
          class="absolute top-1/2 left-1/2 h-[60%] -translate-x-1/2 -translate-y-1/2 text-white/10"
          fill="currentColor"
          aria-hidden="true"
        >
          <circle cx="50" cy="38" r="19" />
          <path d="M50 62c-16 0-29 12-31 27h62c-2-15-15-27-31-27z" />
        </svg>

        <!-- Bingkai pembidik wajah: kotak transparan + pinggiran sudut tebal -->
        <div
          class="absolute top-1/2 left-1/2 aspect-square w-[64%] max-w-[240px] -translate-x-1/2 -translate-y-1/2 border border-dashed border-scan/25"
        >
          <span class="absolute -top-0.5 -left-0.5 size-9 rounded-tl-xl border-t-[5px] border-l-[5px] border-scan" />
          <span class="absolute -top-0.5 -right-0.5 size-9 rounded-tr-xl border-t-[5px] border-r-[5px] border-scan" />
          <span class="absolute -bottom-0.5 -left-0.5 size-9 rounded-bl-xl border-b-[5px] border-l-[5px] border-scan" />
          <span class="absolute -right-0.5 -bottom-0.5 size-9 rounded-br-xl border-r-[5px] border-b-[5px] border-scan" />
        </div>

        <!-- Garis pemindai berjalan -->
        <div class="pointer-events-none absolute inset-x-0 top-0 h-full overflow-hidden">
          <div
            class="h-16 w-full bg-gradient-to-b from-transparent via-scan/15 to-scan/40"
            :class="isScanning ? 'animate-[scan-line_0.9s_ease-in-out_infinite]' : 'animate-[scan-line_2.4s_ease-in-out_infinite]'"
          />
        </div>
      </div>

      <!-- ── Instruksi ── -->
      <p class="mt-5 text-center text-sm font-semibold text-slate-800">
        Posisikan wajah Anda di dalam area
      </p>
      <p class="mt-1.5 flex items-center justify-center gap-1.5 text-xs text-slate-400">
        <AppIcon name="user-scan" :size="15" />
        Siap memindai (face motion)
      </p>

      <!-- ── Tombol mulai scan ── -->
      <button type="button" class="btn-primary mt-5" :disabled="isScanning" @click="onMulaiScan">
        <AppIcon name="scan-face" :size="20" />
        <span>{{ isScanning ? 'Memindai…' : 'Mulai Scan' }}</span>
      </button>

      <!-- ── Opsi alternatif ── -->
      <button type="button" class="btn-outline mb-6 mt-3" @click="showFingerprintModal = true">
        <AppIcon name="fingerprint" :size="20" class="text-brand-700" />
        <span class="text-sm">Sistem bermasalah? Gunakan fingerprint</span>
      </button>
    </main>

    <SiswaFingerprintmodal v-model:open="showFingerprintModal" @success="onFingerprintSuccess" />

    <!-- Panel bantuan (ikon tanda tanya di navbar) -->
    <Teleport to="body">
      <div
        v-if="showHelp"
        class="fixed inset-0 z-50 flex items-end bg-black/60 p-4"
        role="dialog"
        aria-modal="true"
        @click.self="showHelp = false"
      >
        <div class="w-full rounded-t-3xl bg-white p-6 pb-[calc(env(safe-area-inset-bottom)+1.5rem)]">
          <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-slate-200" />
          <h2 class="text-base font-bold text-slate-900">Tips Scan Wajah</h2>
          <ul class="mt-3 space-y-2.5 text-sm text-slate-600">
            <li v-for="tip in ['Pastikan wajah berada di dalam bingkai hijau', 'Gunakan pencahayaan terang di sekitar Anda', 'Lepas masker dan kacamata']" :key="tip" class="flex gap-2.5">
              <AppIcon name="check" :size="18" class="mt-0.5 text-brand-600" :stroke-width="2.4" />
              <span>{{ tip }}</span>
            </li>
          </ul>
          <button type="button" class="btn-primary mt-5" @click="showHelp = false">Mengerti</button>
        </div>
      </div>
    </Teleport>
  </div>
</template>
