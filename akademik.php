<?php
// File: akademik.php
session_start();

// Redireksi jika belum login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$just_logged_in = false;
if (isset($_SESSION['just_logged_in']) && $_SESSION['just_logged_in'] === true) {
    $just_logged_in = true;
    unset($_SESSION['just_logged_in']);
}

require_once 'config.php';

// Ambil list kelas
$res_classes = callAPI(API_BASE . "?type=classes");
$classes = is_array($res_classes) ? $res_classes : [];

// Ambil summary untuk stats
$res_summary = callAPI(API_BASE . "?type=students_summary");
$summary = is_array($res_summary) ? $res_summary : [
    "total_aktif" => 0, "total_alumni" => 0, "ipk_tertinggi" => 0, "ipk_terendah" => 0
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Data Akademik – TKJ PNUP</title>
    <script>
        (function() {
            const justLoggedIn = <?= $just_logged_in ? 'true' : 'false' ?>;
            if (justLoggedIn) {
                sessionStorage.setItem('tab_session_active', '1');
            } else {
                if (!sessionStorage.getItem('tab_session_active')) {
                    window.location.href = 'logout.php';
                }
            }
        })();
    </script>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .material-symbols-outlined { 
            font-family: 'Material Symbols Outlined'; 
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24; 
        }
        /* ===== THEME VARIABLES ===== */
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
        /* Light mode text overrides */
        body.light-mode .text-slate-100 { color: #1e1b4b !important; }
        body.light-mode .text-slate-200 { color: #1e293b !important; }
        body.light-mode .text-slate-300 { color: #334155 !important; }
        body.light-mode .text-slate-400 { color: #4f46e5 !important; }
        body.light-mode .text-text-muted { color: #6366f1 !important; }
        body.light-mode #preloader { background: #f1f5f9; }
        body.light-mode .bg-slate-950\/20 { background: rgba(241,245,249,0.6) !important; }
        body.light-mode .bg-slate-900\/30 { background: rgba(99,102,241,0.08) !important; }
        body.light-mode .bg-slate-900\/40 { background: rgba(99,102,241,0.08) !important; }
        body.light-mode .border-white\/5, body.light-mode .border-white\/10 { border-color: rgba(99,102,241,0.15) !important; }
        body.light-mode .divide-white\/5 > * { border-color: rgba(99,102,241,0.12) !important; }
        body.light-mode .text-slate-500 { color: #6366f1 !important; }
        body.light-mode .text-slate-600 { color: #4338ca !important; }
        /* Badge light mode */
        body.light-mode .badge-active   { background: rgba(16,185,129,0.15); color: #059669; }
        body.light-mode .badge-alumni   { background: rgba(99,102,241,0.15); color: #4f46e5; }
        body.light-mode .badge-inactive { background: rgba(244,63,94,0.12);  color: #e11d48; }
        /* Table row hover in light mode */
        body.light-mode .hover\:bg-white\/5:hover { background: rgba(99,102,241,0.06) !important; }
        body.light-mode .hover\:bg-white\/5:hover .text-primary-light { color: #4f46e5; }
        /* Mobile nav in light mode */
        body.light-mode nav.md\:hidden { background: rgba(241,245,249,0.9) !important; border-color: rgba(99,102,241,0.15) !important; }

        .badge { 
            display: inline-flex; 
            align-items: center; 
            padding: 4px 12px; 
            border-radius: 9999px; 
            font-size: 10px; 
            font-weight: 800; 
            letter-spacing: 0.05em; 
            text-transform: uppercase;
        }
        .badge-active   { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .badge-alumni   { background: rgba(99, 102, 241, 0.15); color: #818cf8; }
        .badge-inactive { background: rgba(244, 63, 94, 0.15); color: #fb7185; }
        
        .ipk-bar { height: 6px; border-radius: 999px; background: rgba(255, 255, 255, 0.08); overflow: hidden; }
        body.light-mode .ipk-bar { background: rgba(99,102,241,0.12); }
        .ipk-fill { 
            height: 100%; 
            border-radius: 999px; 
            background: linear-gradient(90deg, #6366f1, #ec4899); 
            transition: width 1s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        
        /* Glassmorphism Styles */
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
        /* Fix browser autofill override */
        .glass-input:-webkit-autofill,
        .glass-input:-webkit-autofill:hover,
        .glass-input:-webkit-autofill:focus {
            -webkit-text-fill-color: #e2e8f0 !important;
            -webkit-box-shadow: 0 0 0px 1000px rgba(15, 23, 42, 0.7) inset !important;
            transition: background-color 5000s ease-in-out 0s;
        }
        .glass-input::placeholder {
            color: #64748b !important;
            opacity: 1;
        }
        .glass-btn {
            background: var(--btn-bg);
            border: 1px solid var(--btn-border);
            color: var(--btn-color);
            transition: all 0.3s ease;
        }
        .glass-btn:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.15);
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
        
        /* Hide scrollbars */
        ::-webkit-scrollbar { display: none; }
        * {
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE/Edge */
        }
        
        /* Preloader */
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
    </style>
</head>
<body class="text-text-main min-h-screen flex relative overflow-x-hidden">

<!-- Background Glow Blobs -->
<div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
    <div class="absolute top-[10%] left-[20%] w-[350px] h-[350px] rounded-full bg-indigo-600/20 blur-[80px]"></div>
    <div class="absolute bottom-[20%] right-[10%] w-[400px] h-[400px] rounded-full bg-pink-600/15 blur-[90px]"></div>
    <div class="absolute top-[60%] left-[5%] w-[300px] h-[300px] rounded-full bg-purple-600/15 blur-[75px]"></div>
</div>

<!-- Preloader -->
<div id="preloader">
    <div class="spinner"></div>
    <p class="mt-4 text-xs font-bold text-slate-450">Memuat Data...</p>
</div>

<!-- ====== LAYOUT WRAPPER ====== -->
<div class="flex flex-1 w-full mx-auto relative min-h-screen">

    <!-- ====== SIDEBAR ====== -->
    <aside class="w-24 glass-card flex flex-col items-center py-8 fixed left-0 top-0 h-screen z-[1000] justify-between hidden md:flex border-y-0 border-l-0">
        <div class="flex flex-col items-center gap-12">
            <!-- School Logo & Dashboard Link -->
            <a class="w-14 h-14 rounded-2xl glass-btn flex items-center justify-center text-slate-400 hover:text-slate-100 hover:bg-white/5 transition-all focus:outline-none relative group" href="index.php" title="Dashboard">
                <span class="material-symbols-outlined text-3xl font-bold">school</span>
            </a>
            
            <!-- Menu Navigation -->
            <nav class="flex flex-col gap-6 items-center w-full px-3">
                <a class="w-12 h-12 rounded-xl flex items-center justify-center text-primary-light glass-btn-active shrink-0 relative group" href="akademik.php" title="Data Mahasiswa">
                    <span class="material-symbols-outlined text-[24px]">group</span>
                    <!-- Glowing indicator -->
                    <span class="absolute right-0 top-1/2 -translate-y-1/2 w-1.5 h-6 bg-[#6366f1] rounded-l-full shadow-[0_0_12px_rgba(99,102,241,0.8)]"></span>
                    <span class="absolute left-full ml-4 px-2 py-1 bg-slate-800 text-[10px] text-white rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50">Data Mahasiswa</span>
                </a>
                <a class="w-12 h-12 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-100 hover:bg-white/5 transition-all shrink-0 relative group" href="laporan.php" title="Perbandingan Kelas">
                    <span class="material-symbols-outlined text-[24px]">analytics</span>
                    <span class="absolute left-full ml-4 px-2 py-1 bg-slate-800 text-[10px] text-white rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50">Perbandingan Kelas</span>
                </a>
                <a class="w-12 h-12 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-100 hover:bg-white/5 transition-all shrink-0 relative group" href="tren.php" title="Tren IPK Angkatan">
                    <span class="material-symbols-outlined text-[24px]">timeline</span>
                    <span class="absolute left-full ml-4 px-2 py-1 bg-slate-800 text-[10px] text-white rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50">Tren IPK Angkatan</span>
                </a>
                <a class="w-12 h-12 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-100 hover:bg-white/5 transition-all shrink-0 relative group" href="skema.php" title="Skema Data Warehouse">
                    <span class="material-symbols-outlined text-[24px]">schema</span>
                    <span class="absolute left-full ml-4 px-2 py-1 bg-slate-800 text-[10px] text-white rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50">Skema DW</span>
                </a>
                <a class="w-12 h-12 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-100 hover:bg-white/5 transition-all shrink-0 relative group" href="api_docs.php" title="Dokumentasi & Tester API">
                    <span class="material-symbols-outlined text-[24px]">api</span>
                    <span class="absolute left-full ml-4 px-2 py-1 bg-slate-800 text-[10px] text-white rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50">Dokumentasi & Tester API</span>
                </a>
            </nav>
        </div>

        <!-- Bottom Icons -->
        <div class="flex flex-col gap-6 w-full px-4 items-center">
            <button onclick="openSettingsModal()" class="w-12 h-12 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-100 hover:bg-white/5 transition-all focus:outline-none" title="Pengaturan">
                <span class="material-symbols-outlined text-[22px]">settings</span>
            </button>
            <a class="w-12 h-12 rounded-xl flex items-center justify-center text-red-400 hover:text-red-350 hover:bg-red-500/10 transition-all shrink-0" href="logout.php" title="Logout">
                <span class="material-symbols-outlined text-[22px]">logout</span>
            </a>
        </div>
    </aside>

    <!-- ====== MAIN CONTENT AREA ====== -->
    <main class="flex-1 md:pl-[96px] p-6 md:p-8 pb-28 md:pb-8 w-full min-h-screen flex flex-col gap-6 relative">

        <!-- ====== HEADER / TOP BAR ====== -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 w-full glass-card rounded-2xl px-6 py-5">
            <div>
                <h1 class="text-base font-extrabold text-slate-100 tracking-tight flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#6366f1]">school</span> Data Akademik
                </h1>
                <p class="text-xs font-bold text-text-muted mt-0.5">Klik nama mahasiswa untuk melihat grafik IPK per semester.</p>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Theme Toggle Button -->
                <button id="themeToggleBtn" onclick="toggleTheme()" title="Ganti Tema" class="w-9 h-9 rounded-xl flex items-center justify-center transition-all glass-btn">
                    <span id="themeIcon" class="material-symbols-outlined text-[18px] text-amber-400">light_mode</span>
                </button>

                <!-- Add Student Button -->
                <button onclick="openCrudModal('add')" class="text-primary-light rounded-xl py-2.5 px-5 font-bold text-xs flex items-center gap-1.5 transition-all glass-btn">
                    <span class="material-symbols-outlined text-[16px] font-bold">add</span> Tambah Mahasiswa
                </button>

                <!-- Admin Avatar -->
                <div class="flex items-center">
                    <button onclick="toggleProfileDropdown(event)" class="w-9 h-9 rounded-full p-0.5 bg-slate-900/30 hover:scale-105 transition-transform outline-none focus:outline-none border border-white/10">
                        <img alt="Profile" class="w-full h-full rounded-full object-cover"
                             src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username'] ?? 'Admin TKJ') ?>&background=1e1b4b&color=6366f1"/>
                    </button>
                </div>
            </div>
        </div>

        <!-- Profile Dropdown Box -->
        <div id="profileDropdown" class="hidden absolute right-6 md:right-8 top-24 w-64 glass-card rounded-2xl p-5 flex flex-col gap-4 z-[9999]">
            <div class="flex items-center gap-3 border-b border-white/10 pb-3">
                <div class="w-10 h-10 rounded-full p-0.5 bg-slate-900/30">
                    <img alt="Profile" class="w-full h-full rounded-full object-cover" src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username'] ?? 'Admin TKJ') ?>&background=1e1b4b&color=6366f1"/>
                </div>
                <div class="text-left">
                    <h4 class="text-xs font-extrabold text-slate-100"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin TKJ') ?></h4>
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

        <!-- ====== FILTER & SEARCH BAR ====== -->
        <div class="rounded-[2rem] p-6 glass-card flex flex-col xl:flex-row gap-4 justify-between items-stretch xl:items-center">
            <!-- Search field -->
            <div class="relative flex items-center flex-1 max-w-md">
                <input id="searchInput" type="text" placeholder="Cari nama atau NIM..."
                       class="w-full border-0 rounded-2xl py-3 px-4 pl-11 text-xs font-semibold focus:ring-0 text-slate-200 outline-none transition-all placeholder:text-slate-400 glass-input"/>
                <span class="material-symbols-outlined text-slate-400 absolute left-4 text-[18px]">search</span>
            </div>

            <!-- Filters & Buttons group -->
            <div class="flex flex-wrap items-center gap-4 select-none">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-slate-400 text-[18px]">class</span>
                    <select id="classSelector" onchange="loadStudents()" class="border-0 rounded-2xl pl-4 pr-10 py-2.5 text-xs font-bold focus:ring-0 text-slate-200 cursor-pointer glass-input">
                        <option value="">Pilih Kelas</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <select id="filterStatus" class="border-0 rounded-2xl pl-4 pr-10 py-2.5 text-xs font-bold focus:ring-0 text-slate-200 cursor-pointer glass-input">
                    <option value="">Semua Status</option>
                    <option value="Aktif">Aktif</option>
                    <option value="Lulus">Lulus</option>
                    <option value="Tidak Aktif">Tidak Aktif</option>
                </select>

                <button onclick="resetFilter()" class="py-2.5 px-4 rounded-2xl text-xs font-bold text-slate-200 transition-all flex items-center gap-1 glass-btn">
                    <span class="material-symbols-outlined text-[14px]">restart_alt</span> Reset
                </button>

                <div class="px-4 py-2.5 rounded-2xl text-xs font-extrabold text-[#818cf8] bg-slate-900/40 border border-white/5">
                    Menampilkan <span id="rowCount" class="text-indigo-400">0</span> mahasiswa
                </div>
            </div>
        </div>

        <!-- ====== STUDENT TABLE SECTIONS ====== -->
        <div id="studentsContainer" class="flex flex-col gap-6 w-full">
            <!-- Loading State -->
            <div id="loadingState" class="hidden text-center py-12">
                <div class="spinner mx-auto mb-4"></div>
                <p class="text-xs font-bold text-slate-400">Mengambil data mahasiswa...</p>
            </div>
            
            <!-- Empty State -->
            <div id="emptyState" class="rounded-[2rem] p-12 text-center text-text-muted glass-card">
                <span class="material-symbols-outlined text-5xl block mb-3 text-slate-600">class</span>
                <p class="text-xs font-bold">Silakan pilih kelas terlebih dahulu.</p>
            </div>
            
            <!-- Table Content -->
            <div id="tableContainer" class="hidden class-section flex flex-col gap-4">
                <div class="flex justify-between items-center px-4">
                    <h3 id="currentClassTitle" class="text-xs font-extrabold text-slate-100 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-primary"></span>
                        -
                    </h3>
                </div>

                <div class="rounded-[2rem] overflow-hidden glass-card">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-950/20 text-slate-400 font-extrabold text-[10px] uppercase tracking-wider border-b border-white/5">
                                    <th class="px-6 py-4 w-12 text-center">No</th>
                                    <th class="px-6 py-4">NIM</th>
                                    <th class="px-6 py-4">Nama Mahasiswa</th>
                                    <th class="px-6 py-4">Angkatan</th>
                                    <th class="px-6 py-4 w-32">IPK</th>
                                    <th class="px-6 py-4">Predikat</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4 text-center w-28">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="studentTableBody" class="divide-y divide-white/5 font-bold text-slate-200">
                                <!-- Dynamic rows via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====== SUMMARY STATS CARDS ====== -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 w-full mt-2">
            <!-- Card 1: Total Mahasiswa Aktif -->
            <div class="rounded-[2rem] p-6 flex flex-col gap-4 glass-card group hover:scale-[1.02] transition-transform">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-slate-900/30 text-[#818cf8]">
                        <span class="material-symbols-outlined text-[22px] font-bold">group</span>
                    </div>
                    <div>
                        <p class="text-[10px] font-extrabold text-text-muted tracking-wide whitespace-nowrap uppercase">Total Mahasiswa Aktif</p>
                        <p id="stat_total_aktif" class="text-2xl font-extrabold text-slate-100 mt-1"><?= $summary['total_aktif'] ?? 0 ?></p>
                    </div>
                </div>
            </div>

            <!-- Card 2: Total Alumni -->
            <div class="rounded-[2rem] p-6 flex flex-col gap-4 glass-card group hover:scale-[1.02] transition-transform">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-slate-900/30 text-pink-400">
                        <span class="material-symbols-outlined text-[22px] font-bold">workspace_premium</span>
                    </div>
                    <div>
                        <p class="text-[10px] font-extrabold text-text-muted tracking-wide whitespace-nowrap uppercase">Total Alumni</p>
                        <p id="stat_total_alumni" class="text-2xl font-extrabold text-slate-100 mt-1"><?= $summary['total_alumni'] ?? 0 ?></p>
                    </div>
                </div>
            </div>

            <!-- Card 3: IPK Tertinggi -->
            <div class="rounded-[2rem] p-6 flex flex-col gap-4 glass-card group hover:scale-[1.02] transition-transform">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-slate-900/30 text-emerald-400">
                        <span class="material-symbols-outlined text-[22px] font-bold">trending_up</span>
                    </div>
                    <div>
                        <p class="text-[10px] font-extrabold text-text-muted tracking-wide whitespace-nowrap uppercase">IPK Tertinggi</p>
                        <p id="stat_ipk_tertinggi" class="text-2xl font-extrabold text-slate-100 mt-1"><?= number_format((float)($summary['ipk_tertinggi'] ?? 0), 2) ?></p>
                    </div>
                </div>
            </div>

            <!-- Card 4: IPK Terendah -->
            <div class="rounded-[2rem] p-6 flex flex-col gap-4 glass-card group hover:scale-[1.02] transition-transform">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center bg-slate-900/30 text-rose-450">
                        <span class="material-symbols-outlined text-[22px] font-bold">trending_down</span>
                    </div>
                    <div>
                        <p class="text-[10px] font-extrabold text-text-muted tracking-wide whitespace-nowrap uppercase">IPK Terendah</p>
                        <p id="stat_ipk_terendah" class="text-2xl font-extrabold text-slate-100 mt-1"><?= number_format((float)($summary['ipk_terendah'] ?? 0), 2) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====== FOOTER ====== -->
        <footer class="mt-auto py-6 border-t border-white/5 flex flex-col sm:flex-row justify-between items-center gap-4 text-[10px] font-bold text-text-muted">
            <p>&copy; 2026 Teknik Komputer dan Jaringan PNUP. Academic Data Integration.</p>
        </footer>

    </main>
</div>


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
                    <span class="text-xs font-bold text-[#818cf8]">Administrator</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-white/5">
                    <span class="text-xs font-semibold text-slate-400">Username</span>
                    <span class="text-xs font-bold text-slate-200">admin</span>
                </div>
            </div>

            <!-- Section 3: Customization -->
            <div class="flex flex-col gap-3">
                <span class="text-[10px] text-text-muted font-bold uppercase tracking-wider">Preferensi Tampilan</span>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-semibold text-slate-400">Tema Gelap / Terang</span>
                    <button onclick="toggleTheme()" id="darkModeToggleSettings" class="w-10 h-6 rounded-full bg-primary p-0.5 flex items-center transition-all focus:outline-none">
                        <div id="darkModeThumb" class="w-5 h-5 rounded-full bg-white shadow-md transform translate-x-4 transition-transform"></div>
                    </button>
                </div>
            </div>
        </div>
        
        <button onclick="closeSettingsModal()" class="w-full text-indigo-300 rounded-2xl py-3.5 mt-8 font-bold text-xs glass-btn uppercase tracking-wider">
            Simpan & Selesai
        </button>
    </div>
</div>

<!-- ====== PREMIUM MODAL GRAFIK IPK ====== -->
<div id="chartModal" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4 transition-all">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-slate-950/40 backdrop-blur-md transition-opacity" onclick="closeChart()"></div>
    
    <div class="glass-card rounded-[2.5rem] w-full max-w-2xl overflow-hidden relative z-10 transition-all transform scale-95 duration-300">
        <div class="flex items-center justify-between px-8 py-6 border-b border-white/5">
            <div>
                <h3 class="font-extrabold text-slate-100 text-lg" id="modalTitle">Grafik IPK</h3>
                <p class="text-xs text-text-muted font-bold mt-0.5">Perkembangan IPS & IPK per semester</p>
            </div>
            <button onclick="closeChart()" class="w-10 h-10 rounded-full flex items-center justify-center text-slate-400 hover:text-rose-455 transition-colors glass-btn">
                <span class="material-symbols-outlined text-[20px] font-bold">close</span>
            </button>
        </div>
        <div class="p-8">
            <div id="modalLoading" class="flex flex-col items-center justify-center py-12 gap-3">
                <div class="w-8 h-8 border-3 border-[#6366f1] border-t-transparent rounded-full animate-spin"></div>
                <p class="text-xs font-bold text-text-muted">Memuat data grafik...</p>
            </div>
            <div id="modalChart" class="hidden relative h-64 w-full">
                <canvas id="studentChart"></canvas>
            </div>
            <p id="modalEmpty" class="hidden text-center text-text-muted text-xs py-8 px-6 bg-slate-900/40 border border-white/5 rounded-2xl leading-relaxed">
                <span class="material-symbols-outlined block text-3xl mb-2 text-slate-500">info</span>
                Grafik perkembangan IPS dan IPK belum tersedia untuk mahasiswa ini.<br>
                <span class="opacity-80 block mt-1">(Bisa jadi karena statusnya Alumni yang hanya memiliki data IPK Akhir, atau data semester belum diinput).</span>
            </p>
        </div>
    </div>
</div>

<!-- ====== PREMIUM MODAL CRUD MAHASISWA ====== -->
<div id="crudModal" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4 transition-all">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-slate-950/40 backdrop-blur-md transition-opacity" onclick="closeCrudModal()"></div>
    
    <div class="glass-card rounded-[2.5rem] w-full max-w-md overflow-hidden relative z-10 transition-all transform scale-95 duration-300">
        <div class="flex items-center justify-between px-8 py-6 border-b border-white/5">
            <h3 class="font-extrabold text-slate-100 text-lg" id="crudModalTitle">Tambah Mahasiswa</h3>
            <button onclick="closeCrudModal()" class="w-10 h-10 rounded-full flex items-center justify-center text-slate-400 hover:text-rose-455 transition-colors glass-btn">
                <span class="material-symbols-outlined text-[20px] font-bold">close</span>
            </button>
        </div>
        <div class="p-8">
            <form id="crudForm" onsubmit="saveStudent(event)">
                <input type="hidden" id="crud_sk" name="sk_mahasiswa">
                <div class="flex flex-col gap-5">
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-2.5">NIM</label>
                        <input type="text" id="crud_nim" name="nim" required class="w-full border-0 rounded-2xl py-3 px-4 text-xs font-semibold focus:ring-0 text-slate-200 outline-none transition-all placeholder:text-slate-400 glass-input">
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-2.5">Nama Mahasiswa</label>
                        <input type="text" id="crud_nama" name="nama_mahasiswa" required class="w-full border-0 rounded-2xl py-3 px-4 text-xs font-semibold focus:ring-0 text-slate-200 outline-none transition-all placeholder:text-slate-400 glass-input">
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-2.5">Angkatan</label>
                        <input type="number" id="crud_angkatan" name="angkatan" required class="w-full border-0 rounded-2xl py-3 px-4 text-xs font-semibold focus:ring-0 text-slate-200 outline-none transition-all placeholder:text-slate-400 glass-input">
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-2.5">Kelas</label>
                        <input type="text" id="crud_kelas" name="kelas" required class="w-full border-0 rounded-2xl py-3 px-4 text-xs font-semibold focus:ring-0 text-slate-200 outline-none transition-all placeholder:text-slate-400 glass-input">
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-2.5">Status Akademik</label>
                        <select id="crud_status" name="status_akademik" class="w-full border-0 rounded-2xl py-3 pl-4 pr-10 text-xs font-bold focus:ring-0 text-slate-200 outline-none transition-all cursor-pointer glass-input">
                            <option value="Aktif">Aktif</option>
                            <option value="Lulus">Lulus (Alumni)</option>
                            <option value="Tidak Aktif">Tidak Aktif</option>
                        </select>
                    </div>
                    
                    <div class="mt-4 flex justify-end gap-3 border-t border-white/5 pt-6">
                        <button type="button" onclick="closeCrudModal()" class="py-3 px-5 rounded-2xl text-xs font-bold text-slate-300 transition-colors glass-btn">Batal</button>
                        <button type="submit" class="text-primary-light rounded-2xl py-3 px-6 font-bold text-xs flex items-center gap-1.5 transition-all glass-btn active">
                            <span class="material-symbols-outlined text-[16px]" id="crudBtnIcon">save</span>
                            <span id="crudBtnText">Simpan</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ====== CUSTOM CONFIRM MODAL ====== -->
<div id="confirmModal" class="hidden fixed inset-0 z-[20000] items-center justify-center p-4">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-slate-950/40 backdrop-blur-md transition-opacity" onclick="closeConfirmModal()"></div>
    
    <!-- Modal Content -->
    <div class="glass-card rounded-[2.5rem] p-8 max-w-sm w-full relative z-10 scale-95 transition-all duration-300">
        <div class="text-center">
            <div class="w-14 h-14 rounded-full bg-rose-500/10 text-rose-400 flex items-center justify-center mx-auto mb-4 border border-rose-500/20 animate-bounce">
                <span class="material-symbols-outlined text-[28px] font-bold">warning</span>
            </div>
            <h3 class="text-base font-extrabold text-slate-100" id="confirmTitle">Konfirmasi Hapus</h3>
            <p class="text-xs font-semibold text-text-muted mt-2 leading-relaxed" id="confirmMessage">Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.</p>
        </div>
        
        <div class="flex gap-3 mt-8">
            <button onclick="closeConfirmModal()" class="flex-1 py-3.5 text-slate-350 rounded-2xl font-bold text-xs glass-btn">
                Batal
            </button>
            <button id="confirmYesBtn" class="flex-1 py-3.5 bg-rose-600 hover:bg-rose-500 text-white rounded-2xl font-bold text-xs transition-colors shadow-[0_0_15px_rgba(239,68,68,0.25)]">
                Ya, Hapus
            </button>
        </div>
    </div>
</div>

<!-- ====== MOBILE BOTTOM NAV ====== -->
<nav class="md:hidden fixed bottom-6 left-6 right-6 h-16 bg-[#0f172a]/85 backdrop-blur-lg border border-white/15 rounded-2xl flex items-center justify-around px-2 z-[9999] shadow-2xl">
    <a href="index.php" class="flex flex-col items-center justify-center text-slate-400 hover:text-slate-200">
        <span class="material-symbols-outlined text-[20px]">school</span>
        <span class="text-[8px] font-bold mt-0.5">Home</span>
    </a>
    <a href="akademik.php" class="flex flex-col items-center justify-center text-primary-light px-2 py-1 rounded-xl bg-white/5">
        <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1">group</span>
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
    <a href="api_docs.php" class="flex flex-col items-center justify-center text-slate-400 hover:text-slate-200">
        <span class="material-symbols-outlined text-[20px]">api</span>
        <span class="text-[8px] font-bold mt-0.5">API</span>
    </a>
</nav>

<div id="toastContainer" class="fixed top-6 right-6 z-[99999] flex flex-col gap-3 pointer-events-none"></div>

<script>
// ====== THEME SYSTEM (synced with index.php via localStorage) ======
let isDarkMode = localStorage.getItem('bi_theme') !== 'light';

function applyTheme() {
    if (isDarkMode) {
        document.body.classList.remove('light-mode');
        const icon = document.getElementById('themeIcon');
        if (icon) { icon.textContent = 'light_mode'; icon.className = 'material-symbols-outlined text-[18px] text-amber-400'; }
        const thumb = document.getElementById('darkModeThumb');
        if (thumb) thumb.style.transform = 'translateX(1rem)';
        const btn = document.getElementById('darkModeToggleSettings');
        if (btn) btn.style.background = '#6366f1';
    } else {
        document.body.classList.add('light-mode');
        const icon = document.getElementById('themeIcon');
        if (icon) { icon.textContent = 'dark_mode'; icon.className = 'material-symbols-outlined text-[18px] text-slate-600'; }
        const thumb = document.getElementById('darkModeThumb');
        if (thumb) thumb.style.transform = 'translateX(0)';
        const btn = document.getElementById('darkModeToggleSettings');
        if (btn) btn.style.background = '#e2e8f0';
    }
}

function toggleTheme() {
    isDarkMode = !isDarkMode;
    localStorage.setItem('bi_theme', isDarkMode ? 'dark' : 'light');
    applyTheme();
    showToast(isDarkMode ? 'Mode Gelap aktif' : 'Mode Terang aktif', 'success');
}

// ====== INITIAL LOAD ======
window.addEventListener('load', () => {
    applyTheme(); // apply saved theme on load
    setTimeout(() => {
        const pre = document.getElementById('preloader');
        if(pre) {
            pre.style.opacity = '0';
            setTimeout(() => pre.style.display = 'none', 500);
        }
    }, 500);
});

// ====== GLOBAL ACTIONS ======
function toggleProfileDropdown(e) {
    e.stopPropagation();
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('hidden');
}

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
    }, 200);
}

// ====== CUSTOM TOAST SYSTEM ======
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `transform translate-x-full transition-all duration-300 ease-out glass-card pointer-events-auto flex items-center gap-3 px-5 py-4 rounded-2xl border-l-4`;
    
    let icon = 'info';
    let borderClass = 'border-l-indigo-500';
    let iconColor = 'text-indigo-400';
    if (type === 'success') {
        icon = 'check_circle';
        borderClass = 'border-l-emerald-500';
        iconColor = 'text-emerald-400';
    } else if (type === 'error') {
        icon = 'error';
        borderClass = 'border-l-rose-500';
        iconColor = 'text-rose-400';
    } else if (type === 'warning') {
        icon = 'warning';
        borderClass = 'border-l-amber-500';
        iconColor = 'text-amber-400';
    }
    
    toast.className += ` ${borderClass}`;
    
    toast.innerHTML = `
        <span class="material-symbols-outlined ${iconColor} text-[20px] shrink-0">${icon}</span>
        <div class="text-xs font-bold text-slate-200 pr-4">${message}</div>
        <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white transition-colors ml-auto shrink-0">
            <span class="material-symbols-outlined text-[16px]">close</span>
        </button>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 10);
    
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-x-full');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 4000);
}

// ====== CUSTOM CONFIRM SYSTEM ======
let confirmAction = null;
function showConfirm(title, message, onConfirm) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    
    const modal = document.getElementById('confirmModal');
    modal.classList.replace('hidden', 'flex');
    setTimeout(() => {
        modal.querySelector('.glass-card').classList.replace('scale-95', 'scale-100');
    }, 10);
    
    confirmAction = onConfirm;
}

// Close Confirm Modal
// Close Confirm Modal
function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    modal.querySelector('.glass-card').classList.replace('scale-100', 'scale-95');
    setTimeout(() => {
        modal.classList.replace('flex', 'hidden');
    }, 200);
}

document.getElementById('confirmYesBtn').addEventListener('click', () => {
    if (confirmAction) confirmAction();
    closeConfirmModal();
});

// Close dropdown on outside click
document.addEventListener('click', function() {
    const dropdown = document.getElementById('profileDropdown');
    if (dropdown) dropdown.classList.add('hidden');
});

// ====== DATA MAHASISWA & AJAX FETCH ======
let activeChart = null;
let crudMode = 'add';

async function loadSummaryStats(kelas) {
    try {
        const res = await fetch(`<?= API_BASE ?>?type=students_summary&kelas=${encodeURIComponent(kelas)}`, {
            headers: { 'key': '<?= API_KEY ?>' }
        });
        const json = await res.json();
        const stats = json.results || { total_aktif: 0, total_alumni: 0, ipk_tertinggi: 0, ipk_terendah: 0 };
        
        document.getElementById('stat_total_aktif').textContent = stats.total_aktif;
        document.getElementById('stat_total_alumni').textContent = stats.total_alumni;
        document.getElementById('stat_ipk_tertinggi').textContent = parseFloat(stats.ipk_tertinggi || 0).toFixed(2);
        document.getElementById('stat_ipk_terendah').textContent = parseFloat(stats.ipk_terendah || 0).toFixed(2);
    } catch (e) {
        console.error('Error loading stats summary:', e);
    }
}

async function loadStudents() {
    const kelas = document.getElementById('classSelector').value;
    const tableContainer = document.getElementById('tableContainer');
    const emptyState = document.getElementById('emptyState');
    const loadingState = document.getElementById('loadingState');
    const tbody = document.getElementById('studentTableBody');
    
    // Dynamically update statistics cards for this class/alumni or globally
    loadSummaryStats(kelas);
    
    if (!kelas) {
        tableContainer.classList.add('hidden');
        loadingState.classList.add('hidden');
        emptyState.classList.remove('hidden');
        emptyState.innerHTML = '<span class="material-symbols-outlined text-5xl block mb-3 text-slate-600">class</span><p class="text-xs font-bold">Silakan pilih kelas terlebih dahulu.</p>';
        document.getElementById('rowCount').textContent = '0';
        return;
    }
    
    tableContainer.classList.add('hidden');
    emptyState.classList.add('hidden');
    loadingState.classList.remove('hidden');
    
    try {
        const res = await fetch(`<?= API_BASE ?>?type=students&kelas=${encodeURIComponent(kelas)}`, {
            headers: { 'key': '<?= API_KEY ?>' }
        });
        const json = await res.json();
        const data = json.results || [];
        
        loadingState.classList.add('hidden');
        
        if (data.length === 0) {
            emptyState.classList.remove('hidden');
            emptyState.innerHTML = '<span class="material-symbols-outlined text-5xl block mb-3 text-slate-650">person_off</span><p class="text-xs font-bold">Tidak ada mahasiswa di kelas ini.</p>';
            document.getElementById('rowCount').textContent = '0';
            return;
        }
        
        document.getElementById('currentClassTitle').innerHTML = `<span class="w-2 h-2 rounded-full bg-primary"></span> ${kelas}`;
        
        tbody.innerHTML = '';
        data.forEach((mhs, i) => {
            const ipk_val = parseFloat(mhs.ipk || 0);
            const ipk_pct = Math.min(100, (ipk_val / 4.0) * 100);
            const status = mhs.status_akademik || '-';
            let badge = 'badge-inactive';
            if (status.toLowerCase() === 'aktif') badge = 'badge-active';
            else if (status.toLowerCase() === 'alumni' || status.toLowerCase() === 'lulus') badge = 'badge-alumni';
            
            const jsonData = JSON.stringify(mhs).replace(/"/g, '&quot;');
            const escapedNama = (mhs.nama_mahasiswa || '').replace(/'/g, "\\'");
            
            tbody.innerHTML += `
                <tr class="hover:bg-white/5 transition-colors student-row group" data-nim="${mhs.nim || ''}" data-nama="${(mhs.nama_mahasiswa || '').toLowerCase()}" data-angkatan="${mhs.angkatan || ''}" data-status="${status}">
                    <td class="px-6 py-4 text-text-muted row-num text-center text-xs font-semibold">${i + 1}</td>
                    <td class="px-6 py-4 font-mono text-xs text-slate-400">${mhs.nim || '-'}</td>
                    <td class="px-6 py-4 font-bold text-slate-200 cursor-pointer hover:text-primary-light transition-colors" onclick="openChart(${mhs.sk_mahasiswa}, '${escapedNama}')">${mhs.nama_mahasiswa || '-'}</td>
                    <td class="px-6 py-4 text-slate-400 text-xs">${mhs.angkatan || '-'}</td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col gap-1 w-24">
                            <span class="font-extrabold text-slate-200 text-xs">${ipk_val.toFixed(2)}</span>
                            <div class="ipk-bar w-full"><div class="ipk-fill" style="width:${ipk_pct}%"></div></div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-xs font-semibold text-slate-400">${mhs.predikat || '-'}</td>
                    <td class="px-6 py-4"><span class="badge ${badge}">${status}</span></td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex justify-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button onclick="openCrudModal('edit', ${jsonData})" class="text-slate-400 hover:text-white w-8 h-8 rounded-lg flex items-center justify-center transition-colors glass-btn" title="Edit"><span class="material-symbols-outlined text-[16px]">edit</span></button>
                            <button onclick="deleteStudent(${mhs.sk_mahasiswa})" class="text-slate-400 hover:text-rose-400 w-8 h-8 rounded-lg flex items-center justify-center transition-colors glass-btn" title="Hapus"><span class="material-symbols-outlined text-[16px]">delete</span></button>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        tableContainer.classList.remove('hidden');
        document.getElementById('rowCount').textContent = data.length;
        
        // apply filter
        filterTable();
    } catch (e) {
        console.error(e);
        loadingState.classList.add('hidden');
        emptyState.classList.remove('hidden');
        emptyState.innerHTML = '<span class="material-symbols-outlined text-5xl block mb-3 text-rose-400">error</span><p class="text-xs font-bold">Gagal memuat data kelas.</p>';
    }
}

// ====== FILTER & SEARCH ======
function filterTable() {
    const q       = document.getElementById('searchInput').value.toLowerCase();
    const status  = document.getElementById('filterStatus').value.toLowerCase();
    const tbody = document.getElementById('studentTableBody');
    if (!tbody) return;
    
    let secCount = 0;
    const rows = tbody.querySelectorAll('.student-row');
    
    rows.forEach(row => {
        const nama    = row.dataset.nama;
        const nim     = row.dataset.nim.toLowerCase();
        const stat    = row.dataset.status.toLowerCase();
        const matchQ  = !q || nama.includes(q) || nim.includes(q);
        const matchS  = !status || stat.includes(status);
        const show    = matchQ && matchS;
        row.style.display = show ? '' : 'none';
        if (show) secCount++;
    });

    document.getElementById('rowCount').textContent = secCount;
    renumberRows();
}

// Renumber rows for search
function renumberRows() {
    const tbody = document.getElementById('studentTableBody');
    if (!tbody) return;
    
    let n = 1;
    tbody.querySelectorAll('.student-row').forEach(row => {
        if (row.style.display !== 'none') {
            const rowNumEl = row.querySelector('.row-num');
            if (rowNumEl) rowNumEl.textContent = n++;
        }
    });
}

function resetFilter() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('classSelector').value = '';
    loadStudents();
}

// ====== CRUD MODAL ======
function openCrudModal(mode, data = null) {
    crudMode = mode;
    const modal = document.getElementById('crudModal');
    modal.classList.replace('hidden', 'flex');
    setTimeout(() => {
        modal.querySelector('.glass-card').classList.replace('scale-95', 'scale-100');
    }, 10);
    
    if (mode === 'edit' && data) {
        document.getElementById('crudModalTitle').textContent = 'Edit Mahasiswa';
        document.getElementById('crudBtnText').textContent = 'Simpan Perubahan';
        document.getElementById('crud_sk').value = data.sk_mahasiswa || '';
        document.getElementById('crud_nim').value = data.nim || '';
        document.getElementById('crud_nama').value = data.nama_mahasiswa || '';
        document.getElementById('crud_angkatan').value = data.angkatan || '';
        document.getElementById('crud_kelas').value = data.kelas || '';
        document.getElementById('crud_status').value = data.status_akademik || 'Aktif';
    } else {
        document.getElementById('crudModalTitle').textContent = 'Tambah Mahasiswa';
        document.getElementById('crudBtnText').textContent = 'Tambah';
        document.getElementById('crudForm').reset();
        document.getElementById('crud_sk').value = '';
    }
}

function closeCrudModal() {
    const modal = document.getElementById('crudModal');
    modal.querySelector('.glass-card').classList.replace('scale-100', 'scale-95');
    setTimeout(() => {
        modal.classList.replace('flex', 'hidden');
        if (window.location.hash === '#crudModal') {
            history.replaceState(null, null, ' ');
        }
    }, 200);
}

async function saveStudent(e) {
    e.preventDefault();
    const form = document.getElementById('crudForm');
    const formData = new FormData(form);
    const type = crudMode === 'add' ? 'create_student' : 'update_student';
    
    try {
        const res = await fetch(`<?= API_BASE ?>?type=${type}`, {
            method: 'POST',
            headers: { 'key': '<?= API_KEY ?>' },
            body: formData
        });
        const json = await res.json();
        if (json.status === 'success' || json.message) {
            showToast(json.message || 'Berhasil menyimpan data', 'success');
            closeCrudModal();
            loadStudents();
        } else {
            showToast('Error: ' + json.message, 'error');
        }
    } catch (err) {
        console.error("Save Student Error:", err);
        showToast('Gagal menghubungi server.', 'error');
    }
}

async function deleteStudent(sk) {
    showConfirm('Konfirmasi Hapus', 'Yakin ingin menghapus data mahasiswa ini?', async () => {
        const formData = new FormData();
        formData.append('sk_mahasiswa', sk);
        
        try {
            const res = await fetch(`<?= API_BASE ?>?type=delete_student`, {
                method: 'POST',
                headers: { 'key': '<?= API_KEY ?>' },
                body: formData
            });
            const json = await res.json();
            if (json.status === 'success' || json.message) {
                showToast(json.message || 'Berhasil menghapus data', 'success');
                loadStudents();
            } else {
                showToast('Error: ' + json.message, 'error');
            }
        } catch (err) {
            console.error("Delete Student Error:", err);
            showToast('Gagal menghubungi server.', 'error');
        }
    });
}

// ====== MODAL GRAFIK ======
async function openChart(sk, nama) {
    const modal = document.getElementById('chartModal');
    modal.classList.replace('hidden', 'flex');
    setTimeout(() => {
        modal.querySelector('.glass-card').classList.replace('scale-95', 'scale-100');
    }, 10);
    
    document.getElementById('modalTitle').textContent = 'Grafik IPK — ' + nama;
    document.getElementById('modalLoading').classList.remove('hidden');
    document.getElementById('modalChart').classList.add('hidden');
    document.getElementById('modalEmpty').classList.add('hidden');
    if (activeChart) { activeChart.destroy(); activeChart = null; }

    try {
        const res  = await fetch(`<?= API_BASE ?>?type=chart_ipk_mhs&sk=${sk}`, {
            headers: { 'key': '<?= API_KEY ?>' }
        });
        const json = await res.json();
        const data = json.results || [];

        document.getElementById('modalLoading').classList.add('hidden');

        if (data.length === 0) {
            document.getElementById('modalEmpty').classList.remove('hidden');
            return;
        }

        document.getElementById('modalChart').classList.remove('hidden');
        const ctx = document.getElementById('studentChart').getContext('2d');
        activeChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => d.label || d.tipe_semester + ' ' + d.tahun_ajaran),
                datasets: [
                    {
                        label: 'IPS',
                        data: data.map(d => parseFloat(d.ips || 0)),
                        backgroundColor: 'rgba(99, 102, 241, 0.25)', // Indigo glass background
                        borderColor: '#818cf8',
                        borderWidth: 2,
                        borderRadius: 8,
                        type: 'bar',
                    },
                    {
                        label: 'IPK Kumulatif',
                        data: data.map(d => parseFloat(d.ipk || 0)),
                        borderColor: '#ec4899', // Pink
                        backgroundColor: 'transparent',
                        borderWidth: 3,
                        tension: 0.3,
                        pointBackgroundColor: '#ec4899',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        type: 'line',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { 
                        min: 0, 
                        max: 4.0, 
                        grid: { color: 'rgba(255, 255, 255, 0.05)' }, 
                        border: { display: false },
                        ticks: { font: { family: 'Plus Jakarta Sans', size: 10, weight: 'bold' }, color: '#94a3b8' } 
                    },
                    x: { 
                        grid: { display: false }, 
                        border: { display: false },
                        ticks: { font: { family: 'Plus Jakarta Sans', size: 10, weight: 'bold' }, color: '#94a3b8' } 
                    }
                },
                plugins: {
                    legend: { 
                        position: 'top', 
                        labels: { 
                            font: { family: 'Plus Jakarta Sans', size: 11, weight: 'bold' }, 
                            color: '#94a3b8',
                            usePointStyle: true,
                            pointStyle: 'circle'
                        } 
                    },
                    tooltip: { 
                        backgroundColor: '#1e1b4b',
                        titleColor: '#f8fafc',
                        bodyColor: '#f1f5f9',
                        borderColor: 'rgba(255, 255, 255, 0.08)',
                        borderWidth: 1,
                        titleFont: { family: 'Plus Jakarta Sans', weight: 'bold' },
                        bodyFont: { family: 'Plus Jakarta Sans' },
                        callbacks: { label: ctx => ` ${ctx.dataset.label}: ${parseFloat(ctx.parsed.y).toFixed(2)}` } 
                    }
                }
            }
        });
    } catch (err) {
        console.error("Fetch Chart Error:", err);
        document.getElementById('modalLoading').classList.add('hidden');
        document.getElementById('modalEmpty').classList.remove('hidden');
    }
}

function closeChart() {
    const modal = document.getElementById('chartModal');
    modal.querySelector('.glass-card').classList.replace('scale-100', 'scale-95');
    setTimeout(() => {
        modal.classList.replace('flex', 'hidden');
        if (activeChart) { activeChart.destroy(); activeChart = null; }
    }, 200);
}

// Close modal handlers
document.getElementById('chartModal').addEventListener('click', function(e) {
    if (e.target === this) closeChart();
});

document.getElementById('crudModal').addEventListener('click', function(e) {
    if (e.target === this) closeCrudModal();
});

// Init Event Listeners
document.getElementById('searchInput').addEventListener('input', filterTable);
document.getElementById('filterStatus').addEventListener('change', filterTable);

filterTable();
</script>
</body>
</html>