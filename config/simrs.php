<?php

return [
    'connection' => 'simrs',

    'pasien' => [
        'table' => 'pasien',
        'no_rm' => 'no_rm',
        'nama' => 'nama',
        'tgl_lahir' => 'tgl_lahir',
        'no_hp' => 'no_hp',
    ],

    'diagnosis' => [
        'table' => 'diagnosis',
        'no_rm' => 'no_rm',
        'kode_icd' => 'kode_icd',
        'tanggal' => 'tgl_diagnosis',
    ],

    'resep' => [
        'table' => 'resep_obat',
        'no_rm' => 'no_rm',
        'nama_obat' => 'nama_obat',
        'dosis' => 'dosis',
        'tanggal' => 'tgl_resep',
    ],

    // Awalan kode ICD-10 untuk TBC (A15 sampai A19)
    'tbc_icd_prefix' => ['A15', 'A16', 'A17', 'A18', 'A19'],

    // true = hanya tampilkan pasien dengan diagnosis TBC
    'filter_tbc' => true,
];