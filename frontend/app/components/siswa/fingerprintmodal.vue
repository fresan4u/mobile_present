<script setup lang="ts">
const emit = defineEmits<{ 'update:open': [value: boolean]; success: [] }>()

const props = defineProps<{ open: boolean }>()

type Status = 'idle' | 'scanning' | 'success'
const status = ref<Status>('idle')
let timer: ReturnType<typeof setTimeout> | undefined

watch(
  () => props.open,
  (open) => {
    clearTimeout(timer)
    if (import.meta.client) {
      if (open) window.addEventListener('keydown', onKeydown)
      else window.removeEventListener('keydown', onKeydown)
    }

    if (!open) {
      status.value = 'idle'
      return
    }

    // Simulasi proses pemindaian sidik jari.
    status.value = 'scanning'
    timer = setTimeout(() => {
      status.value = 'success'
      timer = setTimeout(() => emit('success'), 700)
    }, 2500)
  },
  { immediate: true },
)

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') onBatal()
}

function onBatal() {
  clearTimeout(timer)
  status.value = 'idle'
  emit('update:open', false)
}

onBeforeUnmount(() => {
  clearTimeout(timer)
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
            Silakan tempelkan sidik jari pada smartphone Anda.
          </p>

          <!-- Status pemindaian -->
          <p
            class="mt-5 flex items-center justify-center gap-2 text-sm font-semibold"
            :class="status === 'success' ? 'text-brand-600' : 'text-brand-500/70'"
          >
            <span
              class="size-2 rounded-full"
              :class="[
                status === 'scanning' ? 'animate-ping bg-brand-300' : '',
                status === 'idle' ? 'bg-slate-300' : '',
                status === 'success' ? 'bg-brand-500' : '',
              ]"
            />
            <span v-if="status === 'scanning'">Memindai sidik jari…</span>
            <span v-else-if="status === 'success'">Sidik jari dikenali</span>
            <span v-else>Menunggu sidik jari…</span>
          </p>

          <!-- Tombol batal -->
          <button type="button" class="btn-outline mt-6 border-brand-300 text-brand-700" @click="onBatal">
            Batal
          </button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
