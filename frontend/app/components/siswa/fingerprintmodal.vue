<script setup lang="ts">
const emit = defineEmits<{ 'update:open': [value: boolean]; success: [] }>()

const props = defineProps<{ open: boolean }>()

type Status = 'idle' | 'scanning' | 'success' | 'error'
const status = ref<Status>('idle')
const errorMessage = ref('')
const hasStoredCredential = ref(false)

// ID kunci perangkat disimpan di browser. TODO: pindahkan ke backend saat tersedia.
const CREDENTIAL_KEY = 'presensi_fp_credential'

let timer: ReturnType<typeof setTimeout> | undefined
let runId = 0

function toBase64Url(buffer: ArrayBuffer): string {
  const bytes = new Uint8Array(buffer)
  let binary = ''
  bytes.forEach((b) => (binary += String.fromCharCode(b)))
  return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '')
}

function fromBase64Url(value: string): Uint8Array {
  const base64 = value.replace(/-/g, '+').replace(/_/g, '/')
  const padded = base64 + '='.repeat((4 - (base64.length % 4)) % 4)
  const binary = atob(padded)
  const bytes = new Uint8Array(binary.length)
  for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i)
  return bytes
}

function randomBytes(length: number): Uint8Array {
  const bytes = new Uint8Array(length)
  crypto.getRandomValues(bytes)
  return bytes
}

function fail(message: string) {
  status.value = 'error'
  errorMessage.value = message
}

async function verifyFingerprint() {
  const myRun = ++runId
  status.value = 'scanning'
  errorMessage.value = ''

  const host = window.location.hostname
  const isIpAddress = /^\d{1,3}(\.\d{1,3}){3}$/.test(host) || host.includes(':')

  if (!window.isSecureContext || !window.PublicKeyCredential) {
    return fail('Browser ini tidak mendukung fingerprint, atau halaman tidak dibuka lewat HTTPS.')
  }
  if (isIpAddress) {
    return fail('Fingerprint tidak bisa dipakai lewat alamat IP. Buka lewat alamat domain HTTPS (misalnya tunnel).')
  }

  try {
    const available = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable()
    if (!available) {
      return fail('Perangkat ini belum mengaktifkan fingerprint atau kunci layar. Aktifkan dulu di pengaturan HP.')
    }

    // TODO: challenge seharusnya berasal dari backend agar bisa diverifikasi server.
    const challenge = randomBytes(32)
    const storedId = localStorage.getItem(CREDENTIAL_KEY)
    hasStoredCredential.value = !!storedId

    if (storedId) {
      await navigator.credentials.get({
        publicKey: {
          challenge,
          rpId: host,
          allowCredentials: [
            { type: 'public-key', id: fromBase64Url(storedId), transports: ['internal'] },
          ],
          userVerification: 'required',
          timeout: 60000,
        },
      })
    } else {
      const credential = (await navigator.credentials.create({
        publicKey: {
          challenge,
          rp: { name: 'Absensi Digital', id: host },
          // TODO: ganti dengan ID dan nama siswa asli dari backend.
          user: { id: randomBytes(16), name: 'siswa', displayName: 'Siswa' },
          pubKeyCredParams: [
            { type: 'public-key', alg: -7 },
            { type: 'public-key', alg: -257 },
          ],
          authenticatorSelection: {
            authenticatorAttachment: 'platform',
            userVerification: 'required',
            residentKey: 'preferred',
          },
          timeout: 60000,
          attestation: 'none',
        },
      })) as PublicKeyCredential | null

      if (!credential) return fail('Pendaftaran fingerprint gagal. Coba lagi.')
      localStorage.setItem(CREDENTIAL_KEY, toBase64Url(credential.rawId))
      hasStoredCredential.value = true
    }

    // Abaikan hasil bila modal sudah ditutup atau ada percobaan yang lebih baru.
    if (myRun !== runId || !props.open) return

    status.value = 'success'
    timer = setTimeout(() => emit('success'), 700)
  } catch (err) {
    if (myRun !== runId || !props.open) return
    const name = (err as DOMException)?.name
    if (name === 'NotAllowedError') {
      fail('Verifikasi dibatalkan atau waktu habis. Coba lagi.')
    } else if (name === 'SecurityError') {
      fail('Alamat ini tidak didukung untuk fingerprint. Gunakan alamat domain HTTPS yang sah.')
    } else if (name === 'NotSupportedError') {
      fail('Perangkat ini tidak mendukung fingerprint untuk situs web.')
    } else {
      fail('Fingerprint gagal diproses. Coba lagi.')
    }
  }
}

