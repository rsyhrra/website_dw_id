<?php
// File: config.php

// PENTING: Jika nama folder kamu di htdocs bukan 'pbl_integrasi', ubah bagian url di bawah ini!
define('API_BASE', "http://localhost/pbl_integrasi/api_dw_tkj.php");
define('API_KEY', "TKJ-PNUP-2026-SECRET");

function callAPI($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Mengirimkan API Key melalui HTTP Header (Modul VI)
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('key: ' . API_KEY)); 
    
    $response = curl_exec($ch);
    
    // Tangani jika koneksi curl gagal
    if(curl_errno($ch)){
        return [];
    }
    
    curl_close($ch);
    $data = json_decode($response, true);
    
    return $data['results'] ?? [];
}
?>