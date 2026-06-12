<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// Tangani preflight request dari browser (fetch)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include 'db.php';

function log_request($conn, $endpoint, $method, $status_code, $api_key) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $endpoint_clean = substr($endpoint, 0, 255);
    $method_clean = substr($method, 0, 10);
    $api_key_clean = substr($api_key, 0, 255);
    
    $stmt = $conn->prepare("INSERT INTO api_requests_log (endpoint, method, ip_address, status_code, api_key) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sssis", $endpoint_clean, $method_clean, $ip, $status_code, $api_key_clean);
        $stmt->execute();
        $stmt->close();
    }
}

$headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
$client_key = trim($headers['key'] ?? $headers['Key'] ?? $_SERVER['HTTP_KEY'] ?? $_GET['key'] ?? $_POST['key'] ?? '');
$type = $_GET['type'] ?? '';

// Check key validity
$stmt = $conn->prepare("SELECT id_user FROM admin WHERE key_token = ? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server error."]);
    exit;
}
$stmt->bind_param("s", $client_key);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    $stmt->close();
    log_request($conn, $type, $_SERVER['REQUEST_METHOD'], 401, $client_key);
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "API Key Tidak Valid!"]);
    exit;
}
$stmt->close();

// Check rate limit
$stmt_limit = $conn->prepare("SELECT COUNT(*) FROM api_requests_log WHERE api_key = ?");
$stmt_limit->bind_param("s", $client_key);
$stmt_limit->execute();
$stmt_limit->bind_result($req_count);
$stmt_limit->fetch();
$stmt_limit->close();

if ($req_count >= 200) {
    log_request($conn, $type, $_SERVER['REQUEST_METHOD'], 429, $client_key);
    http_response_code(429);
    echo json_encode(["status" => "error", "message" => "Batas limit request (200) telah terlampaui!"]);
    exit;
}

// Log success request
log_request($conn, $type, $_SERVER['REQUEST_METHOD'], 200, $client_key);

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
    $angkatan = isset($_GET['angkatan']) ? intval($_GET['angkatan']) : 0;
    $kelas    = isset($_GET['kelas']) ? trim($_GET['kelas']) : '';

    $where_clauses = [];
    $params = [];
    $types = "";

    if ($angkatan > 0) {
        $where_clauses[] = "m.angkatan = ?";
        $params[] = $angkatan;
        $types .= "i";
    }
    if ($kelas !== '') {
        $where_clauses[] = "m.kelas = ?";
        $params[] = $kelas;
        $types .= "s";
    }

    $where_sql = "";
    if (count($where_clauses) > 0) {
        $where_sql = "WHERE " . implode(" AND ", $where_clauses);
    }

    // 1. Total Mahasiswa
    $mhs_sql = "SELECT COUNT(*) as total FROM dim_mahasiswa_tkj m $where_sql";
    $stmt_mhs = $conn->prepare($mhs_sql);
    if ($stmt_mhs) {
        if (count($params) > 0) {
            $stmt_mhs->bind_param($types, ...$params);
        }
        $stmt_mhs->execute();
        $mhs = $stmt_mhs->get_result()->fetch_assoc();
        $stmt_mhs->close();
    } else {
        $mhs = ["total" => 0];
    }

    // 2. Rata-rata IPK
    $ipk_sql = "SELECT ROUND(AVG(f.ipk), 2) as rata FROM fact_ringkasan_akademik f JOIN dim_mahasiswa_tkj m ON f.sk_mahasiswa = m.sk_mahasiswa $where_sql";
    $stmt_ipk = $conn->prepare($ipk_sql);
    if ($stmt_ipk) {
        if (count($params) > 0) {
            $stmt_ipk->bind_param($types, ...$params);
        }
        $stmt_ipk->execute();
        $ipk = $stmt_ipk->get_result()->fetch_assoc();
        $stmt_ipk->close();
    } else {
        $ipk = ["rata" => 0];
    }

    // 3. Total Cum Laude
    $where_cumlaude = "";
    if (count($where_clauses) > 0) {
        $where_cumlaude = "AND " . implode(" AND ", $where_clauses);
    }
    $cumlaude_sql = "SELECT COUNT(*) as total FROM fact_kelulusan_tkj f JOIN dim_mahasiswa_tkj m ON f.sk_mahasiswa = m.sk_mahasiswa WHERE f.predikat LIKE '%Cum Laude%' $where_cumlaude";
    $stmt_cum = $conn->prepare($cumlaude_sql);
    if ($stmt_cum) {
        if (count($params) > 0) {
            $stmt_cum->bind_param($types, ...$params);
        }
        $stmt_cum->execute();
        $cumlaude = $stmt_cum->get_result()->fetch_assoc();
        $stmt_cum->close();
    } else {
        $cumlaude = ["total" => 0];
    }

    // Get unique angkatan (unfiltered)
    $angkatan_query = $conn->query("SELECT DISTINCT angkatan FROM dim_mahasiswa_tkj WHERE angkatan IS NOT NULL ORDER BY angkatan DESC");
    $angkatan_list = [];
    if ($angkatan_query) {
        while ($row = $angkatan_query->fetch_assoc()) {
            $angkatan_list[] = (int)$row['angkatan'];
        }
    }

    // Get unique kelas (unfiltered)
    $kelas_query = $conn->query("SELECT DISTINCT kelas FROM dim_mahasiswa_tkj WHERE kelas IS NOT NULL AND kelas != '' ORDER BY kelas ASC");
    $kelas_list = [];
    if ($kelas_query) {
        while ($row = $kelas_query->fetch_assoc()) {
            $kelas_list[] = $row['kelas'];
        }
    }

    $response = [
        "total_mahasiswa" => (int)($mhs['total'] ?? 0),
        "rata_rata_ipk"   => (float)($ipk['rata'] ?? 0),
        "total_cumlaude"  => (int)($cumlaude['total'] ?? 0),
        "angkatan_list"   => $angkatan_list,
        "kelas_list"      => $kelas_list,
    ];
}

