<?php

return [
    'upload_path' => env('KEMENHAM_UPLOAD_PATH', dirname(base_path(), 2).DIRECTORY_SEPARATOR.'kemenham-uploads'),
    'status' => ['Terdaftar', 'Karya terkirim', 'Diverifikasi', 'Perlu revisi', 'Ditolak'],
    'competitions' => [
        'buku-cerita' => [
            'name' => 'Lomba Buku Cerita HAM',
            'short_name' => 'Buku Cerita',
            'participant' => 'mahasiswa aktif perguruan tinggi',
            'id_label' => 'NIM',
            'origin_label' => 'Asal perguruan tinggi',
            'card_label' => 'Scan Kartu Tanda Mahasiswa (KTM)',
            'work_title' => 'Unggah Karya Buku Cerita HAM',
            'rules' => [
                'Peserta: mahasiswa aktif perguruan tinggi di Daerah Khusus Jakarta.',
                'Individu atau kelompok (maksimal 3 orang), 1 karya per peserta.',
                'Buku memuat 10 sampai 20 cerita, tiap cerita 2.000 sampai 3.000 karakter termasuk spasi.',
                'Wajib ada sampul, daftar isi, dan nomor halaman.',
                'Format PDF, ukuran kertas A5.',
            ],
        ],
        'komik' => [
            'name' => 'Lomba Serial Komik Bergambar',
            'short_name' => 'Komik',
            'participant' => 'pelajar aktif SLTA sederajat',
            'id_label' => 'NIS',
            'origin_label' => 'Asal sekolah',
            'card_label' => 'Scan Kartu Pelajar',
            'work_title' => 'Unggah Karya Serial Komik Bergambar',
            'rules' => [
                'Peserta: pelajar aktif SLTA sederajat di Daerah Khusus Jakarta.',
                'Individu atau kelompok (maksimal 3 orang), 1 karya per peserta.',
                'Serial memuat 5 sampai 10 cerita yang berkelanjutan.',
                'Wajib ada sampul dan nomor halaman. Tanpa unsur SARA dan pornografi.',
                'Format PDF, ukuran kertas A5.',
            ],
        ],
    ],
];
