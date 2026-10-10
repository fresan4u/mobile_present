<script setup lang="ts">
useHead({ title: 'Scan Wajah · Absensi Digital' })

const router = useRouter()

type CameraStatus = 'starting' | 'ready' | 'denied' | 'not-found' | 'in-use' | 'insecure' | 'unsupported' | 'error'

/** Status scan: dibuka/ditutup oleh petugas (lihat composables/useScanStatus.ts). */
const { scanActive, scanLoading, refreshScanStatus } = useScanStatus()
const statusChecked = ref(false)

const isScanning = ref(false)
const showFingerprintModal = ref(false)
const showHelp = ref(false)

const cameraStatus = ref<CameraStatus>('starting')
const videoEl = ref<HTMLVideoElement | null>(null)
let stream: MediaStream | null = null
let scanTimer: ReturnType<typeof setTimeout> | undefined

// Foto terakhir hasil scan (data URL). TODO: kirim ke API saat backend tersedia.
const scanPhoto = useState<string | null>('scanPhoto', () => null)

const cameraMessage = computed(() => {
  switch (cameraStatus.value) {
    case 'denied':
      return 'Izin kamera ditolak. Aktifkan izin kamera untuk situs ini di pengaturan browser, lalu coba lagi.'
    case 'not-found':
      return 'Kamera tidak ditemukan di perangkat ini.'
    case 'in-use':
      return 'Kamera sedang dipakai aplikasi lain. Tutup aplikasi tersebut, lalu coba lagi.'
    case 'insecure':
      return 'Kamera hanya bisa dipakai lewat koneksi aman (HTTPS) atau localhost.'
    case 'unsupported':
      return 'Browser ini tidak mendukung akses kamera.'
    case 'error':
      return 'Kamera gagal dibuka. Coba lagi.'
    default:
      return ''
  }
})

const statusText = computed(() => {
  if (isScanning.value) return 'Memindai wajah…'
  if (cameraStatus.value === 'starting') return 'Membuka kamera…'
  if (cameraStatus.value === 'ready') return 'Siap memindai'
  return 'Kamera belum aktif'
})

async function startCamera() {
  if (!import.meta.client || !scanActive.value) return
  stopCamera()
  cameraStatus.value = 'starting'

  if (!window.isSecureContext) {
    cameraStatus.value = 'insecure'
    return
  }
  if (!navigator.mediaDevices?.getUserMedia) {
    cameraStatus.value = 'unsupported'
    return
  }

  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: {
        facingMode: 'user',
        width: { ideal: 1280 },
        height: { ideal: 960 },
      },
      audio: false,
    })

    const video = videoEl.value
    if (!video || !scanActive.value) {
      stopCamera()
      return
    }
    video.srcObject = stream
    await video.play()
    cameraStatus.value = 'ready'
  } catch (err) {
    stopCamera()
    const name = (err as DOMException)?.name
    if (name === 'NotAllowedError' || name === 'SecurityError') cameraStatus.value = 'denied'
    else if (name === 'NotFoundError' || name === 'OverconstrainedError') cameraStatus.value = 'not-found'
    else if (name === 'NotReadableError' || name === 'AbortError') cameraStatus.value = 'in-use'
    else cameraStatus.value = 'error'
  }
}

function stopCamera() {
  stream?.getTracks().forEach((track) => track.stop())
  stream = null
  if (videoEl.value) videoEl.value.srcObject = null
}

// Ambil satu frame dari kamera sebagai gambar JPEG (data URL).
function captureFrame(): string | null {
  const video = videoEl.value
  if (!video || !video.videoWidth) return null
  const canvas = document.createElement('canvas')
  canvas.width = video.videoWidth
  canvas.height = video.videoHeight
  const ctx = canvas.getContext('2d')
  if (!ctx) return null
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height)
  return canvas.toDataURL('image/jpeg', 0.85)
}

// Jangan keluar aplikasi jika tidak ada history (mis. dibuka langsung via URL).
function goBack() {
  if (window.history.length > 1) router.back()
  else router.push('/siswa')
}

function onMulaiScan() {
  if (!scanActive.value || isScanning.value || cameraStatus.value !== 'ready') return
  isScanning.value = true
  scanPhoto.value = captureFrame()
  // TODO: kirim scanPhoto ke API pengenalan wajah (backend belum tersedia).
  scanTimer = setTimeout(() => {
    isScanning.value = false
    stopCamera()
    router.push('/siswa/berhasil')
  }, 1800)
}

function onFingerprintSuccess() {
  if (!scanActive.value) {
    showFingerprintModal.value = false
    return
  }
  showFingerprintModal.value = false
  stopCamera()
  router.push('/siswa/berhasil')
}