elseif ($type == 'chart_ipk') {
    $angkatan = isset($_GET['angkatan']) ? intval($_GET['angkatan']) : 0;
    $kelas    = isset($_GET['kelas']) ? trim($_GET['kelas']) : '';

    $where_clauses = [];
    $params = [];
    $types = "";

    if ($angkatan > 0) {
        $where_clauses[] = "m.angkatan = ?";
        $params[] = $angkatan;
        $types .= "i";
    }
    if ($kelas !== '') {
        $where_clauses[] = "m.kelas = ?";
        $params[] = $kelas;
        $types .= "s";
    }

    $where_sql = "";
    if (count($where_clauses) > 0) {
        $where_sql = "WHERE " . implode(" AND ", $where_clauses);
    }

    $sql = "SELECT
                w.sk_waktu,
                CONCAT(w.tipe_semester, ' ', w.tahun_ajaran) AS label_semester,
                ROUND(AVG(f.ipk), 2) AS ipk
            FROM fact_ringkasan_akademik f
            JOIN dim_waktu w ON f.sk_waktu = w.sk_waktu
            JOIN dim_mahasiswa_tkj m ON f.sk_mahasiswa = m.sk_mahasiswa
            $where_sql
            GROUP BY w.sk_waktu, w.tipe_semester, w.tahun_ajaran
            ORDER BY w.sk_waktu ASC";

    $stmt2 = $conn->prepare($sql);
    if ($stmt2) {
        if (count($params) > 0) {
            $stmt2->bind_param($types, ...$params);
        }
        $stmt2->execute();
        $result = $stmt2->get_result();
        while ($row = $result->fetch_assoc()) {
            $response[] = [
                "sk_waktu" => $row['sk_waktu'],
                "label"    => $row['label_semester'],
                "ipk"      => (float)$row['ipk'],
            ];
        }
        $stmt2->close();
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
    $filter_kelas = $_GET['kelas'] ?? '';
    
    $where = [];
    $params = [];
    $types = "";
    
    if ($filter_kelas !== '') {
        if (strpos($filter_kelas, 'Alumni (Lulusan') === 0) {
            preg_match('/Alumni \(Lulusan (.*?)\)/', $filter_kelas, $matches);
            $tahun = $matches[1] ?? '';
            $where[] = "(m.status_akademik = 'Lulus' OR m.status_akademik = 'Alumni') AND (SELECT w.tahun_ajaran FROM fact_kelulusan_tkj kt JOIN dim_waktu w ON kt.sk_waktu = w.sk_waktu WHERE kt.sk_mahasiswa = m.sk_mahasiswa LIMIT 1) = ?";
            $params[] = $tahun;
            $types .= "s";
        } elseif ($filter_kelas === 'Alumni') {
            $where[] = "(m.status_akademik = 'Lulus' OR m.status_akademik = 'Alumni') AND NOT EXISTS (SELECT 1 FROM fact_kelulusan_tkj kt WHERE kt.sk_mahasiswa = m.sk_mahasiswa)";
        } else {
            $where[] = "m.kelas = ?";
            $params[] = $filter_kelas;
            $types .= "s";
        }
    }
    
    $where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";
    
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
                ) as predikat,
                COALESCE(
                    (SELECT w.tahun_ajaran FROM fact_kelulusan_tkj kt
                     JOIN dim_waktu w ON kt.sk_waktu = w.sk_waktu
                     WHERE kt.sk_mahasiswa = m.sk_mahasiswa LIMIT 1),
                    '-'
                ) as tahun_lulus
            FROM dim_mahasiswa_tkj m
            $where_sql
            ORDER BY m.nim ASC";
            
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $response[] = $row;
        }
        $stmt->close();
    } else {
        // Fallback
        $response = [];
    }
}

