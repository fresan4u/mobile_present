<script setup lang="ts">
import { NuxtLink } from '#components'

type MenuKey = 'beranda' | 'riwayat' | 'profil'

const props = defineProps<{ active?: MenuKey }>()

/**
 * Semua menu sudah punya halaman tujuan. `to: null` tetap didukung bila nanti
 * ada menu yang belum siap (item tampil namun tidak bisa dinavigasi).
 *
 * NuxtLink diimpor eksplisit karena dipakai lewat `<component :is>`; komponen
 * yang dirujuk sebagai string tidak ter-resolve otomatis saat SSR.
 */
const menus: { key: MenuKey; label: string; icon: 'home' | 'history' | 'user'; to: string | null }[] = [
  { key: 'beranda', label: 'Beranda', icon: 'home', to: '/siswa' },
  { key: 'riwayat', label: 'Riwayat', icon: 'history', to: '/siswa/riwayat' },
  { key: 'profil', label: 'Profil', icon: 'user', to: '/siswa/profil' },
]
</script>

<template>
  <!--
    Tinggi nav diambil dari --bottom-nav-h (lihat assets/css/main.css) yang juga
    dipakai halaman lewat .pb-nav, sehingga konten tidak akan pernah tertutup nav.
  -->
  <nav
    class="fixed inset-x-0 bottom-0 z-30 h-[var(--bottom-nav-h)] border-t border-slate-100 bg-white shadow-[0_-4px_20px_-8px_rgba(15,23,42,0.15)]"
    aria-label="Navigasi utama"
  >
    <ul
      class="mx-auto flex h-full max-w-md items-stretch justify-around px-2 pb-[env(safe-area-inset-bottom)]"
    >
      <li v-for="menu in menus" :key="menu.key" class="flex flex-1 items-stretch">
        <component
          :is="menu.to ? NuxtLink : 'button'"
          :to="menu.to ?? undefined"
          :type="menu.to ? undefined : 'button'"
          :disabled="!menu.to"
          :aria-current="props.active === menu.key ? 'page' : undefined"
          class="flex w-full flex-col items-center justify-center gap-1 rounded-xl px-1 py-1.5 transition active:scale-95 disabled:cursor-default disabled:active:scale-100"
          :class="props.active === menu.key ? 'text-brand-700' : 'text-slate-400'"
        >
          <span
            class="flex h-8 w-12 items-center justify-center rounded-full transition"
            :class="props.active === menu.key ? 'bg-brand-50' : ''"
          >
            <AppIcon
              :name="menu.icon"
              :size="22"
              :stroke-width="props.active === menu.key ? 2.1 : 1.8"
            />
          </span>
          <span class="text-[11px] leading-none font-semibold">{{ menu.label }}</span>
        </component>
      </li>
    </ul>
  </nav>
</template>
