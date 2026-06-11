<?php
// File: db.php

// Aktifkan tampilan error untuk mendiagnosis penyebab HTTP 500 di hosting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Matikan exception mysqli agar tidak memicu HTTP 500 secara silent jika koneksi gagal
mysqli_report(MYSQLI_REPORT_OFF);

if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1' || strpos($_SERVER['HTTP_HOST'], '192.168.') === 0) {
    // Konfigurasi Localhost (XAMPP)
    $host = "localhost";
    $user = "root";
    $pass = ""; 
    $db   = "dw_tkj_pnup";
} else {
    // Konfigurasi Hosting Live (InfinityFree)
    $host = "sql111.infinityfree.com";
    $user = "if0_42009355";
    $pass = "Rasyah2006"; 
    $db   = "if0_42009355_dw_tkj_pnup";
}

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error . ". Silakan buka file db.php dan sesuaikan kredensial database untuk domain " . htmlspecialchars($_SERVER['HTTP_HOST']) . " Anda.");
}
?>