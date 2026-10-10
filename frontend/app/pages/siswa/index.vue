<script setup lang="ts">
useHead({ title: 'Beranda · Absensi Digital' })

// TODO: ganti dengan data dari API / session siswa.
// Data siswa & riwayat mock dipusatkan di `app/utils/siswa.ts`.
const avatarInisial = computed(() => inisial(siswa.nama))

const hariIni = new Date()
const tanggalHariIni = computed(() => tanggalPanjang(hariIni))

/** Status scan: dibuka/ditutup oleh petugas (lihat composables/useScanStatus.ts). */
const { scanActive, refreshScanStatus } = useScanStatus()
onMounted(refreshScanStatus)

/** Absensi hari ini (null = belum absen) */
const absenHariIni = {
  sudahHadir: true,
  jam: '07:12',
  metode: 'Face Recognition',
  status: 'Tepat Waktu',
}

/** Riwayat absensi terbaru (5 teratas). */
const riwayatTerbaru = riwayatAbsen.slice(0, 5)
</script>

<template>
  <div class="min-h-dvh bg-white pb-nav">
    <!-- ══ SATU BLOK: area hijau + kartu status menyatu ══
         Hijau & kartu sengaja TIDAK lagi saling tumpang tindih (`-mt` sudah
         dihapus): dengan begitu tidak ada lapisan yang bisa saling menimpa dan
         memotong isi. Tinggi hijau = tinggi isinya, jadi selalu proporsional. -->
    <section
      class="relative overflow-hidden rounded-b-3xl bg-brand-700 shadow-[0_12px_28px_-16px_rgba(2,6,23,0.45)]"
    >
      <!-- ── Isi header hijau ── -->
      <header
        class="px-6 pt-[calc(env(safe-area-inset-top)+2.5rem)] text-white"
      >
        <div class="flex items-start justify-between">
          <!-- Logo + nama aplikasi -->
          <div class="flex items-center gap-2.5">
            <span
              class="flex size-9 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20 ring-inset"
            >
              <AppIcon name="scan-face" :size="19" />
            </span>
            <span class="text-sm font-semibold tracking-wide text-white/90 uppercase">
              Absensi Digital
            </span>
          </div>

          <!-- Inisial profil -->
          <NuxtLink
            to="/siswa/profil"
            class="flex size-10 items-center justify-center rounded-full bg-white/15 text-sm font-bold ring-1 ring-white/25 ring-inset transition active:scale-95"
            :aria-label="`Profil ${siswa.nama}`"
          >
            {{ avatarInisial }}
          </NuxtLink>
        </div>

        <!-- Sapaan + tanggal -->
        <div class="mt-7">
          <h1 class="text-[26px] leading-tight font-extrabold tracking-tight">
            Halo, {{ siswa.nama }}!
          </h1>
          <p class="mt-1.5 text-sm text-white/70">{{ tanggalHariIni }}</p>
          <p class="mt-0.5 text-xs text-white/50">Kelas {{ siswa.kelas }} · NIS {{ siswa.nis }}</p>
        </div>
      </header>

      <!-- ── Kartu status, menempel di dasar blok (pt-6 / pb-5) ── -->
      <div class="px-5 pt-6 pb-5">
        <section class="rounded-2xl bg-white p-5 shadow-lg shadow-slate-900/15">
          <template v-if="absenHariIni.sudahHadir">
            <div class="flex items-center gap-4">
              <span
                class="flex size-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-700"
              >
                <AppIcon name="check-circle" :size="26" :stroke-width="2" />
              </span>
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                  <h2 class="text-base font-bold text-slate-900">Sudah Hadir</h2>
                  <span
                    class="rounded-full bg-brand-100 px-2 py-0.5 text-[11px] font-bold text-brand-700"
                  >
                    {{ absenHariIni.status }}
                  </span>
                </div>
                <p class="mt-0.5 text-sm text-slate-500">
                  Jam kedatangan
                  <span class="font-semibold text-slate-700">{{ absenHariIni.jam }}</span>
                </p>
              </div>
            </div>

            <div
              class="mt-4 flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-4 py-3"
            >
              <span class="shrink-0 text-xs font-medium text-slate-500">Metode absen</span>
              <span class="flex min-w-0 items-center gap-1.5 text-xs font-bold text-slate-800">
                <AppIcon name="user-scan" :size="16" class="text-brand-600" />
                <span class="truncate">{{ absenHariIni.metode }}</span>
              </span>
            </div>
          </template>

          <template v-else>
            <div class="flex items-center gap-4">
              <span
                class="flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400"
              >
                <AppIcon name="clock" :size="26" :stroke-width="2" />
              </span>
              <div>
                <h2 class="text-base font-bold text-slate-900">Belum Absen</h2>
                <p class="mt-0.5 text-sm text-slate-500">
                  {{ scanActive ? 'Lakukan absensi sekarang.' : 'Menunggu petugas membuka absensi.' }}
                </p>
              </div>
            </div>
          </template>
        </section>
      </div>
    </section>

    <!-- ── Konten di bawah blok ── -->
    <div class="px-5">
      <!-- ── Tombol scan utama (aktif) ── -->
      <NuxtLink
        v-if="scanActive"
        to="/siswa/scan"
        class="mt-5 flex w-full items-center gap-4 rounded-2xl bg-brand-700 p-4 text-left text-white shadow-xl shadow-brand-700/25 transition active:scale-[0.98] active:bg-brand-800"
      >
        <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-white/15">
          <AppIcon name="scan-face" :size="26" />
        </span>
        <span class="min-w-0 flex-1">
          <span class="block text-base font-bold">Scan Wajah</span>
          <span class="mt-0.5 block text-xs text-white/70">
            Klik untuk melakukan absensi sekarang
          </span>
        </span>
        <AppIcon name="chevron-right" :size="22" class="text-white/70" />
      </NuxtLink>

      <!-- ── Tombol scan (nonaktif: belum dibuka petugas) ── -->
      <div
        v-else
        class="mt-5 flex w-full items-center gap-4 rounded-2xl bg-slate-100 p-4 text-left text-slate-500"
        role="status"
        aria-disabled="true"
      >
        <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-slate-200">
          <AppIcon name="lock" :size="24" />
        </span>
        <span class="min-w-0 flex-1">
          <span class="block text-base font-bold text-slate-600">Scan Wajah</span>
          <span class="mt-0.5 block text-xs text-slate-400">
            Menunggu petugas membuka absensi
          </span>
        </span>
      </div>

      <!-- ── Daftar absensi terbaru ── -->
      <section class="mt-7">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="section-title">Absensi Terbaru</h2>
          <NuxtLink
            to="/siswa/riwayat"
            class="text-sm font-semibold text-brand-700 active:text-brand-800"
          >
            Lihat Semua
          </NuxtLink>
        </div>

        <ul class="divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-100">
          <li v-for="item in riwayatTerbaru" :key="item.tanggal.getTime()" class="flex items-center gap-3 px-4 py-3.5">
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
                {{ item.jam }} · {{ item.metode }}
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
    </div>

    <SiswaBottomnav active="beranda" />
  </div>
</template>