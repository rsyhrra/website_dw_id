<?php
// File: api_docs.php
session_start();

// Redireksi jika belum login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
$apiKey = API_KEY;

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'generate_key') {
        $new_key = "TKJ-PNUP-" . strtoupper(bin2hex(random_bytes(10)));
        $stmt = $conn->prepare("UPDATE admin SET key_token = ? WHERE id_user = 1");
        if ($stmt) {
            $stmt->bind_param("s", $new_key);
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "new_key" => $new_key]);
            } else {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Gagal memperbarui database."]);
            }
            $stmt->close();
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Gagal menyiapkan statement."]);
        }
    } 
    
    elseif ($_POST['action'] === 'clear_logs') {
        $stmt = $conn->prepare("DELETE FROM api_requests_log WHERE api_key = ?");
        if ($stmt) {
            $stmt->bind_param("s", $apiKey);
            if ($stmt->execute()) {
                echo json_encode(["status" => "success"]);
            } else {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Gagal menghapus log database."]);
            }
            $stmt->close();
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Gagal menyiapkan statement."]);
        }
    }
    
    elseif ($_POST['action'] === 'get_metrics') {
        // Get count from DB
        $used_count = 0;
        $stmt = $conn->prepare("SELECT COUNT(*) FROM api_requests_log WHERE api_key = ?");
        if ($stmt) {
            $stmt->bind_param("s", $apiKey);
            $stmt->execute();
            $stmt->bind_result($used_count);
            $stmt->fetch();
            $stmt->close();
        }
        
        // Get last 15 logs
        $logs = [];
        $stmt = $conn->prepare("SELECT endpoint, method, ip_address, DATE_FORMAT(requested_at, '%Y-%m-%d %H:%i:%s') as requested_at, status_code FROM api_requests_log WHERE api_key = ? ORDER BY requested_at DESC LIMIT 15");
        if ($stmt) {
            $stmt->bind_param("s", $apiKey);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $logs[] = $row;
            }
            $stmt->close();
        }
        
        echo json_encode([
            "status" => "success",
            "used" => $used_count,
            "logs" => $logs
        ]);
    }
    exit();
}

// Fetch initial request metrics
$initial_requests_used = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM api_requests_log WHERE api_key = ?");
if ($stmt) {
    $stmt->bind_param("s", $apiKey);
    $stmt->execute();
    $stmt->bind_result($initial_requests_used);
    $stmt->fetch();
    $stmt->close();
}

