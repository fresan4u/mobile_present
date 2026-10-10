/**
 * Status apakah scan absensi sedang dibuka oleh petugas.
 * Nilainya dipakai bersama oleh beranda dan halaman scan.
 */
export function useScanStatus() {
  const scanActive = useState<boolean>('scanActive', () => true)
  const scanLoading = useState<boolean>('scanLoading', () => false)

  async function refreshScanStatus() {
    scanLoading.value = true
    try {
      // TODO: ganti dengan panggilan API ke backend. Bentuk responsnya
      // menyesuaikan API yang dibuat tim backend, contohnya:
      //   const res = await $fetch<{ aktif: boolean }>('/api/scan-status')
      //   scanActive.value = res.aktif

      // Khusus development, untuk menguji tampilan tanpa backend:
      //   buka halaman dengan ?scan=off (nonaktif) atau ?scan=on (aktif).
      if (import.meta.dev && import.meta.client) {
        const flag = new URLSearchParams(window.location.search).get('scan')
        if (flag === 'off') scanActive.value = false
        if (flag === 'on') scanActive.value = true
      }
    } finally {
      scanLoading.value = false
    }
  }

  return { scanActive, scanLoading, refreshScanStatus }
}