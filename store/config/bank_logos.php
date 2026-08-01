<?php

/*
|--------------------------------------------------------------------------
| Katalog logo bank
|--------------------------------------------------------------------------
| Daftar bank terkurasi untuk rekening pembayaran manual. Tiap entri:
|   slug   => key (juga nama file SVG di public/images/bank-logos/<slug>.svg)
|   label  => nama tampil bank
|   color  => palette badge fallback (dipakai hanya kalau SVG tidak ada /
|             bank kustom di luar katalog). Nilai valid: sky, amber, emerald,
|             rose, indigo (lihat komponen resources/views/components/bank-logo).
|
| Aset SVG bersumber dari idn-finlogos (CC BY-NC 4.0) — lihat
| public/images/bank-logos/CREDITS.md untuk atribusi.
*/

return [
    'bca' => ['label' => 'BCA', 'color' => 'sky'],
    'mandiri' => ['label' => 'Mandiri', 'color' => 'amber'],
    'bri' => ['label' => 'BRI', 'color' => 'indigo'],
    'bni' => ['label' => 'BNI', 'color' => 'amber'],
    'bsi' => ['label' => 'BSI', 'color' => 'emerald'],
    'btn' => ['label' => 'BTN', 'color' => 'amber'],
    'btpn' => ['label' => 'BTPN', 'color' => 'indigo'],
    'cimb-niaga' => ['label' => 'CIMB Niaga', 'color' => 'rose'],
    'permata' => ['label' => 'Permata', 'color' => 'emerald'],
    'danamon' => ['label' => 'Danamon', 'color' => 'amber'],
    'maybank' => ['label' => 'Maybank', 'color' => 'amber'],
    'paninbank' => ['label' => 'Panin', 'color' => 'indigo'],
    'ocbc-nisp' => ['label' => 'OCBC NISP', 'color' => 'rose'],
    'jago' => ['label' => 'Bank Jago', 'color' => 'amber'],
    'seabank' => ['label' => 'SeaBank', 'color' => 'sky'],
];
