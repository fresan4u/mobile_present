/**
 * Helper format tanggal/waktu berbahasa Indonesia.
 * Auto-import oleh Nuxt (folder app/utils).
 */

const HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']
const HARI_SHORT = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']
const BULAN = [
  'Januari',
  'Februari',
  'Maret',
  'April',
  'Mei',
  'Juni',
  'Juli',
  'Agustus',
  'September',
  'Oktober',
  'November',
  'Desember',
]
const BULAN_SHORT = [
  'Jan',
  'Feb',
  'Mar',
  'Apr',
  'Mei',
  'Jun',
  'Jul',
  'Agu',
  'Sep',
  'Okt',
  'Nov',
  'Des',
]

/** "Senin, 6 Oktober 2026" */
export function tanggalPanjang(input: Date | string): string {
  const d = typeof input === 'string' ? new Date(input) : input
  return `${HARI[d.getDay()]}, ${d.getDate()} ${BULAN[d.getMonth()]} ${d.getFullYear()}`
}

/** "Sen, 6 Okt 2026" */
export function tanggalPendek(input: Date | string): string {
  const d = typeof input === 'string' ? new Date(input) : input
  return `${HARI_SHORT[d.getDay()]}, ${d.getDate()} ${BULAN_SHORT[d.getMonth()]} ${d.getFullYear()}`
}

/** "Oktober 2026" */
export function bulanPanjang(input: Date | string): string {
  const d = typeof input === 'string' ? new Date(input) : input
  return `${BULAN[d.getMonth()]} ${d.getFullYear()}`
}

/** "07:12" */
export function waktu(input: Date | string): string {
  const d = typeof input === 'string' ? new Date(input) : input
  const jam = String(d.getHours()).padStart(2, '0')
  const menit = String(d.getMinutes()).padStart(2, '0')
  return `${jam}:${menit}`
}

/** Inisial untuk avatar, mis. "Ahmad Fauzi" -> "AF" */
export function inisial(nama: string): string {
  const parts = nama.trim().split(/\s+/).filter(Boolean)
  if (parts.length === 0) return '?'
  if (parts.length === 1) return parts[0]!.slice(0, 2).toUpperCase()
  return (parts[0]![0]! + parts[1]![0]!).toUpperCase()
}
