<script setup lang="ts">
useHead({ title: 'Absensi Berhasil · Absensi Digital' })

const router = useRouter()

// TODO: ganti dengan data respons API absensi.
const detail = {
  nama: 'Ahmad Fauzi',
  tanggal: new Date(),
  jam: '07:12',
  metode: 'Face Recognition',
}

const baris = computed(() => [
  { label: 'Nama', value: detail.nama },
  { label: 'Tanggal', value: tanggalPanjang(detail.tanggal) },
  { label: 'Jam', value: detail.jam },
  { label: 'Metode Scan', value: detail.metode },
])
</script>

<template>
  <div class="flex min-h-dvh flex-col bg-slate-50">
    <!-- ── Ikon + judul ── -->
    <header class="px-6 pt-[calc(env(safe-area-inset-top)+3rem)] pb-8 text-center">
      <div class="mx-auto flex size-24 items-center justify-center rounded-full bg-brand-50 text-brand-600">
        <AppIcon name="check" :size="48" :stroke-width="2.4" />
      </div>
      <h1 class="mt-6 text-2xl font-extrabold tracking-tight text-slate-900">Absensi Berhasil</h1>
      <p class="mt-2 text-sm text-slate-500">
        Kehadiran Anda hari ini sudah tercatat. Selamat belajar!
      </p>
    </header>

    <!-- ── Detail kehadiran ── -->
    <main class="flex-1 px-5">
      <section class="rounded-2xl bg-white p-5 shadow-sm">
        <h2 class="text-sm font-bold tracking-wide text-slate-900 uppercase">
          Detail Kehadiran
        </h2>
        <dl class="mt-4 space-y-4">
          <div v-for="row in baris" :key="row.label" class="flex items-center justify-between gap-4">
            <dt class="text-sm text-slate-500">{{ row.label }}</dt>
            <dd class="text-right text-sm font-bold text-slate-900">{{ row.value }}</dd>
          </div>
        </dl>
      </section>
    </main>

    <!-- ── Tombol kembali ke beranda ── -->
    <footer class="px-5 pt-6 pb-[calc(env(safe-area-inset-bottom)+1.5rem)]">
      <button type="button" class="btn-primary" @click="router.push('/siswa')">
        Kembali ke Beranda
      </button>
    </footer>
  </div>
</template>
