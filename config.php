<?php
// config.php
// Dynamic base URL detection to support localhost and live hosting (e.g. InfinityFree)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$dir = str_replace('\\', '/', dirname($scriptName));
if ($dir === '/') {
    $dir = '';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('API_BASE', $protocol . $host . $dir . "/api_dw_tkj.php"); 

// Include database to fetch the current API Key dynamically
require_once 'db.php';
$db_key = "TKJ-PNUP-2026-SECRET"; // Default fallback
if (isset($conn) && !$conn->connect_error) {
    if (isset($_SESSION['username'])) {
        $stmt_key = $conn->prepare("SELECT key_token FROM admin WHERE nama = ? LIMIT 1");
        if ($stmt_key) {
            $stmt_key->bind_param("s", $_SESSION['username']);
            $stmt_key->execute();
            $res_key = $stmt_key->get_result();
            if ($res_key && $row_key = $res_key->fetch_assoc()) {
                if (!empty($row_key['key_token'])) {
                    $db_key = $row_key['key_token'];
                }
            }
            $stmt_key->close();
        }
    } else {
        $res_key = $conn->query("SELECT key_token FROM admin WHERE id_user = 1 LIMIT 1");
        if ($res_key) {
            $row_key = $res_key->fetch_assoc();
            if (!empty($row_key['key_token'])) {
                $db_key = $row_key['key_token'];
            }
        }
    }
}
define('API_KEY', $db_key);

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
?>