<?php
// File: db.php
$host = "localhost";
$user = "root";
$pass = ""; 
$db   = "dw_tkj_pnup"; // Harus sama dengan database-mu

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(["error" => "Koneksi ke database gagal"]));
}
?>