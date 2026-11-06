<?php
/*
|--------------------------------------------------------------------------
| KAPASITAS HARIAN PER MESIN (m² per hari kerja)
|--------------------------------------------------------------------------
| Kapasitas tidak ada di data ERP, jadi diisi manual di file ini
| (database tidak diubah sama sekali).
|
| Kunci   = isi kolom produksi.nama_mesin (huruf besar)
| kapasitas = m² per hari kerja
| sumber    = 'RESMI'      -> angka dari PPIC / produksi
|             'PERKIRAAN'  -> sementara; dashboard menampilkan catatan kuning
|
| Cara mengganti: ubah angka kapasitas, lalu ganti sumber menjadi 'RESMI'.
|--------------------------------------------------------------------------
*/

return [
    // Sementara = output harian tertinggi yang pernah tercatat (Okt 2026).
    'CUT'  => ['kapasitas' => 300, 'sumber' => 'PERKIRAAN'],
    'SE'   => ['kapasitas' => 100,  'sumber' => 'PERKIRAAN'],
    'DE'   => ['kapasitas' => 100, 'sumber' => 'PERKIRAAN'],
    'JET'  => ['kapasitas' => 100, 'sumber' => 'PERKIRAAN'],
    'TEMP' => ['kapasitas' => 100, 'sumber' => 'PERKIRAAN'],
];