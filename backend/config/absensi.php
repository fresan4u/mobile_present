<?php

return [

    /*
    | Zona waktu untuk semua perhitungan jam absensi (WIB).
    */
    'timezone' => 'Asia/Jakarta',

    /*
    | Nilai bawaan jadwal scan. Semua batas dihitung sampai AKHIR menit tersebut:
    | "06:20" berarti masih berlaku sampai 06:20:59.
    | Nanti petugas bisa menimpa jam ini lewat tabel pengaturan di database.
    */
    'masuk' => [
        'buka'        => '05:30',
        'batas_hadir' => '06:20', // sampai batas ini status HADIR, setelahnya TERLAMBAT
        'tutup'       => '06:45',
    ],

    'pulang' => [
        'buka'  => '15:00',
        'tutup' => '15:45',
    ],

    /*
    | Hari scan dibuka: 1 = Senin sampai 5 = Jumat. Hari lain ditutup.
    */
    'hari_aktif' => [1, 2, 3, 4, 5],

    /*
    | Geolokasi: murid harus berada dalam radius dari titik tengah sekolah.
    | Titik tengah diisi lewat file .env.
    */
    'lokasi' => [
        'aktif'              => (bool) env('ABSENSI_LOKASI_AKTIF', true),
        'latitude'           => env('ABSENSI_LATITUDE'),
        'longitude'          => env('ABSENSI_LONGITUDE'),
        'radius_meter'       => (int) env('ABSENSI_RADIUS_METER', 30),
        // Scan ditolak bila akurasi GPS lebih buruk dari batas ini (dalam meter).
        'akurasi_maks_meter' => (int) env('ABSENSI_AKURASI_MAKS_METER', 30),
    ],

];