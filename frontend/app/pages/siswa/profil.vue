<script setup lang="ts">
useHead({ title: 'Profil · Absensi Digital' })

const router = useRouter()

// TODO: ganti dengan data dari API / session siswa.
const avatarInisial = computed(() => inisial(siswa.nama))

/** Informasi akun yang ditampilkan (read-only). */
const infoAkun = [
  { icon: 'user', label: 'Nama Lengkap', value: siswa.nama },
  { icon: 'at-sign', label: 'Username', value: siswa.username },
  { icon: 'phone', label: 'Nomor Wali Murid', value: siswa.nomorWaliMurid },
] as const

function onKeluar() {
  // TODO: hapus sesi login lalu arahkan ke halaman login.
  router.push('/siswa/login')
}
</script>

<template>
  <div class="min-h-dvh bg-slate-50 pb-nav">
    <!-- ── Header hijau: avatar + identitas ── -->
    <section
      class="relative overflow-hidden rounded-b-3xl bg-brand-700 shadow-[0_12px_28px_-16px_rgba(2,6,23,0.45)]"
    >
      <header class="px-6 pt-[calc(env(safe-area-inset-top)+2.5rem)] pb-8 text-white">
        <h1 class="text-sm font-semibold tracking-wide text-white/80 uppercase">Profil</h1>

        <div class="mt-6 flex items-center gap-4">
          <span
            class="flex size-16 shrink-0 items-center justify-center rounded-full bg-white/15 text-xl font-bold ring-1 ring-white/25 ring-inset"
          >
            {{ avatarInisial }}
          </span>
          <div class="min-w-0">
            <p class="truncate text-xl font-extrabold tracking-tight">{{ siswa.nama }}</p>
            <p class="mt-0.5 text-sm text-white/70">
              Kelas {{ siswa.kelas }} · NIS {{ siswa.nis }}
            </p>
          </div>
        </div>
      </header>
    </section>

    <main class="px-5">
      <!-- ── Kartu informasi akun ── -->
      <section class="mt-5 rounded-2xl bg-white p-5 shadow-sm">
        <h2 class="text-sm font-bold tracking-wide text-slate-900 uppercase">Informasi Akun</h2>

        <dl class="mt-4 divide-y divide-slate-100">
          <div
            v-for="row in infoAkun"
            :key="row.label"
            class="flex items-center gap-3 py-3.5 first:pt-0 last:pb-0"
          >
            <span
              class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700"
            >
              <AppIcon :name="row.icon" :size="20" />
            </span>
            <dt class="text-sm text-slate-500">{{ row.label }}</dt>
            <dd class="ml-auto min-w-0 truncate text-right text-sm font-bold text-slate-900">
              {{ row.value }}
            </dd>
          </div>
        </dl>
      </section>

      <!-- ── Tombol keluar ── -->
      <button
        type="button"
        class="btn-outline mt-4 border-danger-100 text-danger-600"
        @click="onKeluar"
      >
        <AppIcon name="logout" :size="20" />
        <span>Keluar</span>
      </button>
    </main>

    <SiswaBottomnav active="profil" />
  </div>
</template>
