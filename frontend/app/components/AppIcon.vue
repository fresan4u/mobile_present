<script setup lang="ts">
/**
 * Ikon SVG garis (stroke) agar tampilan konsisten di semua halaman.
 * Dipakai sebagai <AppIcon name="..." /> — nama ikon auto-complete dari IconName.
 */
const ICONS = {
  'scan-face': {
    // scan + wajah (kamar pemindai)
    paths: [
      'M3 8V5a2 2 0 0 1 2-2h3',
      'M16 3h3a2 2 0 0 1 2 2v3',
      'M21 16v3a2 2 0 0 1-2 2h-3',
      'M8 21H5a2 2 0 0 1-2-2v-3',
      'M8.5 10.5v.01',
      'M15.5 10.5v.01',
      'M12 10.5v2.5h-1',
      'M9 16c.9.6 1.9 1 3 1s2.1-.4 3-1',
      'M4 12h1',
      'M19 12h1',
      'M12 4v1',
      'M12 19v1',
    ],
  },
  mail: {
    paths: ['M3 7.5A2.5 2.5 0 0 1 5.5 5h13A2.5 2.5 0 0 1 21 7.5v9a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 16.5z', 'm3.5 7 7.4 5.2a2 2 0 0 0 2.2 0L20.5 7'],
  },
  lock: {
    paths: ['M6 10.5h12a1.5 1.5 0 0 1 1.5 1.5v7A1.5 1.5 0 0 1 18 20.5H6A1.5 1.5 0 0 1 4.5 19v-7A1.5 1.5 0 0 1 6 10.5Z', 'M8 10.5V7.5a4 4 0 0 1 8 0v3', 'M12 14.5v2.5'],
  },
  'arrow-right': {
    paths: ['M4 12h15', 'm13 6 6 6-6 6'],
  },
  'chevron-left': {
    paths: ['m14.5 5-7 7 7 7'],
  },
  'chevron-right': {
    paths: ['m9.5 5 7 7-7 7'],
  },
  check: {
    paths: ['m5 12.5 4.5 4.5L19 7.5'],
  },
  'check-circle': {
    paths: ['M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z', 'm8.5 12 2.5 2.5 4.5-4.5'],
  },
  'clock': {
    paths: ['M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z', 'M12 7.5V12l3 1.5'],
  },
  home: {
    paths: ['M4 10.5 12 4l8 6.5V19a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 19z', 'M9.5 20.5V14h5v6.5'],
  },
  history: {
    paths: ['M4 12a8 8 0 1 0 2.6-5.9', 'M4 4.5V9h4.5', 'M12 8v4.3l3 1.7'],
  },
  user: {
    paths: ['M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z', 'M4.5 20.5a7.5 7.5 0 0 1 15 0'],
  },
  phone: {
    paths: [
      'M21.5 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 1.6 4.2 2 2 0 0 1 3.6 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.6a2 2 0 0 1-.5 2.1L7.6 9.6a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.8.3 1.7.6 2.6.7a2 2 0 0 1 1.7 2Z',
    ],
  },
  'at-sign': {
    paths: ['M12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z', 'M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.9 7.9'],
  },
  'user-scan': {
    // wajah dengan bingkai pemindai
    paths: [
      'M3 8V5.5A2.5 2.5 0 0 1 5.5 3H8',
      'M16 3h2.5A2.5 2.5 0 0 1 21 5.5V8',
      'M21 16v2.5a2.5 2.5 0 0 1-2.5 2.5H16',
      'M8 21H5.5A2.5 2.5 0 0 1 3 18.5V16',
      'M9 10.5v.01',
      'M15 10.5v.01',
      'M12 10.5v2h-1',
      'M9.5 15.5c.8.5 1.6.8 2.5.8s1.7-.3 2.5-.8',
    ],
  },
  fingerprint: {
    paths: [
      'M12 11v2.5a10 10 0 0 1-.7 3.6',
      'M8.4 20.5a15 15 0 0 0 1.1-5V12a2.5 2.5 0 1 1 5 0v1.2',
      'M4.5 9a9 9 0 0 1 15-3.2',
      'M19.5 9v3a20 20 0 0 1-.5 4',
      'M8 12a4 4 0 0 1 8 0v1',
      'M9.5 17.5c.3-1.8.5-3.6.5-5.5a2 2 0 0 1 4 0c0 1-.1 2-.2 3',
    ],
  },
  help: {
    paths: ['M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z', 'M9.6 9.2a2.5 2.5 0 0 1 4.85.8c0 1.7-2.45 2-2.45 3.5', 'M12 16.8v.01'],
  },
  calendar: {
    paths: ['M4.5 6.5h15A1.5 1.5 0 0 1 21 8v11.5A1.5 1.5 0 0 1 19.5 21h-15A1.5 1.5 0 0 1 3 19.5V8a1.5 1.5 0 0 1 1.5-1.5Z', 'M8 3.5v4', 'M16 3.5v4', 'M3 11h18'],
  },
  logout: {
    paths: ['M15 8V6.5A1.5 1.5 0 0 0 13.5 5h-8A1.5 1.5 0 0 0 4 6.5v11A1.5 1.5 0 0 0 5.5 19h8a1.5 1.5 0 0 0 1.5-1.5V16', 'M10 12h10', 'm17 9 3 3-3 3'],
  },
  eye: {
    paths: ['M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z', 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z'],
  },
  'eye-off': {
    paths: ['M3 3l18 18', 'M10.6 6a8.6 8.6 0 0 1 1.4-.1c6 0 9.5 6.1 9.5 6.1a17 17 0 0 1-2.3 3', 'M6.2 8.1A16.6 16.6 0 0 0 2.5 12S6 18.1 12 18.1c1.7 0 3.1-.5 4.3-1.2', 'M9.9 9.9a3 3 0 0 0 4.2 4.2'],
  },
  alert: {
    paths: ['M12 8.5v4', 'M12 16v.01', 'M10.3 4.2 2.8 17.2A1.6 1.6 0 0 0 4.2 19.6h15.6a1.6 1.6 0 0 0 1.4-2.4L13.7 4.2a2 2 0 0 0-3.4 0Z'],
  },
} as const

export type IconName = keyof typeof ICONS

const props = withDefaults(
  defineProps<{
    name: IconName
    /** Ukuran dalam px (atau kelas css custom) */
    size?: number | string
    /** Ketebalan garis */
    strokeWidth?: number
  }>(),
  { size: 24, strokeWidth: 1.8 },
)

const icon = computed(() => ICONS[props.name])
const px = computed(() => (typeof props.size === 'number' ? `${props.size}px` : props.size))
</script>

<template>
  <svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-linecap="round"
    stroke-linejoin="round"
    :stroke-width="strokeWidth"
    :width="px"
    :height="px"
    aria-hidden="true"
    focusable="false"
  >
    <path v-for="(d, i) in icon.paths" :key="i" :d="d" />
  </svg>
</template>