// --------------------------------------------------
// ENDPOINT: classes (BARU)
// Digunakan oleh: akademik.php (dropdown kelas)
// --------------------------------------------------
elseif ($type == 'classes') {
    $kelas_list = [];
    
    // 1. Get real classes (Aktif)
    $sql_active = "SELECT DISTINCT kelas FROM dim_mahasiswa_tkj WHERE status_akademik = 'Aktif' AND kelas IS NOT NULL AND kelas != '' ORDER BY kelas ASC";
    $query = $conn->query($sql_active);
    if ($query) {
        while ($row = $query->fetch_assoc()) {
            $kelas_list[] = $row['kelas'];
        }
    }
    
    // 2. Get Alumni classes
    $sql_alumni = "SELECT DISTINCT w.tahun_ajaran 
                   FROM dim_mahasiswa_tkj m 
                   JOIN fact_kelulusan_tkj kt ON m.sk_mahasiswa = kt.sk_mahasiswa 
                   JOIN dim_waktu w ON kt.sk_waktu = w.sk_waktu 
                   WHERE m.status_akademik = 'Lulus' OR m.status_akademik = 'Alumni'
                   ORDER BY w.tahun_ajaran DESC";
    $query2 = $conn->query($sql_alumni);
    if ($query2) {
        while ($row = $query2->fetch_assoc()) {
            $kelas_list[] = "Alumni (Lulusan " . $row['tahun_ajaran'] . ")";
        }
    }
    
    // 3. Catch alumni without fact data
    $sql_alumni_no_year = "SELECT COUNT(*) as c FROM dim_mahasiswa_tkj m 
                           WHERE (m.status_akademik = 'Lulus' OR m.status_akademik = 'Alumni') 
                           AND m.sk_mahasiswa NOT IN (SELECT sk_mahasiswa FROM fact_kelulusan_tkj)";
    $res3 = $conn->query($sql_alumni_no_year)->fetch_assoc();
    if (($res3['c'] ?? 0) > 0) {
        $kelas_list[] = "Alumni";
    }
    
    $response = $kelas_list;
}

