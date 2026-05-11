<?php
// File: config.php

// Gunakan 127.0.0.1 untuk koneksi lokal yang lebih stabil di Windows
define('API_BASE', "http://127.0.0.1/pbl_integrasi/api_dw_tkj.php"); 
define('API_KEY', "TKJ-PNUP-2026-SECRET");

function callAPI($url) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // Anti-delay localhost XAMPP
    
    // Header Security
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('key: ' . API_KEY)); 
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    // Decode data JSON
    $data = json_decode($response, true);
    
    return $data['results'] ?? [];
}
?>