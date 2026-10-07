<script setup lang="ts">
useHead({ title: 'Riwayat Absensi · Absensi Digital' })

const router = useRouter()

// Jangan keluar aplikasi jika tidak ada history (mis. dibuka langsung via URL).
function goBack() {
  if (window.history.length > 1) router.back()
  else router.push('/siswa')
}

// TODO: ganti dengan data dari API riwayat absensi.
const rekap = rekapAbsen(riwayatAbsen)

const kartuRekap = [
  { label: 'Hadir', nilai: rekap.hadir, warna: 'text-brand-600', bg: 'bg-brand-50' },
  { label: 'Terlambat', nilai: rekap.terlambat, warna: 'text-amber-600', bg: 'bg-amber-50' },
  { label: 'Alpa', nilai: rekap.alpa, warna: 'text-danger-600', bg: 'bg-danger-50' },
]
</script>

<template>
  <div class="min-h-dvh bg-slate-50 pb-nav">
    <!-- ── Navbar atas ── -->
    <header
      class="sticky top-0 z-20 flex items-center gap-1 border-b border-slate-100 bg-white px-4 py-3"
    >
      <button
        type="button"
        class="-ml-1 flex size-10 items-center justify-center rounded-full text-slate-700 transition active:bg-slate-100"
        aria-label="Kembali"
        @click="goBack"
      >
        <AppIcon name="chevron-left" :size="24" :stroke-width="2" />
      </button>
      <h1 class="text-base font-bold text-slate-900">Riwayat Absensi</h1>
    </header>

    <main class="px-5 pt-5">
      <!-- ── Rekap kehadiran ── -->
      <section>
        <h2 class="section-title">Rekap Kehadiran</h2>
        <p class="mt-1 text-xs text-slate-400">30 hari terakhir · {{ bulanPanjang(new Date()) }}</p>

        <div class="mt-3 grid grid-cols-3 gap-3">
          <div
            v-for="kartu in kartuRekap"
            :key="kartu.label"
            class="rounded-2xl bg-white p-4 text-center shadow-sm"
          >
            <p class="text-2xl font-extrabold" :class="kartu.warna">{{ kartu.nilai }}</p>
            <p class="mt-1 text-xs font-semibold text-slate-500">{{ kartu.label }}</p>
          </div>
        </div>
      </section>

      <!-- ── Daftar absensi per tanggal ── -->
      <section class="mt-7">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="section-title">Daftar Absensi</h2>
          <span class="text-xs font-medium text-slate-400">{{ riwayatAbsen.length }} catatan</span>
        </div>

        <ul class="divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-100 bg-white">
          <li
            v-for="item in riwayatAbsen"
            :key="item.tanggal.getTime()"
            class="flex items-center gap-3 px-4 py-3.5"
          >
            <span
              class="flex size-10 shrink-0 items-center justify-center rounded-xl"
              :class="gayaStatus[item.status].ikonBox"
            >
              <AppIcon :name="gayaStatus[item.status].ikon" :size="20" :stroke-width="2.4" />
            </span>

            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-semibold text-slate-800">
                {{ tanggalPendek(item.tanggal) }}
              </p>
              <p class="text-xs text-slate-400">
                <template v-if="item.jam">{{ item.jam }} · {{ item.metode }}</template>
                <template v-else>Tidak ada absen masuk</template>
              </p>
            </div>

            <span
              class="rounded-full px-2.5 py-1 text-[11px] font-bold"
              :class="gayaStatus[item.status].badge"
            >
              {{ item.status }}
            </span>
          </li>
        </ul>
      </section>
    </main>

    <SiswaBottomnav active="riwayat" />
  </div>
</template>