// --------------------------------------------------
// ENDPOINT: students_summary (BARU)
// Digunakan oleh: akademik.php (footer stats)
// --------------------------------------------------
elseif ($type == 'students_summary') {
    $filter_kelas = $_GET['kelas'] ?? '';
    
    if ($filter_kelas !== '') {
        $where = [];
        $params = [];
        $types = "";
        
        if (strpos($filter_kelas, 'Alumni (Lulusan') === 0) {
            preg_match('/Alumni \(Lulusan (.*?)\)/', $filter_kelas, $matches);
            $tahun = $matches[1] ?? '';
            $where[] = "(m.status_akademik = 'Lulus' OR m.status_akademik = 'Alumni') AND (SELECT w.tahun_ajaran FROM fact_kelulusan_tkj kt JOIN dim_waktu w ON kt.sk_waktu = w.sk_waktu WHERE kt.sk_mahasiswa = m.sk_mahasiswa LIMIT 1) = ?";
            $params[] = $tahun;
            $types .= "s";
        } elseif ($filter_kelas === 'Alumni') {
            $where[] = "(m.status_akademik = 'Lulus' OR m.status_akademik = 'Alumni') AND NOT EXISTS (SELECT 1 FROM fact_kelulusan_tkj kt WHERE kt.sk_mahasiswa = m.sk_mahasiswa)";
        } else {
            $where[] = "m.kelas = ?";
            $params[] = $filter_kelas;
            $types .= "s";
        }
        
        $where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";
        
        $total_active = 0;
        $total_alumni = 0;
        
        $count_sql = "SELECT m.status_akademik, COUNT(*) as c FROM dim_mahasiswa_tkj m $where_sql GROUP BY m.status_akademik";
        $stmt_count = $conn->prepare($count_sql);
        if ($stmt_count) {
            if (!empty($params)) {
                $stmt_count->bind_param($types, ...$params);
            }
            $stmt_count->execute();
            $res_count = $stmt_count->get_result();
            while ($row = $res_count->fetch_assoc()) {
                $status_l = strtolower($row['status_akademik']);
                if ($status_l === 'aktif') {
                    $total_active += $row['c'];
                } elseif ($status_l === 'lulus' || $status_l === 'alumni') {
                    $total_alumni += $row['c'];
                }
            }
            $stmt_count->close();
        }
        
        $ipk_sql = "
            SELECT 
                MAX(current_ipk) as max_ipk,
                MIN(NULLIF(current_ipk, 0)) as min_ipk
            FROM (
                SELECT 
                    COALESCE(
                        (SELECT ipk FROM fact_ringkasan_akademik ra
                         WHERE ra.sk_mahasiswa = m.sk_mahasiswa
                         ORDER BY id_fact_akademik DESC LIMIT 1),
                        (SELECT ipk_akhir FROM fact_kelulusan_tkj kt
                         WHERE kt.sk_mahasiswa = m.sk_mahasiswa LIMIT 1),
                        0
                    ) as current_ipk
                FROM dim_mahasiswa_tkj m
                $where_sql
            ) t";
        
        $ipk_max = 0;
        $ipk_min = 0;
        
        $stmt_ipk = $conn->prepare($ipk_sql);
        if ($stmt_ipk) {
            if (!empty($params)) {
                $stmt_ipk->bind_param($types, ...$params);
            }
            $stmt_ipk->execute();
            $res_ipk = $stmt_ipk->get_result()->fetch_assoc();
            $ipk_max = $res_ipk['max_ipk'] ?? 0;
            $ipk_min = $res_ipk['min_ipk'] ?? 0;
            $stmt_ipk->close();
        }
        
        $response = [
            "total_aktif" => (int)$total_active,
            "total_alumni" => (int)$total_alumni,
            "ipk_tertinggi" => (float)$ipk_max,
            "ipk_terendah" => (float)$ipk_min
        ];
    } else {
        $aktif = $conn->query("SELECT COUNT(*) as c FROM dim_mahasiswa_tkj WHERE status_akademik = 'Aktif'")->fetch_assoc()['c'] ?? 0;
        $alumni = $conn->query("SELECT COUNT(*) as c FROM dim_mahasiswa_tkj WHERE status_akademik = 'Lulus' OR status_akademik = 'Alumni'")->fetch_assoc()['c'] ?? 0;
        
        $ipk_max = $conn->query("
            SELECT MAX(val) as m FROM (
                SELECT ipk as val FROM fact_ringkasan_akademik
                UNION ALL
                SELECT ipk_akhir as val FROM fact_kelulusan_tkj
            ) t
        ")->fetch_assoc()['m'] ?? 0;
        
        $ipk_min = $conn->query("
            SELECT MIN(val) as m FROM (
                SELECT ipk as val FROM fact_ringkasan_akademik WHERE ipk > 0
                UNION ALL
                SELECT ipk_akhir as val FROM fact_kelulusan_tkj WHERE ipk_akhir > 0
            ) t
        ")->fetch_assoc()['m'] ?? 0;
        
        $response = [
            "total_aktif" => (int)$aktif,
            "total_alumni" => (int)$alumni,
            "ipk_tertinggi" => (float)$ipk_max,
            "ipk_terendah" => (float)$ipk_min
        ];
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
// ENDPOINT: create_student
// --------------------------------------------------
elseif ($type == 'create_student') {
    $nim = $_POST['nim'] ?? '';
    $nama = $_POST['nama_mahasiswa'] ?? '';
    $angkatan = intval($_POST['angkatan'] ?? 0);
    $kelas = $_POST['kelas'] ?? '';
    $status = $_POST['status_akademik'] ?? 'Aktif';
    
    $sql = "INSERT INTO dim_mahasiswa_tkj (nim, nama_mahasiswa, angkatan, kelas, status_akademik) VALUES (?, ?, ?, ?, ?)";
    $stmt_crud = $conn->prepare($sql);
    if ($stmt_crud) {
        $stmt_crud->bind_param("ssiss", $nim, $nama, $angkatan, $kelas, $status);
        if ($stmt_crud->execute()) {
            $response = ["message" => "Berhasil menambahkan mahasiswa"];
        } else {
            http_response_code(500);
            $response = ["message" => "Gagal: " . $stmt_crud->error];
        }
        $stmt_crud->close();
    } else {
        http_response_code(500);
        $response = ["message" => "Gagal prepare statement"];
    }
}

// --------------------------------------------------
// ENDPOINT: update_student
// --------------------------------------------------
elseif ($type == 'update_student') {
    $sk = intval($_POST['sk_mahasiswa'] ?? 0);
    $nim = $_POST['nim'] ?? '';
    $nama = $_POST['nama_mahasiswa'] ?? '';
    $angkatan = intval($_POST['angkatan'] ?? 0);
    $kelas = $_POST['kelas'] ?? '';
    $status = $_POST['status_akademik'] ?? 'Aktif';
    
    $sql = "UPDATE dim_mahasiswa_tkj SET nim=?, nama_mahasiswa=?, angkatan=?, kelas=?, status_akademik=? WHERE sk_mahasiswa=?";
    $stmt_crud = $conn->prepare($sql);
    if ($stmt_crud) {
        $stmt_crud->bind_param("ssissi", $nim, $nama, $angkatan, $kelas, $status, $sk);
        if ($stmt_crud->execute()) {
            $response = ["message" => "Berhasil mengubah mahasiswa"];
        } else {
            http_response_code(500);
            $response = ["message" => "Gagal: " . $stmt_crud->error];
        }
        $stmt_crud->close();
    } else {
        http_response_code(500);
        $response = ["message" => "Gagal prepare statement"];
    }
}

// --------------------------------------------------
// ENDPOINT: delete_student
// --------------------------------------------------
elseif ($type == 'delete_student') {
    $sk = intval($_POST['sk_mahasiswa'] ?? 0);
    $sql = "DELETE FROM dim_mahasiswa_tkj WHERE sk_mahasiswa=?";
    $stmt_crud = $conn->prepare($sql);
    if ($stmt_crud) {
        $stmt_crud->bind_param("i", $sk);
        if ($stmt_crud->execute()) {
            $response = ["message" => "Berhasil menghapus mahasiswa"];
        } else {
            http_response_code(500);
            $response = ["message" => "Gagal: " . $stmt_crud->error];
        }
        $stmt_crud->close();
    } else {
        http_response_code(500);
        $response = ["message" => "Gagal prepare statement"];
    }
}

// --------------------------------------------------
// ENDPOINT: comparison (OLAP Slice & Dice per Kelas)
// Digunakan oleh: laporan.php
// --------------------------------------------------
elseif ($type == 'comparison') {
    $sql = "SELECT
                m.kelas,
                COUNT(DISTINCT m.sk_mahasiswa) as total_mahasiswa,
                ROUND(AVG(f.ipk), 2) as avg_ipk,
                MAX(f.ipk) as max_ipk,
                MIN(NULLIF(f.ipk, 0)) as min_ipk,
                COUNT(DISTINCT CASE WHEN m.status_akademik = 'Aktif' THEN m.sk_mahasiswa END) as total_aktif,
                COUNT(DISTINCT CASE WHEN m.status_akademik IN ('Lulus','Alumni') THEN m.sk_mahasiswa END) as total_alumni
            FROM dim_mahasiswa_tkj m
            LEFT JOIN fact_ringkasan_akademik f ON m.sk_mahasiswa = f.sk_mahasiswa
            WHERE m.kelas IS NOT NULL AND m.kelas != ''
            GROUP BY m.kelas
            ORDER BY m.kelas ASC";
    $q = $conn->query($sql);
    if ($q) {
        while ($row = $q->fetch_assoc()) {
            $response[] = [
                'kelas'           => $row['kelas'],
                'total_mahasiswa' => (int)$row['total_mahasiswa'],
                'avg_ipk'         => (float)$row['avg_ipk'],
                'max_ipk'         => (float)$row['max_ipk'],
                'min_ipk'         => (float)($row['min_ipk'] ?? 0),
                'total_aktif'     => (int)$row['total_aktif'],
                'total_alumni'    => (int)$row['total_alumni'],
            ];
        }
    }
}

// --------------------------------------------------
// ENDPOINT: tren_angkatan (Roll-Up Tren IPK per Angkatan)
// Digunakan oleh: tren.php
// --------------------------------------------------
elseif ($type == 'tren_angkatan') {
    $angkatan = isset($_GET['angkatan']) ? intval($_GET['angkatan']) : 0;
    $where = $angkatan > 0 ? "WHERE m.angkatan = $angkatan" : "";

    $sql = "SELECT
                m.angkatan,
                w.sk_waktu,
                CONCAT(w.tipe_semester, ' ', w.tahun_ajaran) AS label_semester,
                ROUND(AVG(f.ipk), 2) AS avg_ipk,
                ROUND(AVG(f.ips), 2) AS avg_ips,
                COUNT(DISTINCT m.sk_mahasiswa) AS jumlah_mhs
            FROM fact_ringkasan_akademik f
            JOIN dim_mahasiswa_tkj m ON f.sk_mahasiswa = m.sk_mahasiswa
            JOIN dim_waktu w ON f.sk_waktu = w.sk_waktu
            $where
            GROUP BY m.angkatan, w.sk_waktu, w.tipe_semester, w.tahun_ajaran
            ORDER BY m.angkatan ASC, w.sk_waktu ASC";

    $q = $conn->query($sql);
    if ($q) {
        while ($row = $q->fetch_assoc()) {
            $response[] = [
                'angkatan'      => (int)$row['angkatan'],
                'sk_waktu'      => (int)$row['sk_waktu'],
                'label'         => $row['label_semester'],
                'avg_ipk'       => (float)$row['avg_ipk'],
                'avg_ips'       => (float)$row['avg_ips'],
                'jumlah_mhs'    => (int)$row['jumlah_mhs'],
            ];
        }
    }
}

// --------------------------------------------------
// ENDPOINT: ranking (Top Mahasiswa per Kelas)
// Digunakan oleh: laporan.php
// --------------------------------------------------
elseif ($type == 'ranking') {
    $kelas = isset($_GET['kelas']) ? trim($_GET['kelas']) : '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $where = $kelas !== '' ? "AND m.kelas = ?" : "";

    $sql = "SELECT
                m.sk_mahasiswa,
                m.nim,
                m.nama_mahasiswa,
                m.kelas,
                m.angkatan,
                m.status_akademik,
                COALESCE(
                    (SELECT ipk FROM fact_ringkasan_akademik ra
                     WHERE ra.sk_mahasiswa = m.sk_mahasiswa
                     ORDER BY id_fact_akademik DESC LIMIT 1),
                    (SELECT ipk_akhir FROM fact_kelulusan_tkj kt
                     WHERE kt.sk_mahasiswa = m.sk_mahasiswa LIMIT 1),
                    0
                ) as ipk_terakhir,
                COALESCE(
                    (SELECT predikat FROM fact_kelulusan_tkj kt
                     WHERE kt.sk_mahasiswa = m.sk_mahasiswa LIMIT 1),
                    '-'
                ) as predikat
            FROM dim_mahasiswa_tkj m
            WHERE 1=1 $where
            ORDER BY ipk_terakhir DESC
            LIMIT ?";

    $stmt_r = $conn->prepare($sql);
    if ($stmt_r) {
        if ($kelas !== '') {
            $stmt_r->bind_param("si", $kelas, $limit);
        } else {
            $stmt_r->bind_param("i", $limit);
        }
        $stmt_r->execute();
        $res = $stmt_r->get_result();
        $rank = 1;
        while ($row = $res->fetch_assoc()) {
            $row['rank'] = $rank++;
            $response[] = $row;
        }
        $stmt_r->close();
    }
}

// --------------------------------------------------
// ENDPOINT: predikat_per_kelas (Distribusi Predikat per Kelas)
// Digunakan oleh: laporan.php
// --------------------------------------------------
elseif ($type == 'predikat_per_kelas') {
    $sql = "SELECT
                m.kelas,
                k.predikat,
                COUNT(*) as jumlah
            FROM fact_kelulusan_tkj k
            JOIN dim_mahasiswa_tkj m ON k.sk_mahasiswa = m.sk_mahasiswa
            WHERE m.kelas IS NOT NULL AND m.kelas != ''
            GROUP BY m.kelas, k.predikat
            ORDER BY m.kelas ASC, jumlah DESC";
    $q = $conn->query($sql);
    if ($q) {
        while ($row = $q->fetch_assoc()) {
            $response[] = [
                'kelas'    => $row['kelas'],
                'predikat' => $row['predikat'],
                'jumlah'   => (int)$row['jumlah'],
            ];
        }
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