// Tombol "Cek Lagi" di layar absensi belum dibuka.
async function onCekLagi() {
  await refreshScanStatus()
  if (scanActive.value) {
    await nextTick()
    startCamera()
  }
}

// Bila petugas menutup scan saat halaman terbuka: matikan kamera dan tutup modal.
watch(scanActive, (aktif) => {
  if (!aktif) {
    clearTimeout(scanTimer)
    isScanning.value = false
    showFingerprintModal.value = false
    stopCamera()
  }
})

onMounted(async () => {
  await refreshScanStatus()
  statusChecked.value = true
  if (scanActive.value) startCamera()
})

onBeforeUnmount(() => {
  clearTimeout(scanTimer)
  stopCamera()
})
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

    <!-- ── Layar: absensi belum dibuka petugas ── -->
    <main
      v-if="statusChecked && !scanActive"
      class="flex flex-1 flex-col items-center justify-center px-8 text-center"
    >
      <span class="flex size-20 items-center justify-center rounded-full bg-slate-100 text-slate-400">
        <AppIcon name="lock" :size="36" />
      </span>
      <h2 class="mt-6 text-lg font-bold text-slate-900">Absensi belum dibuka</h2>
      <p class="mt-2 max-w-[18rem] text-sm leading-relaxed text-slate-500">
        Petugas belum mengaktifkan scan. Silakan tunggu, atau hubungi petugas di sekolah.
      </p>

      <button type="button" class="btn-primary mt-8" :disabled="scanLoading" @click="onCekLagi">
        <span>{{ scanLoading ? 'Memeriksa…' : 'Cek Lagi' }}</span>
      </button>
      <NuxtLink to="/siswa" class="btn-outline mt-3 text-center">
        <span class="text-sm">Kembali ke Beranda</span>
      </NuxtLink>
    </main>

    <!-- ── Area kamera pemindai ── -->
    <main v-else class="flex flex-1 flex-col px-5 pt-4">
      <div
        class="relative mx-2 h-[46vh] min-h-[320px] w-full flex-1 overflow-hidden rounded-3xl bg-slate-900 shadow-[0_8px_30px_-12px_rgba(2,6,23,0.45)]"
      >
        <!-- Latar saat kamera belum aktif -->
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_38%,#1f2937_0%,#0b1220_72%)]" />

        <!-- Feed kamera (dicerminkan agar terasa seperti cermin) -->
        <video
          ref="videoEl"
          class="absolute inset-0 size-full scale-x-[-1] object-cover transition-opacity duration-300"
          :class="cameraStatus === 'ready' ? 'opacity-100' : 'opacity-0'"
          autoplay
          playsinline
          muted
        />

        <!-- Badge status kamera -->
        <span
          v-if="cameraStatus === 'ready'"
          class="absolute top-4 left-4 z-10 flex items-center gap-1.5 rounded-full bg-black/45 px-3 py-1.5 text-[11px] font-bold tracking-wide text-scan uppercase backdrop-blur-sm"
        >
          <span class="size-1.5 animate-pulse rounded-full bg-scan" />
          Kamera Aktif
        </span>

        <!-- Siluet wajah (hanya saat kamera belum aktif) -->
        <svg
          v-if="cameraStatus !== 'ready'"
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

        <!-- Garis pemindai berjalan (hanya saat kamera aktif) -->
        <div v-if="cameraStatus === 'ready'" class="pointer-events-none absolute inset-x-0 top-0 h-full overflow-hidden">
          <div
            class="h-16 w-full bg-gradient-to-b from-transparent via-scan/15 to-scan/40"
            :class="isScanning ? 'animate-[scan-line_0.9s_ease-in-out_infinite]' : 'animate-[scan-line_2.4s_ease-in-out_infinite]'"
          />
        </div>

        <!-- Pesan bila kamera gagal dibuka -->
        <div
          v-if="cameraMessage"
          class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-4 bg-slate-900/85 px-6 text-center"
        >
          <p class="text-sm leading-relaxed text-white/85">{{ cameraMessage }}</p>
          <button
            v-if="cameraStatus !== 'insecure' && cameraStatus !== 'unsupported'"
            type="button"
            class="rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 transition active:scale-95"
            @click="startCamera"
          >
            Coba Lagi
          </button>
        </div>
      </div>

      <!-- ── Instruksi ── -->
      <p class="mt-5 text-center text-sm font-semibold text-slate-800">
        Posisikan wajah Anda di dalam area
      </p>
      <p class="mt-1.5 flex items-center justify-center gap-1.5 text-xs text-slate-400">
        <AppIcon name="user-scan" :size="15" />
        {{ statusText }}
      </p>

      <!-- ── Tombol mulai scan ── -->
      <button
        type="button"
        class="btn-primary mt-5"
        :disabled="isScanning || cameraStatus !== 'ready'"
        @click="onMulaiScan"
      >
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