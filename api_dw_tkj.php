<?php
// File: api_dw_tkj.php
header("Content-Type: application/json");
include 'db.php'; 

// --- SECURITY (Modul VI) ---
// Gunakan $_SERVER['HTTP_KEY'] sebagai backup jika apache_request_headers() diblokir oleh server lokal
$headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
$client_key = $headers['key'] ?? $headers['Key'] ?? $_SERVER['HTTP_KEY'] ?? '';

// Validasi Token
$sql_check = "SELECT * FROM admin WHERE key_token = '$client_key' LIMIT 1";
$check_admin = $conn->query($sql_check);

// Jika query gagal (misal tabel admin tidak ada) atau jumlah baris 0
if (!$check_admin || $check_admin->num_rows == 0) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "API Key Tidak Valid! Pastikan Header terkirim dan token sesuai di database."]);
    exit;
}

// --- DATA RETRIEVAL (Modul IV) ---
$type = $_GET['type'] ?? '';
$response = [];

if ($type == 'summary') {
    $mhs = $conn->query("SELECT COUNT(*) as total FROM dim_mahasiswa_tkj")->fetch_assoc();
    $ipk = $conn->query("SELECT ROUND(AVG(ipk), 2) as rata FROM fact_ringkasan_akademik")->fetch_assoc();
    $cumlaude = $conn->query("SELECT COUNT(*) as total FROM fact_kelulusan_tkj WHERE predikat LIKE '%Cum Laude%'")->fetch_assoc();
    
    $response = [
        "total_mahasiswa" => $mhs['total'] ?? 0,
        "rata_rata_ipk" => $ipk['rata'] ?? 0,
        "total_cumlaude" => $cumlaude['total'] ?? 0
    ];
} 
elseif ($type == 'chart_ipk') {
    // Ambil data milik ID 398
    $query = $conn->query("SELECT sk_waktu, ipk FROM fact_ringkasan_akademik WHERE sk_mahasiswa = 398 ORDER BY sk_waktu ASC");
    if($query) {
        while($row = $query->fetch_assoc()) { $response[] = $row; }
    }
}
elseif ($type == 'chart_predikat') {
    $query = $conn->query("SELECT predikat, COUNT(*) as jumlah FROM fact_kelulusan_tkj GROUP BY predikat");
    if($query) {
        while($row = $query->fetch_assoc()) { $response[] = $row; }
    }
}

echo json_encode(["results" => $response]);
?>