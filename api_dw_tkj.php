<?php
// File: api_dw_tkj.php
header("Content-Type: application/json");
include 'db.php'; 

// Menangkap API Key dari Header
$headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
$client_key = $headers['key'] ?? $headers['Key'] ?? $_SERVER['HTTP_KEY'] ?? '';

// Cek keamanan
$check_admin = $conn->query("SELECT * FROM admin WHERE key_token = '$client_key' LIMIT 1");
if (!$check_admin || $check_admin->num_rows == 0) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "API Key Tidak Valid!"]);
    exit;
}

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
elseif ($type == 'students') {
    $sql = "SELECT 
            m.sk_mahasiswa, m.nim, m.nama_mahasiswa, m.angkatan, m.kelas, m.status_akademik,
            COALESCE(
                (SELECT ipk FROM fact_ringkasan_akademik ra WHERE ra.sk_mahasiswa = m.sk_mahasiswa ORDER BY id_fact_akademik DESC LIMIT 1),
                (SELECT ipk_akhir FROM fact_kelulusan_tkj kt WHERE kt.sk_mahasiswa = m.sk_mahasiswa LIMIT 1),
                0
            ) as ipk
            FROM dim_mahasiswa_tkj m
            ORDER BY m.angkatan DESC, m.nama_mahasiswa ASC";
            
    $query = $conn->query($sql);
    if($query) {
        while($row = $query->fetch_assoc()) { $response[] = $row; }
    }
}
elseif ($type == 'chart_ipk_mhs') {
    $sk = isset($_GET['sk']) ? intval($_GET['sk']) : 0;
    $sql = "SELECT w.tipe_semester, w.tahun_ajaran, f.ips, f.ipk 
            FROM fact_ringkasan_akademik f
            JOIN dim_waktu w ON f.sk_waktu = w.sk_waktu
            WHERE f.sk_mahasiswa = $sk 
            ORDER BY w.sk_waktu ASC";
            
    $query = $conn->query($sql);
    if($query) {
        while($row = $query->fetch_assoc()) { $response[] = $row; }
    }
}

// Kembalikan format JSON murni
echo json_encode(["status" => "success", "results" => $response]);
?>