function onDaftarUlang() {
  localStorage.removeItem(CREDENTIAL_KEY)
  hasStoredCredential.value = false
  verifyFingerprint()
}

watch(
  () => props.open,
  (open) => {
    clearTimeout(timer)
    if (import.meta.client) {
      if (open) window.addEventListener('keydown', onKeydown)
      else window.removeEventListener('keydown', onKeydown)
    }

    if (!open) {
      runId++
      status.value = 'idle'
      errorMessage.value = ''
      return
    }

    if (import.meta.client) verifyFingerprint()
  },
  { immediate: true },
)

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') onBatal()
}

function onBatal() {
  clearTimeout(timer)
  runId++
  status.value = 'idle'
  emit('update:open', false)
}

onBeforeUnmount(() => {
  clearTimeout(timer)
  runId++
  if (import.meta.client) window.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <Teleport to="body">
    <!-- Overlay -->
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      leave-active-class="transition-opacity duration-150"
      leave-to-class="opacity-0"
    >
      <div
        v-if="props.open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-6"
        role="dialog"
        aria-modal="true"
        aria-label="Verifikasi Fingerprint"
        @click.self="onBatal"
      >
        <!-- Kartu modal -->
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl">
          <!-- Lingkaran hijau sangat muda + ikon sidik jari -->
          <div
            class="mx-auto flex size-20 items-center justify-center rounded-full bg-brand-50 text-brand-700"
          >
            <AppIcon name="fingerprint" :size="38" :stroke-width="1.7" />
          </div>

          <h2 class="mt-5 text-lg font-bold text-slate-900">Verifikasi Fingerprint</h2>
          <p class="mt-1.5 text-sm leading-relaxed text-slate-500">
            Ikuti petunjuk sidik jari yang muncul di layar smartphone Anda.
          </p>

          <!-- Status pemindaian -->
          <p
            class="mt-5 flex items-center justify-center gap-2 text-sm font-semibold"
            :class="[
              status === 'success' ? 'text-brand-600' : '',
              status === 'error' ? 'text-danger-600' : '',
              status === 'scanning' || status === 'idle' ? 'text-brand-500/70' : '',
            ]"
          >
            <span
              class="size-2 shrink-0 rounded-full"
              :class="[
                status === 'scanning' ? 'animate-ping bg-brand-300' : '',
                status === 'idle' ? 'bg-slate-300' : '',
                status === 'success' ? 'bg-brand-500' : '',
                status === 'error' ? 'bg-danger-600' : '',
              ]"
            />
            <span v-if="status === 'scanning'">Menunggu verifikasi di perangkat…</span>
            <span v-else-if="status === 'success'">Sidik jari dikenali</span>
            <span v-else-if="status === 'error'">Verifikasi gagal</span>
            <span v-else>Menunggu sidik jari…</span>
          </p>

          <!-- Pesan error -->
          <p v-if="status === 'error'" class="mt-2 text-sm leading-relaxed text-slate-500">
            {{ errorMessage }}
          </p>

          <!-- Tombol coba lagi (hanya saat gagal) -->
          <button v-if="status === 'error'" type="button" class="btn-primary mt-5" @click="verifyFingerprint">
            Coba Lagi
          </button>

          <!-- Daftar ulang kunci perangkat (hanya saat gagal dan sudah pernah terdaftar) -->
          <button
            v-if="status === 'error' && hasStoredCredential"
            type="button"
            class="mt-3 text-sm font-semibold text-brand-700 active:text-brand-800"
            @click="onDaftarUlang"
          >
            Daftarkan ulang sidik jari
          </button>

          <!-- Tombol batal -->
          <button type="button" class="btn-outline mt-6 border-brand-300 text-brand-700" @click="onBatal">
            Batal
          </button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>