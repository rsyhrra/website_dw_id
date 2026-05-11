<?php
// File: api_dw_tkj.php
// Versi perbaikan: Fix SQL Injection + tambah endpoint chart_ipk, chart_predikat, search

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

include 'db.php';

// ============================================================
// SECURITY FIX: Gunakan Prepared Statement untuk cek API Key
// (sebelumnya: rentan SQL Injection karena $client_key langsung di-inject)
// ============================================================
$headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
$client_key = trim($headers['key'] ?? $headers['Key'] ?? $_SERVER['HTTP_KEY'] ?? '');

$stmt = $conn->prepare("SELECT id FROM admin WHERE key_token = ? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error."]);
    exit;
}
$stmt->bind_param("s", $client_key);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "API Key Tidak Valid!"]);
    exit;
}
$stmt->close();

// ============================================================
// ROUTING ENDPOINT
// ============================================================
$type = $_GET['type'] ?? '';
$response = [];

// --------------------------------------------------
// ENDPOINT: summary
// Digunakan oleh: index.php (cards statistik)
// --------------------------------------------------
if ($type == 'summary') {
    $mhs      = $conn->query("SELECT COUNT(*) as total FROM dim_mahasiswa_tkj")->fetch_assoc();
    $ipk      = $conn->query("SELECT ROUND(AVG(ipk), 2) as rata FROM fact_ringkasan_akademik")->fetch_assoc();
    $cumlaude = $conn->query("SELECT COUNT(*) as total FROM fact_kelulusan_tkj WHERE predikat LIKE '%Cum Laude%'")->fetch_assoc();

    $response = [
        "total_mahasiswa" => (int)($mhs['total'] ?? 0),
        "rata_rata_ipk"   => (float)($ipk['rata'] ?? 0),
        "total_cumlaude"  => (int)($cumlaude['total'] ?? 0),
    ];
}

// --------------------------------------------------
// ENDPOINT: chart_ipk  ← FIX: endpoint ini hilang sebelumnya!
// Digunakan oleh: index.php (Line Chart Tren IPK per Semester)
// --------------------------------------------------
elseif ($type == 'chart_ipk') {
    $sql = "SELECT
                w.sk_waktu,
                CONCAT(w.tipe_semester, ' ', w.tahun_ajaran) AS label_semester,
                ROUND(AVG(f.ipk), 2) AS ipk
            FROM fact_ringkasan_akademik f
            JOIN dim_waktu w ON f.sk_waktu = w.sk_waktu
            GROUP BY w.sk_waktu, w.tipe_semester, w.tahun_ajaran
            ORDER BY w.sk_waktu ASC";
    $query = $conn->query($sql);
    if ($query) {
        while ($row = $query->fetch_assoc()) {
            $response[] = [
                "sk_waktu" => $row['sk_waktu'],
                "label"    => $row['label_semester'],
                "ipk"      => (float)$row['ipk'],
            ];
        }
    }
}

// --------------------------------------------------
// ENDPOINT: chart_predikat  ← FIX: endpoint ini hilang sebelumnya!
// Digunakan oleh: index.php (Doughnut Chart distribusi predikat)
// --------------------------------------------------
elseif ($type == 'chart_predikat') {
    $sql = "SELECT predikat, COUNT(*) as jumlah
            FROM fact_kelulusan_tkj
            GROUP BY predikat
            ORDER BY jumlah DESC";
    $query = $conn->query($sql);
    if ($query) {
        while ($row = $query->fetch_assoc()) {
            $response[] = [
                "predikat" => $row['predikat'],
                "jumlah"   => (int)$row['jumlah'],
            ];
        }
    }
}

// --------------------------------------------------
// ENDPOINT: students
// Digunakan oleh: akademik.php (tabel data mahasiswa)
// --------------------------------------------------
elseif ($type == 'students') {
    $sql = "SELECT
                m.sk_mahasiswa,
                m.nim,
                m.nama_mahasiswa,
                m.angkatan,
                m.kelas,
                m.status_akademik,
                COALESCE(
                    (SELECT ipk FROM fact_ringkasan_akademik ra
                     WHERE ra.sk_mahasiswa = m.sk_mahasiswa
                     ORDER BY id_fact_akademik DESC LIMIT 1),
                    (SELECT ipk_akhir FROM fact_kelulusan_tkj kt
                     WHERE kt.sk_mahasiswa = m.sk_mahasiswa LIMIT 1),
                    0
                ) as ipk,
                COALESCE(
                    (SELECT predikat FROM fact_kelulusan_tkj kt
                     WHERE kt.sk_mahasiswa = m.sk_mahasiswa LIMIT 1),
                    '-'
                ) as predikat
            FROM dim_mahasiswa_tkj m
            ORDER BY m.angkatan DESC, m.nama_mahasiswa ASC";
    $query = $conn->query($sql);
    if ($query) {
        while ($row = $query->fetch_assoc()) {
            $response[] = $row;
        }
    }
}

// --------------------------------------------------
// ENDPOINT: chart_ipk_mhs
// Digunakan oleh: akademik.php (grafik IPK per mahasiswa)
// --------------------------------------------------
elseif ($type == 'chart_ipk_mhs') {
    $sk  = isset($_GET['sk']) ? intval($_GET['sk']) : 0;
    $sql = "SELECT
                w.tipe_semester,
                w.tahun_ajaran,
                CONCAT(w.tipe_semester, ' ', w.tahun_ajaran) AS label,
                f.ips,
                f.ipk
            FROM fact_ringkasan_akademik f
            JOIN dim_waktu w ON f.sk_waktu = w.sk_waktu
            WHERE f.sk_mahasiswa = ?
            ORDER BY w.sk_waktu ASC";
    $stmt2 = $conn->prepare($sql);
    if ($stmt2) {
        $stmt2->bind_param("i", $sk);
        $stmt2->execute();
        $result = $stmt2->get_result();
        while ($row = $result->fetch_assoc()) {
            $response[] = $row;
        }
        $stmt2->close();
    }
}

// --------------------------------------------------
// ENDPOINT: search  ← BARU: untuk pencarian jurnal/referensi
// Digunakan oleh: index.php (form pencarian akademik)
// --------------------------------------------------
elseif ($type == 'search') {
    $keyword = isset($_GET['q']) ? '%' . trim($_GET['q']) . '%' : '%';
    // Sesuaikan nama tabel/kolom dengan database kamu
    $sql = "SELECT judul, penulis, sumber, tahun, url_pdf
            FROM referensi_akademik
            WHERE judul LIKE ? OR penulis LIKE ?
            ORDER BY tahun DESC
            LIMIT 10";
    $stmt3 = $conn->prepare($sql);
    if ($stmt3) {
        $stmt3->bind_param("ss", $keyword, $keyword);
        $stmt3->execute();
        $result = $stmt3->get_result();
        while ($row = $result->fetch_assoc()) {
            $response[] = $row;
        }
        $stmt3->close();
    }
    // Jika tabel referensi_akademik belum ada, kembalikan array kosong
    if (empty($response)) {
        $response = [];
    }
}

// --------------------------------------------------
// ENDPOINT: tidak dikenal
// --------------------------------------------------
else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Endpoint '$type' tidak dikenal."]);
    exit;
}

// Output JSON
echo json_encode([
    "status"  => "success",
    "type"    => $type,
    "count"   => is_array($response) ? count($response) : 1,
    "results" => $response,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);