// Fetch initial logs list
$initial_logs = [];
$stmt = $conn->prepare("SELECT endpoint, method, ip_address, DATE_FORMAT(requested_at, '%Y-%m-%d %H:%i:%s') as requested_at, status_code FROM api_requests_log WHERE api_key = ? ORDER BY requested_at DESC LIMIT 15");
if ($stmt) {
    $stmt->bind_param("s", $apiKey);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $initial_logs[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Dokumentasi & Tester API - TKJ PNUP</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    "primary": "#6366f1",
                    "primary-light": "#818cf8",
                    "primary-dark": "#3730a3",
                    "text-main": "#f8fafc",
                    "text-muted": "#94a3b8",
                },
                fontFamily: {
                    sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
                }
            }
        }
    }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        .material-symbols-outlined { 
            font-family: 'Material Symbols Outlined'; 
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; 
        }
        
        :root {
            --bg-base: radial-gradient(circle at 50% 0%, #1e1b4b 0%, #0f172a 100%);
            --card-bg: rgba(255,255,255,0.04);
            --card-border: rgba(255,255,255,0.08);
            --input-bg: rgba(15,23,42,0.55);
            --input-border: rgba(255,255,255,0.1);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --btn-bg: rgba(255,255,255,0.05);
            --btn-border: rgba(255,255,255,0.08);
            --btn-color: #f1f5f9;
        }
        body.light-mode {
            --bg-base: radial-gradient(circle at 50% 0%, #e0e7ff 0%, #f1f5f9 100%);
            --card-bg: rgba(255,255,255,0.75);
            --card-border: rgba(99,102,241,0.18);
            --input-bg: rgba(241,245,249,0.9);
            --input-border: rgba(99,102,241,0.25);
            --text-main: #1e1b4b;
            --text-muted: #6366f1;
            --btn-bg: rgba(99,102,241,0.09);
            --btn-border: rgba(99,102,241,0.2);
            --btn-color: #1e1b4b;
        }
        body {
            font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
            background: var(--bg-base);
            transition: background 0.4s ease, color 0.3s ease;
        }
        
        body.light-mode .text-slate-100 { color: #1e1b4b !important; }
        body.light-mode .text-slate-200 { color: #1e293b !important; }
        body.light-mode .text-slate-300 { color: #334155 !important; }
        body.light-mode .text-slate-400 { color: #4f46e5 !important; }
        body.light-mode .text-text-muted { color: #6366f1 !important; }
        body.light-mode #preloader { background: #f1f5f9; }
        body.light-mode .bg-slate-950/20 { background: rgba(241,245,249,0.6) !important; }
        body.light-mode .bg-slate-900/30 { background: rgba(99,102,241,0.08) !important; }
        body.light-mode .bg-slate-900/40 { background: rgba(99,102,241,0.08) !important; }
        body.light-mode .border-white/5, body.light-mode .border-white/10 { border-color: rgba(99,102,241,0.15) !important; }
        body.light-mode .divide-white/5 > * { border-color: rgba(99,102,241,0.12) !important; }
        body.light-mode .text-slate-500 { color: #6366f1 !important; }
        body.light-mode .text-slate-600 { color: #4338ca !important; }
        body.light-mode .hover\:bg-white/5:hover { background: rgba(99,102,241,0.06) !important; }

        .glass-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
            transition: background 0.3s ease, border 0.3s ease;
        }
        body.light-mode .glass-card {
            box-shadow: 0 4px 24px 0 rgba(99,102,241,0.1);
        }
        
        .glass-input {
            background: var(--input-bg) !important;
            border: 1px solid var(--input-border) !important;
            color: var(--text-main) !important;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            transition: all 0.3s ease;
        }
        .glass-input:focus {
            background: rgba(15, 23, 42, 0.7) !important;
            border-color: rgba(99, 102, 241, 0.55) !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
            color: var(--text-main) !important;
        }
        body.light-mode .glass-input { color: #1e1b4b !important; }
        body.light-mode .glass-input:focus { background: rgba(255,255,255,0.95) !important; }

        .glass-btn {
            background: var(--btn-bg);
            border: 1px solid var(--btn-border);
            color: var(--btn-color);
            transition: all 0.3s ease;
        }
        body.light-mode .glass-btn:hover {
            background: rgba(99,102,241,0.15);
            border-color: rgba(99,102,241,0.3);
        }
        .glass-btn.active, .glass-btn-active {
            background: rgba(99, 102, 241, 0.4);
            border-color: rgba(129, 140, 248, 0.5);
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.25);
        }
        
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
        
        #preloader {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #0f172a; z-index: 99999;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            transition: opacity 0.5s ease;
        }
        .spinner {
            width: 40px; height: 40px; border: 4px solid rgba(99, 102, 241, 0.2);
            border-top-color: #6366f1; border-radius: 50%; animation: spin 1s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* JSON formatting colors */
        .json-key { color: #f472b6; }
        .json-string { color: #34d399; }
        .json-number { color: #60a5fa; }
        .json-boolean { color: #facc15; }
        .json-null { color: #94a3b8; }
    </style>
</head>
<body class="text-text-main min-h-screen flex relative overflow-x-hidden">

<div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
    <div class="absolute top-[10%] left-[20%] w-[350px] h-[350px] rounded-full bg-indigo-600/20 blur-[80px]"></div>
    <div class="absolute bottom-[20%] right-[10%] w-[400px] h-[400px] rounded-full bg-pink-600/15 blur-[90px]"></div>
</div>

<!-- Preloader -->
<div id="preloader">
    <div class="spinner"></div>
    <p class="mt-4 text-xs font-bold text-slate-400">Memuat Portal API...</p>
</div>

<!-- Layout Wrapper -->
<div class="flex flex-1 w-full mx-auto relative min-h-screen">

    <!-- Unified Sidebar Navigation -->
    <aside class="w-24 glass-card flex flex-col items-center py-8 fixed left-0 top-0 h-screen z-[1000] justify-between hidden md:flex border-y-0 border-l-0">
        <div class="flex flex-col items-center gap-12">
            <a class="w-14 h-14 rounded-2xl glass-btn flex items-center justify-center text-slate-400 hover:text-slate-100 hover:bg-white/5 transition-all focus:outline-none" href="index.php" title="Dashboard">
                <span class="material-symbols-outlined text-3xl font-bold">school</span>
            </a>
            
            <nav class="flex flex-col gap-6 items-center w-full px-3">
                <a class="w-12 h-12 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-100 hover:bg-white/5 transition-all shrink-0 relative group" href="akademik.php" title="Data Mahasiswa">
                    <span class="material-symbols-outlined text-[24px]">group</span>
                    <span class="absolute left-full ml-4 px-2 py-1 bg-slate-800 text-[10px] text-white rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50">Data Mahasiswa</span>
                </a>
                <a class="w-12 h-12 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-100 hover:bg-white/5 transition-all shrink-0 relative group" href="laporan.php" title="Perbandingan Kelas">
                    <span class="material-symbols-outlined text-[24px]">analytics</span>
                    <span class="absolute left-full ml-4 px-2 py-1 bg-slate-800 text-[10px] text-white rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50">Perbandingan Kelas</span>
                </a>
                <a class="w-12 h-12 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-100 hover:bg-white/5 transition-all shrink-0 relative group" href="tren.php" title="Analisis Tren Cohort">
                    <span class="material-symbols-outlined text-[24px]">timeline</span>
                    <span class="absolute left-full ml-4 px-2 py-1 bg-slate-800 text-[10px] text-white rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50">Tren IPK Angkatan</span>
                </a>
                <a class="w-12 h-12 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-100 hover:bg-white/5 transition-all shrink-0 relative group" href="skema.php" title="Skema Data Warehouse">
                    <span class="material-symbols-outlined text-[24px]">schema</span>
                    <span class="absolute left-full ml-4 px-2 py-1 bg-slate-800 text-[10px] text-white rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50">Skema DW</span>
                </a>
                <a class="w-12 h-12 rounded-xl flex items-center justify-center text-primary-light glass-btn-active shrink-0 relative group" href="api_docs.php" title="Dokumentasi & Tester API">
                    <span class="material-symbols-outlined text-[24px]">api</span>
                    <span class="absolute right-0 top-1/2 -translate-y-1/2 w-1.5 h-6 bg-[#6366f1] rounded-l-full shadow-[0_0_12px_rgba(99,102,241,0.8)]"></span>
                    <span class="absolute left-full ml-4 px-2 py-1 bg-slate-800 text-[10px] text-white rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50">Dokumentasi & Tester API</span>
                </a>
            </nav>
        </div>

        <div class="flex flex-col gap-6 w-full px-4 items-center">
            <button onclick="openSettingsModal()" class="w-12 h-12 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-100 hover:bg-white/5 transition-all focus:outline-none" title="Pengaturan">
                <span class="material-symbols-outlined text-[22px]">settings</span>
            </button>
            <a class="w-12 h-12 rounded-xl flex items-center justify-center text-red-400 hover:text-red-350 hover:bg-red-500/10 transition-all shrink-0" href="logout.php" title="Logout">
                <span class="material-symbols-outlined text-[22px]">logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 md:pl-[96px] p-6 md:p-8 w-full min-h-screen flex flex-col gap-6">

        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 w-full glass-card rounded-2xl px-6 py-5">
            <div>
                <h1 class="text-base font-extrabold text-slate-100 tracking-tight flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#6366f1]">api</span> Dokumentasi dan Tester API
                </h1>
                <p class="text-xs font-bold text-text-muted mt-0.5">Uji coba endpoint Data Warehouse dengan antarmuka mirip Postman serta pemantauan log request secara real-time.</p>
            </div>
            
            <div class="flex items-center gap-4">
                <button id="themeToggleBtn" onclick="toggleTheme()" class="w-9 h-9 rounded-xl flex items-center justify-center transition-all glass-btn">
                    <span id="themeIcon" class="material-symbols-outlined text-[18px] text-amber-400">light_mode</span>
                </button>
                <div class="relative flex items-center">
                    <button onclick="toggleProfileDropdown(event)" class="w-9 h-9 rounded-full p-0.5 bg-slate-900/30 hover:scale-105 transition-transform outline-none focus:outline-none border border-white/10">
                        <img alt="Profile" class="w-full h-full rounded-full object-cover" src="https://ui-avatars.com/api/?name=Admin+TKJ&background=1e1b4b&color=6366f1"/>
                    </button>
                    
                    <!-- Profile Dropdown Box -->
                    <div id="profileDropdown" class="hidden absolute right-0 top-12 w-64 glass-card rounded-2xl p-5 flex flex-col gap-4 z-[9999]">
                        <div class="flex items-center gap-3 border-b border-white/10 pb-3">
                            <div class="w-10 h-10 rounded-full p-0.5 bg-slate-900/30">
                                <img alt="Profile" class="w-full h-full rounded-full object-cover" src="https://ui-avatars.com/api/?name=Admin+TKJ&background=1e1b4b&color=6366f1"/>
                            </div>
                            <div class="text-left">
                                <h4 class="text-xs font-extrabold text-slate-100">Admin TKJ</h4>
                                <span class="text-[9px] font-bold text-text-muted bg-white/5 px-2 py-0.5 rounded-full mt-0.5 inline-block">Administrator</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-2">
                            <a href="index.php" class="flex items-center gap-2.5 py-2 px-3 rounded-xl hover:bg-white/5 text-xs font-bold text-slate-300 hover:text-white transition-all">
                                <span class="material-symbols-outlined text-[18px]">grid_view</span> Dashboard
                            </a>
                            <a href="akademik.php" class="flex items-center gap-2.5 py-2 px-3 rounded-xl hover:bg-white/5 text-xs font-bold text-slate-300 hover:text-white transition-all">
                                <span class="material-symbols-outlined text-[18px]">group</span> Data Mahasiswa
                            </a>
                            <a href="laporan.php" class="flex items-center gap-2.5 py-2 px-3 rounded-xl hover:bg-white/5 text-xs font-bold text-slate-300 hover:text-white transition-all">
                                <span class="material-symbols-outlined text-[18px]">analytics</span> Perbandingan Kelas
                            </a>
                            <a href="tren.php" class="flex items-center gap-2.5 py-2 px-3 rounded-xl hover:bg-white/5 text-xs font-bold text-slate-300 hover:text-white transition-all">
                                <span class="material-symbols-outlined text-[18px]">timeline</span> Tren IPK Angkatan
                            </a>
                            <a href="skema.php" class="flex items-center gap-2.5 py-2 px-3 rounded-xl hover:bg-white/5 text-xs font-bold text-slate-300 hover:text-white transition-all">
                                <span class="material-symbols-outlined text-[18px]">schema</span> Skema DW
                            </a>
                            <button onclick="openSettingsModal()" class="flex items-center gap-2.5 py-2 px-3 rounded-xl hover:bg-white/5 text-xs font-bold text-slate-300 hover:text-white w-full text-left transition-all">
                                <span class="material-symbols-outlined text-[18px]">settings</span> Pengaturan
                            </button>
                        </div>
                        <a href="logout.php" class="flex items-center gap-2.5 py-2.5 px-3 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-xs font-bold text-red-400 transition-all justify-center">
                            <span class="material-symbols-outlined text-[16px]">logout</span> Keluar
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Selection Menu -->
        <div class="flex gap-2 border-b border-white/5 pb-1 select-none">
            <button onclick="switchTab('docs')" id="tabBtnDocs" class="px-5 py-2.5 font-bold text-xs rounded-xl glass-btn active transition-all">
                Workspace Tester
            </button>
            <button onclick="switchTab('dashboard')" id="tabBtnDashboard" class="px-5 py-2.5 font-bold text-xs rounded-xl glass-btn transition-all">
                Dashboard API Key
            </button>
        </div>

        <!-- TAB CONTENT: POSTMAN WORKSPACE TESTER -->
        <div id="tabContentDocs" class="grid grid-cols-1 lg:grid-cols-12 gap-6 w-full items-stretch">
            
            <!-- Left Sidebar: Endpoints (3 cols) -->
            <div class="lg:col-span-3 rounded-[2rem] p-5 glass-card flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-extrabold text-slate-100 uppercase tracking-wider">Collections</span>
                    <span class="text-[9px] font-bold text-text-muted bg-white/5 px-2 py-0.5 rounded-full">TKJ API</span>
                </div>
                
                <div class="relative flex items-center">
                    <span class="material-symbols-outlined absolute left-3 text-slate-400 text-sm">search</span>
                    <input type="text" id="endpointSearch" oninput="filterEndpoints()" placeholder="Filter endpoints..." class="w-full pl-9 pr-4 py-2 border-0 rounded-xl text-xs font-semibold focus:ring-0 text-slate-200 outline-none transition-all placeholder:text-slate-500 glass-input"/>
                </div>

                <div class="flex flex-col gap-1 overflow-y-auto max-h-[450px]" id="endpointsContainer">
                    <!-- GET summary -->
                    <div onclick="selectSidebarEndpoint('summary')" class="endpoint-item p-2.5 rounded-xl hover:bg-white/5 cursor-pointer flex items-center gap-2 transition-all" data-name="summary">
                        <span class="text-[9px] font-extrabold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-1.5 py-0.5 rounded shrink-0">GET</span>
                        <span class="text-xs font-bold text-slate-350 truncate">summary</span>
                    </div>
                    <!-- GET chart_ipk -->
                    <div onclick="selectSidebarEndpoint('chart_ipk')" class="endpoint-item p-2.5 rounded-xl hover:bg-white/5 cursor-pointer flex items-center gap-2 transition-all" data-name="chart_ipk">
                        <span class="text-[9px] font-extrabold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-1.5 py-0.5 rounded shrink-0">GET</span>
                        <span class="text-xs font-bold text-slate-350 truncate">chart_ipk</span>
                    </div>
                    <!-- GET chart_predikat -->
                    <div onclick="selectSidebarEndpoint('chart_predikat')" class="endpoint-item p-2.5 rounded-xl hover:bg-white/5 cursor-pointer flex items-center gap-2 transition-all" data-name="chart_predikat">
                        <span class="text-[9px] font-extrabold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-1.5 py-0.5 rounded shrink-0">GET</span>
                        <span class="text-xs font-bold text-slate-350 truncate">chart_predikat</span>
                    </div>
                    <!-- GET students -->
                    <div onclick="selectSidebarEndpoint('students')" class="endpoint-item p-2.5 rounded-xl hover:bg-white/5 cursor-pointer flex items-center gap-2 transition-all" data-name="students">
                        <span class="text-[9px] font-extrabold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-1.5 py-0.5 rounded shrink-0">GET</span>
                        <span class="text-xs font-bold text-slate-350 truncate">students</span>
                    </div>
                    <!-- GET classes -->
                    <div onclick="selectSidebarEndpoint('classes')" class="endpoint-item p-2.5 rounded-xl hover:bg-white/5 cursor-pointer flex items-center gap-2 transition-all" data-name="classes">
                        <span class="text-[9px] font-extrabold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-1.5 py-0.5 rounded shrink-0">GET</span>
                        <span class="text-xs font-bold text-slate-350 truncate">classes</span>
                    </div>
                    <!-- GET students_summary -->
                    <div onclick="selectSidebarEndpoint('students_summary')" class="endpoint-item p-2.5 rounded-xl hover:bg-white/5 cursor-pointer flex items-center gap-2 transition-all" data-name="students_summary">
                        <span class="text-[9px] font-extrabold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-1.5 py-0.5 rounded shrink-0">GET</span>
                        <span class="text-xs font-bold text-slate-350 truncate">students_summary</span>
                    </div>
                    <!-- GET comparison -->
                    <div onclick="selectSidebarEndpoint('comparison')" class="endpoint-item p-2.5 rounded-xl hover:bg-white/5 cursor-pointer flex items-center gap-2 transition-all" data-name="comparison">
                        <span class="text-[9px] font-extrabold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-1.5 py-0.5 rounded shrink-0">GET</span>
                        <span class="text-xs font-bold text-slate-350 truncate">comparison</span>
                    </div>
                    <!-- GET tren_angkatan -->
                    <div onclick="selectSidebarEndpoint('tren_angkatan')" class="endpoint-item p-2.5 rounded-xl hover:bg-white/5 cursor-pointer flex items-center gap-2 transition-all" data-name="tren_angkatan">
                        <span class="text-[9px] font-extrabold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-1.5 py-0.5 rounded shrink-0">GET</span>
                        <span class="text-xs font-bold text-slate-350 truncate">tren_angkatan</span>
                    </div>
                </div>
            </div>

            <!-- Right Main Pane: URL Bar, Params, Headers, Body, and Response (9 cols) -->
            <div class="lg:col-span-9 flex flex-col gap-6">
                
                <!-- URL Request Bar -->
                <div class="rounded-[2rem] p-6 glass-card flex flex-col gap-4">
                    <div class="flex flex-col md:flex-row items-stretch gap-2.5">
                        <!-- Method Select -->
                        <div class="relative shrink-0">
                            <select id="requestMethod" class="border-0 rounded-2xl px-5 py-3.5 glass-input text-slate-200 cursor-pointer font-extrabold text-xs w-full md:w-32">
                                <option value="GET">GET</option>
                                <option value="POST">POST</option>
                                <option value="PUT">PUT</option>
                                <option value="DELETE">DELETE</option>
                            </select>
                        </div>
                        
                        <!-- URL Input -->
                        <div class="relative flex-1">
                            <input type="text" id="requestUrl" value="<?= API_BASE ?>?type=summary" class="w-full border-0 rounded-2xl py-3.5 px-4 text-xs font-mono text-slate-200 outline-none transition-all glass-input" placeholder="http://..."/>
                        </div>

                        <!-- Send Button -->
                        <button onclick="sendPostmanRequest()" class="bg-[#6366f1] hover:bg-[#5a5de0] text-white rounded-2xl px-8 py-3.5 font-bold text-xs uppercase tracking-wider flex items-center justify-center gap-2 shrink-0 transition-colors shadow-[0_0_15px_rgba(99,102,241,0.25)]">
                            Send
                        </button>
                    </div>

                    <!-- Builder Tabs (Params, Headers, Body) -->
                    <div class="flex gap-4 border-b border-white/5 pb-1 mt-4 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 select-none">
                        <button onclick="switchBuilderTab('params')" id="builderTabBtnParams" class="py-2 border-b-2 border-[#6366f1] text-slate-100 transition-all">Params</button>
                        <button onclick="switchBuilderTab('headers')" id="builderTabBtnHeaders" class="py-2 border-b-2 border-transparent hover:text-slate-200 transition-all">Headers</button>
                        <button onclick="switchBuilderTab('body')" id="builderTabBtnBody" class="py-2 border-b-2 border-transparent hover:text-slate-200 transition-all">Body</button>
                    </div>

                    <!-- Builder Content Panes -->
                    <div class="mt-3">
                        <!-- Params Pane -->
                        <div id="builderPaneParams" class="flex flex-col gap-3">
                            <div class="flex justify-between items-center text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
                                <span>Query Parameters</span>
                                <button onclick="addNewParamRow()" class="text-[#818cf8] hover:underline">Add Parameter</button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs text-left border-collapse" id="paramsTable">
                                    <thead>
                                        <tr class="bg-slate-950/20 text-slate-400 font-extrabold text-[9px] uppercase tracking-wider border-b border-white/5">
                                            <th class="px-4 py-2 w-1/3">Key</th>
                                            <th class="px-4 py-2 w-1/2">Value</th>
                                            <th class="px-4 py-2 w-16 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-white/5 font-bold text-slate-200">
                                        <!-- Injected by selectSidebarEndpoint or manually added -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Headers Pane -->
                        <div id="builderPaneHeaders" class="hidden flex flex-col gap-3">
                            <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Headers</div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs text-left border-collapse">
                                    <thead>
                                        <tr class="bg-slate-950/20 text-slate-400 font-extrabold text-[9px] uppercase tracking-wider border-b border-white/5">
                                            <th class="px-4 py-2">Key</th>
                                            <th class="px-4 py-2">Value</th>
                                            <th class="px-4 py-2">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-white/5 font-bold text-slate-200 font-mono text-[11px]">
                                        <tr>
                                            <td class="px-4 py-3 text-indigo-400">key</td>
                                            <td class="px-4 py-3 text-slate-200" id="headerKeyValue"><?= API_KEY ?></td>
                                            <td class="px-4 py-3 text-slate-400 font-sans">API Key token akses (Wajib)</td>
                                        </tr>
                                        <tr>
                                            <td class="px-4 py-3 text-indigo-400">Content-Type</td>
                                            <td class="px-4 py-3 text-slate-200">application/json</td>
                                            <td class="px-4 py-3 text-slate-400 font-sans">Format media payload</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Body Pane -->
                        <div id="builderPaneBody" class="hidden flex flex-col gap-3">
                            <div class="flex justify-between items-center text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
                                <span>Request Body (raw JSON)</span>
                                <span class="text-slate-500 lowercase">(Hanya dikirim untuk POST/PUT/DELETE)</span>
                            </div>
                            <textarea id="requestBodyText" class="border-0 rounded-2xl w-full px-4 py-3 glass-input font-mono text-[11px]" rows="6" placeholder='{&#10;  "data": "value"&#10;}'></textarea>
                        </div>
                    </div>
                </div>

                <!-- Postman Response Console -->
                <div class="rounded-[2rem] p-6 glass-card flex flex-col gap-4">
                    <div class="flex justify-between items-center border-b border-white/5 pb-3">
                        <span class="text-[10px] font-extrabold text-slate-100 uppercase tracking-wider">Response Panel</span>
                        
                        <!-- Response Info Badge Indicators -->
                        <div class="flex items-center gap-3 text-[10px] font-bold">
                            <div class="flex items-center gap-1">
                                <span class="text-text-muted">Status:</span>
                                <span id="responseStatusBadge" class="text-slate-400 px-2 py-0.5 bg-white/5 rounded">--</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="text-text-muted">Time:</span>
                                <span id="responseTimeBadge" class="text-slate-400 px-2 py-0.5 bg-white/5 rounded">0 ms</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="text-text-muted">Size:</span>
                                <span id="responseSizeBadge" class="text-slate-400 px-2 py-0.5 bg-white/5 rounded">0 B</span>
                            </div>
                        </div>
                    </div>

                    <!-- JSON Editor / Result Display -->
                    <div class="bg-slate-950/50 border border-white/5 p-5 rounded-2xl overflow-x-auto max-h-96 min-h-[150px] relative">
                        <pre class="font-mono text-[11px] leading-relaxed text-slate-300 select-all" id="postmanResponse">Silakan klik Send untuk mengirim request.</pre>
                    </div>
                </div>

            </div>
        </div>

        <!-- TAB CONTENT: API KEY DASHBOARD -->
        <div id="tabContentDashboard" class="hidden flex flex-col gap-6">
            <!-- 3 Stat Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full select-none">
                <div class="rounded-[2rem] p-6 glass-card flex flex-col gap-2">
                    <span class="text-[9px] font-extrabold text-text-muted uppercase tracking-wider">Request Terpakai</span>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-3xl font-extrabold text-slate-100" id="requestsUsedVal"><?= $initial_requests_used ?></span>
                        <span class="text-xs font-bold text-text-muted">/ 200</span>
                    </div>
                </div>
                <div class="rounded-[2rem] p-6 glass-card flex flex-col gap-2">
                    <span class="text-[9px] font-extrabold text-text-muted uppercase tracking-wider">Limit Request</span>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-3xl font-extrabold text-slate-100">200</span>
                        <span class="text-xs font-bold text-text-muted">REQUEST</span>
                    </div>
                </div>
                <div class="rounded-[2rem] p-6 glass-card flex flex-col gap-2">
                    <span class="text-[9px] font-extrabold text-text-muted uppercase tracking-wider">Status Key</span>
                    <div class="flex items-center gap-2 mt-1.5 text-emerald-400">
                        <span class="material-symbols-outlined font-bold text-lg">check_circle</span>
                        <span class="text-base font-extrabold uppercase tracking-wide">Aktif</span>
                    </div>
                </div>
            </div>

            <!-- Key Management -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 w-full items-start">
                
                <!-- Manage Key Box (7 cols) -->
                <div class="lg:col-span-7 rounded-[2rem] p-6 glass-card flex flex-col gap-5">
                    <h3 class="text-xs font-extrabold text-slate-100 uppercase tracking-wider">API Key Saya</h3>
                    
                    <div class="flex items-center gap-3 p-3.5 bg-slate-950/20 border border-white/5 rounded-2xl font-mono text-sm">
                        <span class="material-symbols-outlined text-indigo-400 text-lg shrink-0">vpn_key</span>
                        <span id="dashboardApiKeyText" class="text-slate-200 select-all flex-1 break-all font-bold"><?= API_KEY ?></span>
                        <button onclick="copyToClipboard('dashboardApiKeyText')" class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase glass-btn tracking-wider">Copy</button>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <button onclick="copyToClipboard('dashboardApiKeyText')" class="flex-1 text-slate-100 rounded-2xl py-3.5 font-bold text-xs glass-btn uppercase tracking-wider flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-sm">content_copy</span> Copy Key
                        </button>
                        <button onclick="regenerateApiKey()" class="flex-1 text-slate-100 rounded-2xl py-3.5 font-bold text-xs glass-btn uppercase tracking-wider flex items-center justify-center gap-2 bg-gradient-to-r from-indigo-500/20 to-pink-500/20 hover:from-indigo-500/35 hover:to-pink-500/35 border-indigo-500/30">
                            <span class="material-symbols-outlined text-sm">refresh</span> Generate Key Baru
                        </button>
                    </div>

                    <div class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-2xl text-[10px] font-bold text-amber-400 leading-relaxed flex items-start gap-2.5">
                        <span class="material-symbols-outlined text-base">info</span>
                        <span>Generate key baru tidak mereset kuota request. Key lama akan langsung tidak berlaku dan dinonaktifkan seketika.</span>
                    </div>

                    <!-- Usage progress bar -->
                    <div class="flex flex-col gap-2 mt-2 font-bold">
                        <div class="flex justify-between text-[10px]">
                            <span class="text-text-muted" id="progressText">0 request terpakai</span>
                            <span class="text-slate-200" id="progressPercent">0% terpakai</span>
                        </div>
                        <div class="w-full bg-white/5 rounded-full h-2 overflow-hidden border border-white/5">
                            <div id="progressBar" class="bg-gradient-to-r from-indigo-500 to-pink-500 h-full w-[0%] transition-all duration-500"></div>
                        </div>
                    </div>
                </div>

                <!-- Usage Snippet (5 cols) -->
                <div class="lg:col-span-5 rounded-[2rem] p-6 glass-card flex flex-col gap-4">
                    <h3 class="text-xs font-extrabold text-slate-100 uppercase tracking-wider">Cara Pakai API</h3>
                    <p class="text-xs font-semibold text-text-muted leading-relaxed">
                        Kirim header key di setiap REST request Anda menggunakan snippet di bawah:
                    </p>
                    
                    <div class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider border-b border-white/5 pb-2">Contoh Javascript (Fetch)</div>
                    <pre class="bg-slate-950/40 border border-white/5 text-[10px] p-4 rounded-xl text-slate-300 font-mono overflow-x-auto">
fetch("<?= API_BASE ?>?type=summary", {
  method: "GET",
  headers: {
    "key": "<?= API_KEY ?>"
  }
})
.then(res => res.json())
.then(data => console.log(data));
                    </pre>
                </div>
            </div>

            <!-- API Request Logs Table -->
            <div class="rounded-[2rem] p-6 glass-card flex flex-col gap-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-xs font-extrabold text-slate-100 uppercase tracking-wider">Recent API Logs (Terbaru)</h3>
                    <button onclick="clearLogs()" class="px-4 py-2 rounded-xl text-[10px] font-extrabold uppercase glass-btn border-red-500/20 hover:bg-red-500/10 text-red-400">
                        Hapus Semua Log
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-950/20 text-slate-400 font-extrabold text-[10px] uppercase tracking-wider border-b border-white/5">
                                <th class="px-4 py-3">Timestamp</th>
                                <th class="px-4 py-3">Endpoint</th>
                                <th class="px-4 py-3">Method</th>
                                <th class="px-4 py-3">IP Address</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody" class="font-bold text-slate-200 divide-y divide-white/5">
                            <?php if (empty($initial_logs)): ?>
                                <tr id="noLogsRow"><td colspan="5" class="px-4 py-6 text-center text-text-muted">Belum ada aktivitas request API yang tercatat.</td></tr>
                            <?php else: ?>
                                <?php foreach ($initial_logs as $log): ?>
                                    <tr>
                                        <td class="px-4 py-3.5 font-mono text-[11px] text-slate-450"><?= htmlspecialchars($log['requested_at']) ?></td>
                                        <td class="px-4 py-3.5 font-mono text-indigo-400">?type=<?= htmlspecialchars($log['endpoint']) ?></td>
                                        <td class="px-4 py-3.5"><span class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2 py-0.5 rounded text-[10px]"><?= htmlspecialchars($log['method']) ?></span></td>
                                        <td class="px-4 py-3.5 font-mono text-[11px]"><?= htmlspecialchars($log['ip_address']) ?></td>
                                        <td class="px-4 py-3.5">
                                            <?php 
                                            $st = $log['status_code'];
                                            if ($st == 200) {
                                                echo "<span class='text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded'>200 OK</span>";
                                            } elseif ($st == 429) {
                                                echo "<span class='text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded'>429 Limit Exceeded</span>";
                                            } else {
                                                echo "<span class='text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded'>{$st} Error</span>";
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <footer class="mt-auto py-6 border-t border-white/5 flex flex-col sm:flex-row justify-between items-center gap-4 text-[10px] font-bold text-text-muted">
            <p>&copy; 2026 Teknik Komputer dan Jaringan PNUP. Dokumentasi dan Tester API.</p>
        </footer>

    </main>
</div>

<!-- Custom Settings Modal Placeholder for consistency -->
<div id="settingsModal" class="hidden fixed inset-0 z-[10000] items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-950/40 backdrop-blur-md transition-opacity" onclick="closeSettingsModal()"></div>
    <div class="glass-modal glass-card rounded-[2.5rem] p-8 max-w-md w-full relative z-10 scale-95 transition-all duration-300">
        <h3 class="text-xl font-extrabold text-slate-100 mb-6">Pengaturan Sistem</h3>
        <p class="text-xs font-bold text-text-muted mb-6">Gunakan mode preferensi di pojok kanan atas untuk mengubah tema secara instan.</p>
        <button onclick="closeSettingsModal()" class="w-full text-slate-200 rounded-2xl py-3.5 font-bold text-xs glass-btn uppercase tracking-wider">Tutup</button>
    </div>
</div>

<script>
let isDarkMode = localStorage.getItem('bi_theme') !== 'light';

function applyTheme() {
    if (isDarkMode) {
        document.body.classList.remove('light-mode');
        const icon = document.getElementById('themeIcon');
        if (icon) { icon.textContent = 'light_mode'; icon.className = 'material-symbols-outlined text-[18px] text-amber-400'; }
    } else {
        document.body.classList.add('light-mode');
        const icon = document.getElementById('themeIcon');
        if (icon) { icon.textContent = 'dark_mode'; icon.className = 'material-symbols-outlined text-[18px] text-slate-600'; }
    }
}

function toggleTheme() {
    isDarkMode = !isDarkMode;
    localStorage.setItem('bi_theme', isDarkMode ? 'dark' : 'light');
    applyTheme();
}

function toggleProfileDropdown(e) {
    e.stopPropagation();
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('hidden');
}

window.addEventListener('click', () => {
    const dropdown = document.getElementById('profileDropdown');
    if (dropdown && !dropdown.classList.contains('hidden')) {
        dropdown.classList.add('hidden');
    }
});

// Tab switcher logic
function switchTab(tab) {
    const tabDocs = document.getElementById('tabContentDocs');
    const tabDashboard = document.getElementById('tabContentDashboard');
    const btnDocs = document.getElementById('tabBtnDocs');
    const btnDashboard = document.getElementById('tabBtnDashboard');

    if (tab === 'docs') {
        tabDocs.classList.remove('hidden');
        tabDashboard.classList.add('hidden');
        btnDocs.classList.add('active');
        btnDashboard.classList.remove('active');
    } else {
        tabDocs.classList.add('hidden');
        tabDashboard.classList.remove('hidden');
        btnDocs.classList.remove('active');
        btnDashboard.classList.add('active');
        // Fetch fresh stats from server
        fetchMetrics();
    }
}

// Request Builder Pane Switching
function switchBuilderTab(pane) {
    document.getElementById('builderPaneParams').classList.add('hidden');
    document.getElementById('builderPaneHeaders').classList.add('hidden');
    document.getElementById('builderPaneBody').classList.add('hidden');
    
    document.getElementById('builderTabBtnParams').className = 'py-2 border-b-2 border-transparent hover:text-slate-200 transition-all';
    document.getElementById('builderTabBtnHeaders').className = 'py-2 border-b-2 border-transparent hover:text-slate-200 transition-all';
    document.getElementById('builderTabBtnBody').className = 'py-2 border-b-2 border-transparent hover:text-slate-200 transition-all';
    
    if (pane === 'params') {
        document.getElementById('builderPaneParams').classList.remove('hidden');
        document.getElementById('builderTabBtnParams').className = 'py-2 border-b-2 border-[#6366f1] text-slate-100 transition-all';
    } else if (pane === 'headers') {
        document.getElementById('builderPaneHeaders').classList.remove('hidden');
        document.getElementById('builderTabBtnHeaders').className = 'py-2 border-b-2 border-[#6366f1] text-slate-100 transition-all';
    } else if (pane === 'body') {
        document.getElementById('builderPaneBody').classList.remove('hidden');
        document.getElementById('builderTabBtnBody').className = 'py-2 border-b-2 border-[#6366f1] text-slate-100 transition-all';
    }
}

// Endpoint list definition (similar to Postman collections)
const endpointsMeta = {
    'summary': [
        { key: 'type', value: 'summary' },
        { key: 'angkatan', value: '' },
        { key: 'kelas', value: '' }
    ],
    'chart_ipk': [
        { key: 'type', value: 'chart_ipk' },
        { key: 'angkatan', value: '' },
        { key: 'kelas', value: '' }
    ],
    'chart_predikat': [
        { key: 'type', value: 'chart_predikat' }
    ],
    'students': [
        { key: 'type', value: 'students' },
        { key: 'kelas', value: '' }
    ],
    'classes': [
        { key: 'type', value: 'classes' }
    ],
    'students_summary': [
        { key: 'type', value: 'students_summary' },
        { key: 'kelas', value: '' }
    ],
    'comparison': [
        { key: 'type', value: 'comparison' }
    ],
    'tren_angkatan': [
        { key: 'type', value: 'tren_angkatan' },
        { key: 'angkatan', value: '' }
    ]
};

// Select sidebar endpoint to populate Postman fields
function selectSidebarEndpoint(endpointKey) {
    // Highlight sidebar item
    document.querySelectorAll('.endpoint-item').forEach(el => {
        el.classList.remove('bg-white/10');
    });
    
    // Find the clicked element
    const sidebarItems = document.querySelectorAll('.endpoint-item');
    for (let item of sidebarItems) {
        if (item.getAttribute('data-name') === endpointKey) {
            item.classList.add('bg-white/10');
            break;
        }
    }
    
    // Set method to GET
    document.getElementById('requestMethod').value = 'GET';
    
    // Populate parameter rows
    const tbody = document.querySelector('#paramsTable tbody');
    tbody.innerHTML = '';
    
    const paramsList = endpointsMeta[endpointKey] || [];
    paramsList.forEach(p => {
        addParamRow(p.key, p.value);
    });
    
    // Re-sync full URL input
    updateUrlFromParams();
}

function addParamRow(key = '', val = '') {
    const tbody = document.querySelector('#paramsTable tbody');
    const tr = document.createElement('tr');
    tr.className = 'border-b border-white/5';
    tr.innerHTML = `
        <td class="px-4 py-2">
            <input type="text" value="${key}" oninput="updateUrlFromParams()" placeholder="key" class="w-full bg-transparent border-0 p-0 text-xs font-semibold text-indigo-400 focus:ring-0 placeholder:text-slate-650"/>
        </td>
        <td class="px-4 py-2">
            <input type="text" value="${val}" oninput="updateUrlFromParams()" placeholder="value" class="w-full bg-transparent border-0 p-0 text-xs font-semibold text-slate-200 focus:ring-0 placeholder:text-slate-650"/>
        </td>
        <td class="px-4 py-2 text-center">
            <button onclick="removeParamRow(this)" class="text-rose-400 hover:text-rose-300 font-bold">Delete</button>
        </td>
    `;
    tbody.appendChild(tr);
}

function addNewParamRow() {
    addParamRow();
}

function removeParamRow(btn) {
    btn.closest('tr').remove();
    updateUrlFromParams();
}

function updateUrlFromParams() {
    const base = "<?= API_BASE ?>";
    const tbody = document.querySelector('#paramsTable tbody');
    const rows = tbody.querySelectorAll('tr');
    let queries = [];
    
    rows.forEach(r => {
        const inputs = r.querySelectorAll('input');
        const k = inputs[0].value.trim();
        const v = inputs[1].value.trim();
        if (k !== '') {
            queries.push(`${encodeURIComponent(k)}=${encodeURIComponent(v)}`);
        }
    });
    
    const urlInput = document.getElementById('requestUrl');
    if (queries.length > 0) {
        urlInput.value = `${base}?${queries.join('&')}`;
    } else {
        urlInput.value = base;
    }
}

// Simple endpoint filtration in sidebar
function filterEndpoints() {
    const q = document.getElementById('endpointSearch').value.toLowerCase();
    document.querySelectorAll('.endpoint-item').forEach(el => {
        const text = el.innerText.toLowerCase();
        if (text.includes(q)) {
            el.classList.remove('hidden');
        } else {
            el.classList.add('hidden');
        }
    });
}

// Pretty print JSON formatter with syntax coloring
function syntaxHighlight(json) {
    if (typeof json !== 'string') {
        json = JSON.stringify(json, undefined, 2);
    }
    json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+-]?\d+)?)/g, function (match) {
        let cls = 'json-number';
        if (/^"/.test(match)) {
            if (/:$/.test(match)) {
                cls = 'json-key';
            } else {
                cls = 'json-string';
            }
        } else if (/true|false/.test(match)) {
            cls = 'json-boolean';
        } else if (/null/.test(match)) {
            cls = 'json-null';
        }
        return '<span class="' + cls + '">' + match + '</span>';
    });
}

// Send Postman Simulator request
async function sendPostmanRequest() {
    const url = document.getElementById('requestUrl').value.trim();
    const method = document.getElementById('requestMethod').value;
    const key = "<?= API_KEY ?>";
    
    if (url === '') {
        alert("Mohon isi URL target API.");
        return;
    }
    
    // Visual Pending state
    const responseBox = document.getElementById('postmanResponse');
    responseBox.textContent = "Mengirim request...";
    
    const statusBadge = document.getElementById('responseStatusBadge');
    statusBadge.textContent = "PENDING";
    statusBadge.className = "text-[10px] text-amber-400 font-bold px-2 py-0.5 bg-amber-500/10 rounded";
    
    const timeBadge = document.getElementById('responseTimeBadge');
    timeBadge.textContent = "0 ms";
    
    const sizeBadge = document.getElementById('responseSizeBadge');
    sizeBadge.textContent = "0 B";

    const tStart = performance.now();
    
    try {
        const fetchOptions = {
            method: method,
            headers: {
                'key': key
            }
        };
        
        // Add JSON body if method is POST/PUT/DELETE
        if (method !== 'GET') {
            const bodyContent = document.getElementById('requestBodyText').value.trim();
            if (bodyContent !== '') {
                try {
                    JSON.parse(bodyContent);
                    fetchOptions.body = bodyContent;
                    fetchOptions.headers['Content-Type'] = 'application/json';
                } catch (err) {
                    alert("JSON body tidak valid!");
                    return;
                }
            }
        }
        
        const res = await fetch(url, fetchOptions);
        const tEnd = performance.now();
        const duration = Math.round(tEnd - tStart);
        timeBadge.textContent = `${duration} ms`;
        
        const responseStatusText = `${res.status} ${res.statusText}`;
        statusBadge.textContent = responseStatusText;
        
        if (res.status === 200) {
            statusBadge.className = "text-[10px] text-emerald-400 font-bold px-2 py-0.5 bg-emerald-500/10 rounded";
        } else if (res.status === 429) {
            statusBadge.className = "text-[10px] text-amber-400 font-bold px-2 py-0.5 bg-amber-500/10 rounded";
        } else {
            statusBadge.className = "text-[10px] text-rose-400 font-bold px-2 py-0.5 bg-rose-500/10 rounded";
        }
        
        const rawText = await res.text();
        const sizeKB = (rawText.length / 1024).toFixed(2);
        sizeBadge.textContent = `${sizeKB} KB`;
        
        try {
            const parsedJson = JSON.parse(rawText);
            responseBox.innerHTML = syntaxHighlight(parsedJson);
        } catch (e) {
            responseBox.textContent = rawText;
        }
        
    } catch (error) {
        const tEnd = performance.now();
        timeBadge.textContent = `${Math.round(tEnd - tStart)} ms`;
        statusBadge.textContent = "ERROR";
        statusBadge.className = "text-[10px] text-rose-400 font-bold px-2 py-0.5 bg-rose-500/10 rounded";
        responseBox.textContent = "Koneksi gagal ke target URL.\nDetail: " + error;
    }
}

// Fetch dynamic database counts and recent logs list from DB via POST
async function fetchMetrics() {
    try {
        const formData = new FormData();
        formData.append('action', 'get_metrics');
        
        const res = await fetch('api_docs.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.status === 'success') {
            const used = parseInt(data.used);
            const remaining = 200 - used;
            
            document.getElementById('requestsUsedVal').textContent = used;
            document.getElementById('progressText').textContent = `${used} request terpakai`;
            
            const percent = Math.min(100, Math.round((used / 200) * 100));
            document.getElementById('progressPercent').textContent = `${percent}% terpakai`;
            document.getElementById('progressBar').style.width = `${percent}%`;
            
            // Re-render logs table
            const tbody = document.getElementById('logsTableBody');
            tbody.innerHTML = '';
            
            if (data.logs.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-6 text-center text-text-muted">Belum ada aktivitas request API yang tercatat.</td></tr>`;
            } else {
                data.logs.forEach(log => {
                    let statusBadgeHTML = '';
                    const st = parseInt(log.status_code);
                    if (st === 200) {
                        statusBadgeHTML = `<span class='text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded'>200 OK</span>`;
                    } else if (st === 429) {
                        statusBadgeHTML = `<span class='text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded'>429 Limit Exceeded</span>`;
                    } else {
                        statusBadgeHTML = `<span class='text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded'>${st} Error</span>`;
                    }
                    
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="px-4 py-3.5 font-mono text-[11px] text-slate-450">${log.requested_at}</td>
                        <td class="px-4 py-3.5 font-mono text-indigo-400">?type=${log.endpoint}</td>
                        <td class="px-4 py-3.5"><span class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2 py-0.5 rounded text-[10px]">${log.method}</span></td>
                        <td class="px-4 py-3.5 font-mono text-[11px]">${log.ip_address}</td>
                        <td class="px-4 py-3.5">${statusBadgeHTML}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        }
    } catch (e) {
        console.error("Gagal memuat metrik dari server:", e);
    }
}

async function regenerateApiKey() {
    if (!confirm("Apakah Anda yakin ingin membuat API Key baru? Key yang lama akan langsung hangus.")) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'generate_key');
        
        const res = await fetch('api_docs.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await res.json();
        if (data.status === 'success') {
            document.getElementById('dashboardApiKeyText').textContent = data.new_key;
            document.getElementById('headerKeyValue').textContent = data.new_key;
            alert("API Key baru berhasil dibuat!");
            // Refresh counts
            fetchMetrics();
        } else {
            alert("Gagal membuat API Key baru: " + data.message);
        }
    } catch (err) {
        alert("Gagal membuat API Key baru: " + err);
    }
}

async function clearLogs() {
    if (!confirm("Apakah Anda yakin ingin menghapus semua log request? Tindakan ini akan mereset kuota request Anda kembali ke 0.")) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'clear_logs');
        
        const res = await fetch('api_docs.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await res.json();
        if (data.status === 'success') {
            alert("Log aktivitas berhasil dihapus!");
            fetchMetrics();
        } else {
            alert("Gagal menghapus log: " + data.message);
        }
    } catch (e) {
        alert("Gagal menghapus log: " + e);
    }
}

function copyToClipboard(elementId) {
    const text = document.getElementById(elementId).textContent;
    navigator.clipboard.writeText(text).then(() => {
        alert("Teks berhasil disalin!");
    }).catch(err => {
        alert("Gagal menyalin teks: " + err);
    });
}

window.addEventListener('load', () => {
    applyTheme();
    // Select default endpoint summary on load
    selectSidebarEndpoint('summary');
    // Initialize progress bar
    fetchMetrics();
    
    setTimeout(() => {
        const pre = document.getElementById('preloader');
        if(pre) {
            pre.style.opacity = '0';
            setTimeout(() => pre.style.display = 'none', 500);
        }
    }, 500);
});

function openSettingsModal() {
    const modal = document.getElementById('settingsModal');
    modal.classList.replace('hidden', 'flex');
    setTimeout(() => {
        modal.querySelector('.glass-modal').classList.replace('scale-95', 'scale-100');
    }, 10);
}

function closeSettingsModal() {
    const modal = document.getElementById('settingsModal');
    modal.querySelector('.glass-modal').classList.replace('scale-100', 'scale-95');
    setTimeout(() => {
        modal.classList.replace('flex', 'hidden');
    }, 300);
}
</script>

<!-- ====== MOBILE BOTTOM NAV ====== -->
<nav class="md:hidden fixed bottom-6 left-6 right-6 h-16 bg-[#0f172a]/85 backdrop-blur-lg border border-white/15 rounded-2xl flex items-center justify-around px-2 z-[9999] shadow-2xl">
    <a href="index.php" class="flex flex-col items-center justify-center text-slate-400 hover:text-slate-200">
        <span class="material-symbols-outlined text-[20px]">school</span>
        <span class="text-[8px] font-bold mt-0.5">Home</span>
    </a>
    <a href="akademik.php" class="flex flex-col items-center justify-center text-slate-400 hover:text-slate-200">
        <span class="material-symbols-outlined text-[20px]">group</span>
        <span class="text-[8px] font-bold mt-0.5">Mhs</span>
    </a>
    <a href="laporan.php" class="flex flex-col items-center justify-center text-slate-400 hover:text-slate-200">
        <span class="material-symbols-outlined text-[20px]">analytics</span>
        <span class="text-[8px] font-bold mt-0.5">Lapor</span>
    </a>
    <a href="tren.php" class="flex flex-col items-center justify-center text-slate-400 hover:text-slate-200">
        <span class="material-symbols-outlined text-[20px]">timeline</span>
        <span class="text-[8px] font-bold mt-0.5">Tren</span>
    </a>
    <a href="skema.php" class="flex flex-col items-center justify-center text-slate-400 hover:text-slate-200">
        <span class="material-symbols-outlined text-[20px]">schema</span>
        <span class="text-[8px] font-bold mt-0.5">Skema</span>
    </a>
    <a href="api_docs.php" class="flex flex-col items-center justify-center text-primary-light px-2 py-1 rounded-xl bg-white/5">
        <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1">api</span>
        <span class="text-[8px] font-bold mt-0.5">API</span>
    </a>
</nav>

<!-- ====== SETTINGS MODAL ====== -->
<div id="settingsModal" class="hidden fixed inset-0 z-[10000] items-center justify-center p-4">
    <!-- Backdrop with blur -->
    <div class="absolute inset-0 bg-slate-950/40 backdrop-blur-md transition-opacity" onclick="closeSettingsModal()"></div>
    
    <!-- Modal Content -->
    <div class="glass-modal glass-card rounded-[2.5rem] p-8 md:p-10 max-w-md w-full relative z-10 scale-95 transition-all duration-300">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h3 class="text-xl font-extrabold text-slate-100">Pengaturan Sistem</h3>
                <p class="text-xs font-bold text-text-muted mt-1">Konfigurasi & informasi portal akademik</p>
            </div>
            <button onclick="closeSettingsModal()" class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-200 glass-btn">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
        
        <div class="flex flex-col gap-6">
            <!-- Section 1: API Config -->
            <div class="p-4 rounded-2xl bg-slate-900/40 border border-white/5 flex flex-col gap-3">
                <span class="text-[10px] text-text-muted font-bold uppercase tracking-wider">Koneksi API Warehouse</span>
                <div class="flex flex-col gap-1">
                    <span class="text-xs font-semibold text-slate-400">API Endpoint</span>
                    <span class="text-xs font-bold text-indigo-300 break-all select-all mt-1"><?= API_BASE ?></span>
                </div>
            </div>

            <!-- Section 2: Account Details -->
            <div class="flex flex-col gap-3">
                <span class="text-[10px] text-text-muted font-bold uppercase tracking-wider">Informasi Akun</span>
                <div class="flex justify-between items-center py-2 border-b border-white/5">
                    <span class="text-xs font-semibold text-slate-400">Role Pengguna</span>
                    <span class="text-xs font-bold text-primary-light">Administrator</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-white/5">
                    <span class="text-xs font-semibold text-slate-400">Username</span>
                    <span class="text-xs font-bold text-slate-200">admin</span>
                </div>
            </div>
        </div>
        
        <div class="flex gap-4 mt-8">
            <button onclick="closeSettingsModal()" class="flex-1 py-3.5 text-slate-350 rounded-2xl font-bold text-xs glass-btn">
                Tutup
            </button>
        </div>
    </div>
</div>
</body>
</html>
