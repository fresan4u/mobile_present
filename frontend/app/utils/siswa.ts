/**
 * Data siswa & absensi (mock).
 *
 * TODO: ganti seluruh isi berkas ini dengan data dari API/session siswa.
 * Data dipusatkan di sini agar halaman beranda, riwayat, dan profil selalu
 * menampilkan sumber yang sama.
 */

export interface Siswa {
  nama: string
  username: string
  nis: string
  kelas: string
  nomorWaliMurid: string
}

export type MetodeAbsen = 'Face Recognition' | 'Fingerprint'
export type StatusAbsen = 'Hadir' | 'Terlambat' | 'Alpa'

export interface Absensi {
  tanggal: Date
  /** Kosong bila siswa tidak absen (mis. Alpa). */
  jam?: string
  metode?: MetodeAbsen
  status: StatusAbsen
}

export const siswa: Siswa = {
  nama: 'Ahmad Fauzi',
  username: 'ahmad.fauzi',
  nis: '2024001',
  kelas: 'XII IPA 2',
  nomorWaliMurid: '0812-3456-7890',
}

/** Bangun tanggal relatif terhadap hari ini agar mock selalu relevan. */
function tanggalKe(offsetHari: number, jam?: string): Date {
  const d = new Date()
  d.setDate(d.getDate() - offsetHari)
  if (jam) {
    const [h, m] = jam.split(':').map(Number)
    d.setHours(h ?? 0, m ?? 0, 0, 0)
  }
  return d
}

/** Riwayat absen masuk (terbaru lebih dulu). */
export const riwayatAbsen: Absensi[] = [
  { tanggal: tanggalKe(0, '07:12'), jam: '07:12', metode: 'Face Recognition', status: 'Hadir' },
  { tanggal: tanggalKe(1, '06:58'), jam: '06:58', metode: 'Fingerprint', status: 'Hadir' },
  { tanggal: tanggalKe(2, '07:24'), jam: '07:24', metode: 'Face Recognition', status: 'Terlambat' },
  { tanggal: tanggalKe(3, '07:02'), jam: '07:02', metode: 'Fingerprint', status: 'Hadir' },
  { tanggal: tanggalKe(4, '07:31'), jam: '07:31', metode: 'Face Recognition', status: 'Terlambat' },
  { tanggal: tanggalKe(5), status: 'Alpa' },
  { tanggal: tanggalKe(6, '06:55'), jam: '06:55', metode: 'Face Recognition', status: 'Hadir' },
  { tanggal: tanggalKe(7, '07:05'), jam: '07:05', metode: 'Fingerprint', status: 'Hadir' },
  { tanggal: tanggalKe(8, '07:09'), jam: '07:09', metode: 'Face Recognition', status: 'Hadir' },
  { tanggal: tanggalKe(9, '07:00'), jam: '07:00', metode: 'Fingerprint', status: 'Hadir' },
  { tanggal: tanggalKe(10, '06:52'), jam: '06:52', metode: 'Face Recognition', status: 'Hadir' },
  { tanggal: tanggalKe(11, '07:15'), jam: '07:15', metode: 'Face Recognition', status: 'Hadir' },
]

/** Rekap jumlah per status dari daftar absensi. */
export function rekapAbsen(list: Absensi[]) {
  const hitung = (status: StatusAbsen) => list.filter((a) => a.status === status).length
  return {
    hadir: hitung('Hadir'),
    terlambat: hitung('Terlambat'),
    alpa: hitung('Alpa'),
  }
}

/** Gaya visual per status, dipakai bersama oleh halaman beranda & riwayat. */
export const gayaStatus: Record<
  StatusAbsen,
  { badge: string; ikon: 'check' | 'clock' | 'alert'; ikonBox: string }
> = {
  Hadir: { badge: 'bg-brand-100 text-brand-700', ikon: 'check', ikonBox: 'bg-brand-50 text-brand-700' },
  Terlambat: { badge: 'bg-amber-100 text-amber-700', ikon: 'clock', ikonBox: 'bg-amber-50 text-amber-600' },
  Alpa: { badge: 'bg-danger-100 text-danger-600', ikon: 'alert', ikonBox: 'bg-danger-50 text-danger-500' },
}

