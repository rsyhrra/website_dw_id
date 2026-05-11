<?php
// Ganti dengan URL InfinityFree kamu nantinya
define('API_BASE', "http://localhost/pbl_integrasi/index.php");
define('API_KEY', "TKJ-PNUP-2026-SECRET");

// Fungsi CURL untuk mengambil data (Modul II & III)[cite: 3, 4]
function fetchData($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('key: ' . API_KEY)); // Modul VI[cite: 7]
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}
?>