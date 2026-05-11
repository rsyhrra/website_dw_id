<?php
define('API_BASE', "http://127.0.0.1/pbl_integrasi/api_dw_tkj.php"); 
define('API_KEY', "TKJ-PNUP-2026-SECRET");

function callAPI($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // Wajib di Windows XAMPP
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['key: ' . API_KEY]); 
    $res = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($res, true);
    return $data['results'] ?? [];
}