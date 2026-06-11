<?php
// File: index.php
session_start();

// Redireksi jika belum login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

// Ambil data awal dari API untuk cadangan / SSR
$summary = callAPI(API_BASE . "?type=summary");
$ipkData = callAPI(API_BASE . "?type=chart_ipk");
$predikatData = callAPI(API_BASE . "?type=chart_predikat");

// Fallback jika API kosong
$total_mhs = $summary['total_mahasiswa'] ?? 0;
$avg_ipk   = number_format((float)($summary['rata_rata_ipk'] ?? 0), 2);
$cumlaude  = $summary['total_cumlaude'] ?? 0;

// Daftar angkatan dan kelas untuk filter
$angkatan_list = $summary['angkatan_list'] ?? [];
$kelas_list = $summary['kelas_list'] ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>TKJ Academic Analytics Dashboard</title>
    <!-- Use Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    "primary": "#6366f1",    // Primary Indigo
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
        body.light-mode .text-slate-100 { color: #1e1b4b !important; }
        body.light-mode .text-slate-400 { color: #4f46e5 !important; }
        body.light-mode .text-slate-300 { color: #334155 !important; }
        body.light-mode .text-text-muted { color: #6366f1 !important; }
        body.light-mode #preloader { background: #f1f5f9; }
        /* Print stylesheet */
        @media print {
            #preloader, aside, header, #powerBiSidebar, #toastContainer,
            #settingsModal, #printModal, nav, .widget-controls { display: none !important; }
            body { background: #fff !important; color: #111 !important; }
            .widget-card {
                border: 1px solid #e2e8f0 !important;
                background: #fff !important;
                box-shadow: none !important;
                backdrop-filter: none !important;
                break-inside: avoid;
            }
            #dashboardCanvas { grid-template-columns: 1fr 1fr !important; }
            h4, .text-slate-100 { color: #1e293b !important; }
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
            color: var(--text-sub, #e2e8f0) !important;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            transition: all 0.3s ease;
        }
        .glass-input:focus {
            background: rgba(15, 23, 42, 0.7) !important;
            border-color: rgba(99, 102, 241, 0.55) !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
            color: var(--text-sub, #e2e8f0) !important;
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
        /* Styled select dropdown using glass-input */
        select.glass-input {
            appearance: none;
            padding-right: 2rem;
            background-image: url('data:image/svg+xml,%3Csvg fill="%23e2e8f0" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"%3E%3Cpath d="M7 10l5 5 5-5z"/%3E%3C/svg%3E');
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 1rem;
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

        /* Custom scrollbars */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }
        
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

        .widget-card {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .widget-card:hover {
            background: rgba(255, 255, 255, 0.07);
            border-color: rgba(255, 255, 255, 0.12);
            transform: translateY(-2px);
            box-shadow: 0 12px 40px 0 rgba(0, 0, 0, 0.3);
        }
        .widget-card.selected {
            border: 1.5px solid rgba(99, 102, 241, 0.7) !important;
            background: rgba(255, 255, 255, 0.09) !important;
            box-shadow: 0 0 25px rgba(99, 102, 241, 0.3) !important;
        }
        /* PowerBI Sidebar Tabs Active State */
        #powerBiSidebar button.active {
            border-bottom-color: #6366f1;
            color: #818cf8;
            background: rgba(99, 102, 241, 0.05);
        }
        body.light-mode #powerBiSidebar button.active {
            border-bottom-color: #6366f1;
            color: #6366f1;
            background: rgba(99, 102, 241, 0.04);
        }
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
    <p class="mt-4 text-xs font-bold text-slate-400">Membuka Lembar Kerja...</p>
</div>

<!-- ====== LAYOUT WRAPPER ====== -->
<div class="flex flex-1 w-full mx-auto relative min-h-screen">

    <!-- ====== SIDEBAR NAVIGATION (Glassmorphic Sidebar) ====== -->
    <aside class="w-24 glass-card flex flex-col items-center py-8 fixed left-0 top-0 h-screen z-[1000] justify-between hidden md:flex border-y-0 border-l-0">
        <div class="flex flex-col items-center gap-12">
            <!-- Unified Top Logo & Dashboard Link -->
            <a class="w-14 h-14 rounded-2xl glass-btn flex items-center justify-center text-primary-light active focus:outline-none relative group" href="index.php" title="Dashboard / Halaman Utama">
                <span class="material-symbols-outlined text-3xl font-bold">school</span>
                <!-- Glowing indicator -->
                <span class="absolute right-0 top-1/2 -translate-y-1/2 w-1.5 h-6 bg-[#6366f1] rounded-l-full shadow-[0_0_12px_rgba(99,102,241,0.8)]"></span>
            </a>
            
            <!-- Menu Navigation -->
            <nav class="flex flex-col gap-6 items-center w-full px-3">
                <a class="w-12 h-12 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-100 hover:bg-white/5 transition-all shrink-0 relative group" href="akademik.php" title="Data Mahasiswa">
                    <span class="material-symbols-outlined text-[24px]">group</span>
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
            <a class="w-12 h-12 rounded-xl flex items-center justify-center text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-all shrink-0" href="logout.php" title="Logout">
                <span class="material-symbols-outlined text-[22px]">logout</span>
            </a>
        </div>
    </aside>

    <!-- ====== MAIN CONTENT AREA ====== -->
    <main class="flex-1 md:pl-[96px] w-full min-h-screen flex flex-col">

        <!-- ====== GLASSMORPHIC HEADER & TOOLBAR ====== -->
        <header class="glass-card w-full flex flex-col z-[100] border-t-0 border-x-0 relative">
            <div class="flex flex-wrap items-center justify-between gap-4 px-8 py-4 select-none">
                <!-- Branding / Title -->
                <div class="flex items-center gap-3">
                    <h1 class="text-base font-extrabold tracking-tight flex items-center gap-2" id="headerTitle">
                        <span class="material-symbols-outlined text-[#6366f1] text-[22px]">analytics</span>
                        TKJ Analytics Workspace
                    </h1>
                </div>

                <!-- Insert Visuals & Canvas Grid Inline Toolbar Actions -->
                <div class="flex flex-wrap items-center gap-6">
                    <!-- Section: Insert Visuals -->
                    <div class="flex items-center gap-2">
                        <span class="text-[9px] font-bold text-text-muted uppercase tracking-wider pr-1">Tambah Visual:</span>
                        <div class="flex gap-1.5 bg-slate-900/30 p-1 rounded-xl border border-white/5">
                            <button onclick="addNewWidget('line')" class="flex items-center gap-1 text-slate-300 hover:text-white rounded-lg px-2.5 py-1.5 text-[10px] font-bold transition-all hover:bg-white/5">
                                <span class="material-symbols-outlined text-[15px] text-indigo-400">show_chart</span> Line
                            </button>
                            <button onclick="addNewWidget('bar')" class="flex items-center gap-1 text-slate-300 hover:text-white rounded-lg px-2.5 py-1.5 text-[10px] font-bold transition-all hover:bg-white/5">
                                <span class="material-symbols-outlined text-[15px] text-pink-400">bar_chart</span> Bar
                            </button>
                            <button onclick="addNewWidget('doughnut')" class="flex items-center gap-1 text-slate-300 hover:text-white rounded-lg px-2.5 py-1.5 text-[10px] font-bold transition-all hover:bg-white/5">
                                <span class="material-symbols-outlined text-[15px] text-blue-400">pie_chart</span> Doughnut
                            </button>
                            <button onclick="addNewWidget('radar')" class="flex items-center gap-1 text-slate-300 hover:text-white rounded-lg px-2.5 py-1.5 text-[10px] font-bold transition-all hover:bg-white/5">
                                <span class="material-symbols-outlined text-[15px] text-emerald-400">radar</span> Radar
                            </button>
                            <button onclick="addNewWidget('card')" class="flex items-center gap-1 text-slate-300 hover:text-white rounded-lg px-2.5 py-1.5 text-[10px] font-bold transition-all hover:bg-white/5">
                                <span class="material-symbols-outlined text-[15px] text-amber-400">tag</span> Card
                            </button>
                        </div>
                    </div>

                    <!-- Section: Canvas Layout -->
                    <div class="hidden md:flex items-center gap-2 border-l border-white/10 pl-6">
                        <span class="text-[9px] font-bold text-text-muted uppercase tracking-wider pr-1">Grid:</span>
                        <div class="flex gap-1 bg-slate-900/30 p-1 rounded-xl border border-white/5">
                            <button onclick="setGridColumns('grid-cols-1 md:grid-cols-2 lg:grid-cols-3')" class="flex items-center gap-1 text-slate-300 hover:text-white rounded-lg px-2.5 py-1.5 text-[10px] font-bold transition-all hover:bg-white/5" title="3 Kolom">
                                <span class="material-symbols-outlined text-[15px]">view_week</span> 3-Col
                            </button>
                            <button onclick="setGridColumns('grid-cols-1 md:grid-cols-2')" class="flex items-center gap-1 text-slate-300 hover:text-white rounded-lg px-2.5 py-1.5 text-[10px] font-bold transition-all hover:bg-white/5" title="2 Kolom">
                                <span class="material-symbols-outlined text-[15px]">dashboard</span> 2-Col
                            </button>
                            <button onclick="togglePanes()" class="flex items-center gap-1 text-slate-300 hover:text-white rounded-lg px-2.5 py-1.5 text-[10px] font-bold transition-all hover:bg-white/5" title="Toggle Sidebar Panes">
                                <span class="material-symbols-outlined text-[15px]">view_sidebar</span> Panes
                            </button>
                            <button onclick="resetDashboard()" class="flex items-center gap-1 text-rose-400 hover:text-rose-300 rounded-lg px-2.5 py-1.5 text-[10px] font-bold transition-all hover:bg-rose-500/10" title="Reset Dashboard">
                                <span class="material-symbols-outlined text-[15px]">restart_alt</span> Reset
                            </button>
                        </div>
                    </div>

                    <!-- Theme Toggle + Avatar -->
                    <div class="relative flex items-center gap-3 border-l border-white/10 pl-6">
                        <!-- Dark/Light Mode Toggle -->
                        <button id="themeToggleBtn" onclick="toggleTheme()" title="Ganti Tema" class="w-9 h-9 rounded-xl flex items-center justify-center transition-all glass-btn">
                            <span id="themeIcon" class="material-symbols-outlined text-[18px] text-amber-400">light_mode</span>
                        </button>
                        <button onclick="toggleProfileDropdown(event)" class="w-9 h-9 rounded-full p-0.5 bg-slate-900/30 hover:scale-105 transition-transform outline-none focus:outline-none border border-white/10">
                            <img alt="Profile" class="w-full h-full rounded-full object-cover" src="https://ui-avatars.com/api/?name=Admin+TKJ&background=1e1b4b&color=6366f1"/>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- PROFILE DROPDOWN BOX -->
        <div id="profileDropdown" class="hidden absolute right-8 top-16 w-64 glass-card rounded-2xl p-5 flex flex-col gap-4 z-[9999]">
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

        <!-- ====== MAIN WORKSPACE LAYOUT ====== -->
        <div class="flex-1 flex flex-col md:flex-row relative">
            <!-- Left/Main Canvas Area -->
            <div class="flex-1 p-6 md:p-8 pb-28 md:pb-8 flex flex-col gap-6">
                <!-- Dynamic Grid for Widgets -->
                <div id="dashboardCanvas" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 w-full items-start">
                    <!-- Widgets are injected dynamically via JavaScript (buildDashboard) -->
                </div>
            </div>

            <!-- Right Collapsible PowerBI-like Sidebar -->
            <aside id="powerBiSidebar" class="w-full md:w-80 border-t md:border-t-0 md:border-l border-white/10 flex flex-col glass-card shrink-0 select-none">
                <!-- Sidebar Tabs Header -->
                <div class="flex border-b border-white/10 text-center font-bold text-[10px] uppercase tracking-wider text-slate-400">
                    <button id="tab-filters" onclick="switchRightPane('filters')" class="flex-1 py-4 border-b-2 border-transparent hover:text-slate-200 transition-all flex items-center justify-center gap-1 active">
                        <span class="material-symbols-outlined text-[14px]">filter_alt</span> Filters
                    </button>
                    <button id="tab-visualizations" onclick="switchRightPane('visualizations')" class="flex-1 py-4 border-b-2 border-transparent hover:text-slate-200 transition-all flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">show_chart</span> Visuals
                    </button>
                    <button id="tab-fields" onclick="switchRightPane('fields')" class="flex-1 py-4 border-b-2 border-transparent hover:text-slate-200 transition-all flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-[14px]">table_chart</span> Fields
                    </button>
                </div>

                <!-- Sidebar Body / Content Panes -->
                <div class="flex-1 p-5 pb-28 md:pb-5 overflow-y-auto flex flex-col gap-6">
                    <!-- PANE 1: GLOBAL FILTERS -->
                    <div id="pane-filters" class="flex flex-col gap-5">
                        <div>
                            <h3 class="text-xs font-extrabold text-slate-100 uppercase tracking-wide">Filter Global</h3>
                            <p class="text-[10px] text-text-muted mt-1 font-bold">Terapkan filter ke seluruh visual di canvas</p>
                        </div>
                        <div class="flex flex-col gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Angkatan</label>
                                <select id="globalAngkatan" onchange="applyGlobalFilters()" class="border-0 rounded-2xl pl-4 pr-10 py-2.5 text-xs font-bold text-slate-200 cursor-pointer glass-input w-full">
                                    <option value="">Semua Angkatan</option>
                                    <?php foreach ($angkatan_list as $ang): ?>
                                        <option value="<?= htmlspecialchars($ang) ?>">Angkatan <?= htmlspecialchars($ang) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Kelas</label>
                                <select id="globalKelas" onchange="applyGlobalFilters()" class="border-0 rounded-2xl pl-4 pr-10 py-2.5 text-xs font-bold text-slate-200 cursor-pointer glass-input w-full">
                                    <option value="">Semua Kelas</option>
                                    <?php foreach ($kelas_list as $kls): ?>
                                        <option value="<?= htmlspecialchars($kls) ?>">Kelas <?= htmlspecialchars($kls) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button onclick="clearGlobalFilters()" class="w-full py-2.5 text-slate-300 text-xs font-bold rounded-xl transition-colors mt-2 glass-btn hover:bg-slate-800/40">
                                Clear Filters
                            </button>
                        </div>
                    </div>

                    <!-- PANE 2: VISUALIZATIONS -->
                    <div id="pane-visualizations" class="hidden flex flex-col gap-5">
                        <div>
                            <h3 class="text-xs font-extrabold text-slate-100 uppercase tracking-wide">Change Selected Visual</h3>
                            <p class="text-[10px] text-text-muted mt-1 font-bold">Pilih salah satu widget di canvas lalu klik tipe chart di bawah ini</p>
                        </div>

                        <!-- Grid of Visual Icons -->
                        <div class="grid grid-cols-3 gap-3">
                            <!-- Line -->
                            <button onclick="changeSelectedWidgetType('line')" title="Line Chart" class="w-full aspect-square flex flex-col items-center justify-center rounded-xl transition-colors glass-btn">
                                <span class="material-symbols-outlined text-slate-300 text-[22px]">show_chart</span>
                                <span class="text-[8px] font-bold text-text-muted mt-1">Line</span>
                            </button>
                            <!-- Bar -->
                            <button onclick="changeSelectedWidgetType('bar')" title="Column Chart" class="w-full aspect-square flex flex-col items-center justify-center rounded-xl transition-colors glass-btn">
                                <span class="material-symbols-outlined text-slate-300 text-[22px]">bar_chart</span>
                                <span class="text-[8px] font-bold text-text-muted mt-1">Bar</span>
                            </button>
                            <!-- Doughnut -->
                            <button onclick="changeSelectedWidgetType('doughnut')" title="Doughnut Chart" class="w-full aspect-square flex flex-col items-center justify-center rounded-xl transition-colors glass-btn">
                                <span class="material-symbols-outlined text-slate-300 text-[22px]">pie_chart</span>
                                <span class="text-[8px] font-bold text-text-muted mt-1">Doughnut</span>
                            </button>
                            <!-- Pie -->
                            <button onclick="changeSelectedWidgetType('pie')" title="Pie Chart" class="w-full aspect-square flex flex-col items-center justify-center rounded-xl transition-colors glass-btn">
                                <span class="material-symbols-outlined text-slate-300 text-[22px]">donut_small</span>
                                <span class="text-[8px] font-bold text-text-muted mt-1">Pie</span>
                            </button>
                            <!-- Radar -->
                            <button onclick="changeSelectedWidgetType('radar')" title="Radar Chart" class="w-full aspect-square flex flex-col items-center justify-center rounded-xl transition-colors glass-btn">
                                <span class="material-symbols-outlined text-slate-300 text-[22px]">radar</span>
                                <span class="text-[8px] font-bold text-text-muted mt-1">Radar</span>
                            </button>
                            <!-- PolarArea -->
                            <button onclick="changeSelectedWidgetType('polarArea')" title="Polar Area" class="w-full aspect-square flex flex-col items-center justify-center rounded-xl transition-colors glass-btn">
                                <span class="material-symbols-outlined text-slate-300 text-[22px]">track_changes</span>
                                <span class="text-[8px] font-bold text-text-muted mt-1">Polar</span>
                            </button>
                            <!-- Card KPI -->
                            <button onclick="changeSelectedWidgetType('card')" title="KPI Card" class="w-full aspect-square flex flex-col items-center justify-center rounded-xl transition-colors glass-btn">
                                <span class="material-symbols-outlined text-slate-300 text-[22px]">tag</span>
                                <span class="text-[8px] font-bold text-text-muted mt-1">Card</span>
                            </button>
                        </div>
                    </div>

                    <!-- PANE 3: FIELDS (DATA DICTIONARY) -->
                    <div id="pane-fields" class="hidden flex flex-col gap-5">
                        <div>
                            <h3 class="text-xs font-extrabold text-slate-100 uppercase tracking-wide">Data Fields (DW Schema)</h3>
                            <p class="text-[10px] text-text-muted mt-1 font-bold">Skema tabel & metadata yang terintegrasi di sistem</p>
                        </div>

                        <!-- Table: dim_mahasiswa_tkj -->
                        <div class="flex flex-col gap-2">
                            <span class="text-xs font-bold text-slate-300 flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px] text-primary-light">table_chart</span> dim_mahasiswa_tkj</span>
                            <div class="flex flex-col gap-1 pl-5 border-l border-white/10 text-[10px] font-bold text-slate-400">
                                <span class="flex items-center gap-1.5 py-0.5"><span class="material-symbols-outlined text-[12px] text-indigo-400">key</span> sk_mahasiswa <span class="text-[8px] font-medium text-slate-500">(PK, int)</span></span>
                                <span class="flex items-center gap-1.5 py-0.5"><span class="material-symbols-outlined text-[12px] text-slate-500">badge</span> nim <span class="text-[8px] font-medium text-slate-500">(varchar)</span></span>
                                <span class="flex items-center gap-1.5 py-0.5"><span class="material-symbols-outlined text-[12px] text-slate-500">person</span> nama_mahasiswa <span class="text-[8px] font-medium text-slate-500">(varchar)</span></span>
                                <span class="flex items-center gap-1.5 py-0.5"><span class="material-symbols-outlined text-[12px] text-slate-500">tag</span> angkatan <span class="text-[8px] font-medium text-slate-500">(int)</span></span>
                                <span class="flex items-center gap-1.5 py-0.5"><span class="material-symbols-outlined text-[12px] text-slate-500">class</span> kelas <span class="text-[8px] font-medium text-slate-500">(varchar)</span></span>
                                <span class="flex items-center gap-1.5 py-0.5"><span class="material-symbols-outlined text-[12px] text-slate-500">settings</span> status_akademik <span class="text-[8px] font-medium text-slate-500">(varchar)</span></span>
                            </div>
                        </div>

                        <!-- Table: dim_waktu -->
                        <div class="flex flex-col gap-2">
                            <span class="text-xs font-bold text-slate-300 flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px] text-primary-light">table_chart</span> dim_waktu</span>
                            <div class="flex flex-col gap-1 pl-5 border-l border-white/10 text-[10px] font-bold text-slate-400">
                                <span class="flex items-center gap-1.5 py-0.5"><span class="material-symbols-outlined text-[12px] text-indigo-400">key</span> sk_waktu <span class="text-[8px] font-medium text-slate-500">(PK, int)</span></span>
                                <span class="flex items-center gap-1.5 py-0.5"><span class="material-symbols-outlined text-[12px] text-slate-500">calendar_today</span> tahun_ajaran <span class="text-[8px] font-medium text-slate-500">(varchar)</span></span>
                                <span class="flex items-center gap-1.5 py-0.5"><span class="material-symbols-outlined text-[12px] text-slate-500">schedule</span> tipe_semester <span class="text-[8px] font-medium text-slate-500">(varchar)</span></span>
                                <span class="flex items-center gap-1.5 py-0.5"><span class="material-symbols-outlined text-[12px] text-slate-500">tag</span> tahun <span class="text-[8px] font-medium text-slate-500">(int)</span></span>
                            </div>
                        </div>

                        <!-- Table: fact_ringkasan_akademik -->
                        <div class="flex flex-col gap-2">
                            <span class="text-xs font-bold text-slate-300 flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px] text-indigo-400">table_chart</span> fact_ringkasan_akademik</span>
                            <div class="flex flex-col gap-1 pl-5 border-l border-white/10 text-[10px] font-bold text-slate-400">
                                <span class="flex items-center gap-1.5 py-0.5"><span class="material-symbols-outlined text-[12px] text-indigo-400">key</span> id_fact_akademik <span class="text-[8px] font-medium text-slate-500">(PK, int)</span></span>
                                <span class="flex items-center gap-1.5 py-0.5"><span class="material-symbols-outlined text-[12px] text-emerald-400">link</span> sk_mahasiswa <span class="text-[8px] font-medium text-slate-500">(FK, int)</span></span>
                                <span class="flex items-center gap-1.5 py-0.5"><span class="material-symbols-outlined text-[12px] text-emerald-400">link</span> sk_waktu <span class="text-[8px] font-medium text-slate-500">(FK, int)</span></span>
                                <span class="flex items-center gap-1.5 py-0.5"><span class="material-symbols-outlined text-[12px] text-pink-400">monitoring</span> ips <span class="text-[8px] font-medium text-slate-500">(decimal)</span></span>
                                <span class="flex items-center gap-1.5 py-0.5"><span class="material-symbols-outlined text-[12px] text-pink-400">analytics</span> ipk <span class="text-[8px] font-medium text-slate-500">(decimal)</span></span>
                            </div>
                        </div>

                        <!-- Table: fact_kelulusan_tkj -->
                        <div class="flex flex-col gap-2">
                            <span class="text-xs font-bold text-slate-300 flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px] text-emerald-400">table_chart</span> fact_kelulusan_tkj</span>
                            <div class="flex flex-col gap-1 pl-5 border-l border-white/10 text-[10px] font-bold text-slate-400">
                                <span class="flex items-center gap-1.5 py-0.5"><span class="material-symbols-outlined text-[12px] text-indigo-400">key</span> id_fact_kelulusan <span class="text-[8px] font-medium text-slate-500">(PK, int)</span></span>
                                <span class="flex items-center gap-1.5 py-0.5"><span class="material-symbols-outlined text-[12px] text-emerald-400">link</span> sk_mahasiswa <span class="text-[8px] font-medium text-slate-500">(FK, int)</span></span>
                                <span class="flex items-center gap-1.5 py-0.5"><span class="material-symbols-outlined text-[12px] text-emerald-400">link</span> sk_waktu <span class="text-[8px] font-medium text-slate-500">(FK, int)</span></span>
                                <span class="flex items-center gap-1.5 py-0.5"><span class="material-symbols-outlined text-[12px] text-pink-400">school</span> ipk_akhir <span class="text-[8px] font-medium text-slate-500">(decimal)</span></span>
                                <span class="flex items-center gap-1.5 py-0.5"><span class="material-symbols-outlined text-[12px] text-pink-400">hourglass_empty</span> lama_studi_semester <span class="text-[8px] font-medium text-slate-500">(int)</span></span>
                                <span class="flex items-center gap-1.5 py-0.5"><span class="material-symbols-outlined text-[12px] text-slate-500">workspace_premium</span> predikat <span class="text-[8px] font-medium text-slate-500">(varchar)</span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Minimize Pane Button -->
                <button onclick="togglePanes()" class="p-4 border-t border-white/5 text-center text-xs font-bold text-slate-400 hover:text-white flex items-center justify-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">keyboard_double_arrow_right</span> Hide Panes
                </button>
            </aside>
        </div>

        <!-- ====== MOBILE BOTTOM NAV ====== -->
        <nav class="md:hidden fixed bottom-6 left-6 right-6 h-16 bg-[#0f172a]/85 backdrop-blur-lg border border-white/15 rounded-2xl flex items-center justify-around px-2 z-[9999] shadow-2xl">
            <a href="index.php" class="flex flex-col items-center justify-center text-primary-light px-2 py-1 rounded-xl bg-white/5">
                <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1">school</span>
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
            <a href="api_docs.php" class="flex flex-col items-center justify-center text-slate-400 hover:text-slate-200">
                <span class="material-symbols-outlined text-[20px]">api</span>
                <span class="text-[8px] font-bold mt-0.5">API</span>
            </a>
        </nav>

        <!-- ====== TOAST NOTIFICATION CONTAINER ====== -->
        <div id="toastContainer" class="fixed top-6 right-6 z-[99999] flex flex-col gap-3 pointer-events-none"></div>

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

                    <!-- Section 3: Customization -->
                    <div class="flex flex-col gap-3">
                        <span class="text-[10px] text-text-muted font-bold uppercase tracking-wider">Preferensi Tampilan</span>
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-semibold">Tema Gelap / Terang</span>
                            <button onclick="toggleTheme()" id="darkModeToggleSettings" class="w-10 h-6 rounded-full bg-primary p-0.5 flex items-center transition-all focus:outline-none">
                                <div id="darkModeThumb" class="w-5 h-5 rounded-full bg-white shadow-md transform translate-x-4 transition-transform"></div>
                            </button>
                        </div>
                    </div>
                </div>
                
                <button onclick="closeSettingsModal()" class="w-full text-slate-200 rounded-2xl py-3.5 mt-8 font-bold text-xs glass-btn uppercase tracking-wider">
                    Simpan & Selesai
                </button>
            </div>
        </div>

        <!-- ====== PRINT MODAL ====== -->
        <div id="printModal" class="hidden fixed inset-0 z-[10000] items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-950/40 backdrop-blur-md" onclick="closePrintModal()"></div>
            <div class="glass-card rounded-[2rem] p-8 max-w-md w-full relative z-10 flex flex-col gap-5">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-extrabold text-slate-100">Cetak Laporan</h3>
                        <p class="text-xs text-text-muted font-bold mt-1">Pilih format output laporan visualisasi</p>
                    </div>
                    <button onclick="closePrintModal()" class="w-8 h-8 rounded-full flex items-center justify-center glass-btn">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>
                <div class="flex flex-col gap-3">
                    <button onclick="doPrint()" class="flex items-center gap-3 p-4 rounded-2xl glass-btn hover:bg-indigo-500/15 transition-all text-left w-full">
                        <span class="material-symbols-outlined text-[22px] text-indigo-400">print</span>
                        <div>
                            <div class="text-xs font-extrabold text-slate-100">Cetak / Print</div>
                            <div class="text-[10px] text-text-muted mt-0.5">Kirim ke printer atau simpan sebagai PDF</div>
                        </div>
                    </button>
                    <button onclick="doExportCanvas()" class="flex items-center gap-3 p-4 rounded-2xl glass-btn hover:bg-emerald-500/15 transition-all text-left w-full">
                        <span class="material-symbols-outlined text-[22px] text-emerald-400">image</span>
                        <div>
                            <div class="text-xs font-extrabold text-slate-100">Simpan sebagai Gambar</div>
                            <div class="text-[10px] text-text-muted mt-0.5">Screenshot dashboard canvas (.png)</div>
                        </div>
                    </button>
                </div>
                <p class="text-[10px] text-text-muted text-center font-bold">Semua widget yang tampil di canvas akan tercakup</p>
            </div>
        </div>

    </main>
</div>

<!-- ====== JS LOGIC AND INTEGRATION ====== -->
<script>
// Global States
let selectedWidgetId = null;
let activeCharts = {};
let gridColsClass = 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3';

// Default Visuals Setup
const defaultWidgets = [
    { id: 'widget-1', type: 'card', title: 'Total Mahasiswa', datasource: 'summary', field: 'total_mahasiswa', val: <?= $total_mhs ?>, icon: 'group', color: 'text-primary-light' },
    { id: 'widget-2', type: 'card', title: 'Rata-rata IPK', datasource: 'summary', field: 'rata_rata_ipk', val: '<?= $avg_ipk ?>', icon: 'monitoring', color: 'text-emerald-400' },
    { id: 'widget-3', type: 'card', title: 'Total Cum Laude', datasource: 'summary', field: 'total_cumlaude', val: <?= $cumlaude ?>, icon: 'workspace_premium', color: 'text-pink-400' },
    { id: 'widget-4', type: 'line', title: 'Tren IPK Rata-Rata', datasource: 'chart_ipk', params: { angkatan: '', kelas: '' } },
    { id: 'widget-5', type: 'doughnut', title: 'Distribusi Predikat Kelulusan', datasource: 'chart_predikat' }
];

let currentWidgets = JSON.parse(localStorage.getItem('bi_widgets')) || [...defaultWidgets];

// Double check to strip news/reference widgets from old localStorage states
currentWidgets = currentWidgets.filter(w => w.type !== 'news' && w.type !== 'reference');

// ===== THEME TOGGLE =====
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

// ===== PRINT / EXPORT =====
function openPrintModal() {
    document.getElementById('printModal').classList.replace('hidden', 'flex');
}
function closePrintModal() {
    document.getElementById('printModal').classList.replace('flex', 'hidden');
}
function doPrint() {
    closePrintModal();
    setTimeout(() => window.print(), 150);
}
function doExportCanvas() {
    closePrintModal();
    // Capture all canvases and create a composite image
    const canvas = document.getElementById('dashboardCanvas');
    if (!canvas) return showToast('Canvas tidak ditemukan', 'error');
    import('https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.esm.min.js')
        .then(mod => mod.default(canvas, { backgroundColor: '#0f172a', scale: 1.5, useCORS: true }))
        .then(c => {
            const a = document.createElement('a');
            a.download = 'dashboard_tkj_' + new Date().toISOString().slice(0,10) + '.png';
            a.href = c.toDataURL('image/png');
            a.click();
            showToast('Dashboard berhasil diekspor!', 'success');
        })
        .catch(() => showToast('Export gagal. Coba Cetak PDF saja.', 'error'));
}

// Preloader Close
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

// Dropdown & Settings Functions
function toggleProfileDropdown(e) {
    e.stopPropagation();
    document.getElementById('profileDropdown').classList.toggle('hidden');
}
document.addEventListener('click', () => {
    document.getElementById('profileDropdown').classList.add('hidden');
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
    }, 200);
}

// Custom Toast (Glassmorphism)
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `transform translate-x-full transition-all duration-300 ease-out glass-card pointer-events-auto flex items-center gap-3 px-5 py-4 rounded-2xl max-w-sm border-l-4`;
    
    let icon = 'info', borderClass = 'border-l-indigo-500', iconColor = 'text-indigo-400';
    if (type === 'success') { icon = 'check_circle'; borderClass = 'border-l-emerald-500'; iconColor = 'text-emerald-400'; }
    else if (type === 'error') { icon = 'error'; borderClass = 'border-l-rose-500'; iconColor = 'text-rose-400'; }
    else if (type === 'info') { icon = 'info'; borderClass = 'border-l-amber-500'; iconColor = 'text-amber-400'; }
    
    toast.className += ` ${borderClass}`;
    toast.innerHTML = `
        <span class="material-symbols-outlined ${iconColor} text-[20px] shrink-0">${icon}</span>
        <div class="text-xs font-bold text-slate-100 pr-4">${message}</div>
        <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-200 transition-colors ml-auto shrink-0">
            <span class="material-symbols-outlined text-[16px]">close</span>
        </button>
    `;
    container.appendChild(toast);
    setTimeout(() => toast.classList.remove('translate-x-full'), 10);
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-x-full');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Right Sidebar tabs
function switchRightPane(tab) {
    ['filters', 'visualizations', 'fields'].forEach(t => {
        document.getElementById(`pane-${t}`).classList.add('hidden');
        document.getElementById(`tab-${t}`).classList.remove('active');
    });
    document.getElementById(`pane-${tab}`).classList.remove('hidden');
    document.getElementById(`tab-${tab}`).classList.add('active');
}

function togglePanes() {
    const sidebar = document.getElementById('powerBiSidebar');
    sidebar.classList.toggle('hidden');
}

function setGridColumns(colsClass) {
    gridColsClass = colsClass;
    const canvas = document.getElementById('dashboardCanvas');
    canvas.className = `grid ${colsClass} gap-6 w-full items-start`;
    showToast('Tata letak kolom grid diperbarui!', 'success');
}

// Save & Load Dashboard State
function saveDashboardState() {
    localStorage.setItem('bi_widgets', JSON.stringify(currentWidgets));
}

function resetDashboard() {
    currentWidgets = [...defaultWidgets];
    saveDashboardState();
    buildDashboard();
    showToast('Dashboard berhasil direset ke susunan awal.', 'success');
}

// Widget Engine
function selectWidget(id) {
    selectedWidgetId = id;
    document.querySelectorAll('.widget-card').forEach(w => {
        w.classList.remove('selected');
        if (w.dataset.id === id) {
            w.classList.add('selected');
        }
    });
    switchRightPane('visualizations');
}

function changeSelectedWidgetType(newType) {
    if (!selectedWidgetId) {
        showToast('Pilih widget di canvas terlebih dahulu!', 'info');
        return;
    }
    const widget = currentWidgets.find(w => w.id === selectedWidgetId);
    if (!widget) return;
    
    if (widget.type === 'card' && newType !== 'card') {
        showToast('Widget KPI Agregat tidak dapat diubah menjadi tipe Chart!', 'error');
        return;
    }
    if (widget.type !== 'card' && newType === 'card') {
        showToast('Visualisasi Chart tidak dapat diubah menjadi tipe KPI Card!', 'error');
        return;
    }
    
    widget.type = newType;
    saveDashboardState();
    buildDashboard();
    showToast(`Widget diubah menjadi tipe ${newType.toUpperCase()}!`, 'success');
}

function deleteWidget(id) {
    currentWidgets = currentWidgets.filter(w => w.id !== id);
    if (selectedWidgetId === id) selectedWidgetId = null;
    saveDashboardState();
    buildDashboard();
    showToast('Widget dihapus dari canvas.', 'warning');
}

function addNewWidget(type) {
    const newId = 'widget-' + Date.now();
    let newWidget = {
        id: newId,
        type: type,
        title: `Visual Kustom ${type.toUpperCase()}`,
        datasource: type === 'card' ? 'summary' : 'chart_ipk'
    };
    
    if (type === 'card') {
        newWidget.field = 'total_mahasiswa';
        newWidget.val = '0';
        newWidget.icon = 'tag';
        newWidget.color = 'text-slate-300';
    } else {
        newWidget.params = { angkatan: '', kelas: '' };
    }
    
    currentWidgets.push(newWidget);
    saveDashboardState();
    buildDashboard();
    showToast('Widget baru ditambahkan ke canvas!', 'success');
    selectWidget(newId);
}

// Apply Global Filters to All Charts and Cards
function applyGlobalFilters() {
    const angkatan = document.getElementById('globalAngkatan').value;
    const kelas = document.getElementById('globalKelas').value;
    
    currentWidgets.forEach(widget => {
        if (widget.params) {
            widget.params.angkatan = angkatan;
            widget.params.kelas = kelas;
            
            // Update filter display text in the DOM
            const displayEl = document.querySelector(`.widget-card[data-id="${widget.id}"] .filter-display`);
            if (displayEl) {
                displayEl.textContent = `${angkatan || 'Semua'} / ${kelas || 'Semua'}`;
            }
            
            if (activeCharts[widget.id]) {
                loadChartDataAndRender(widget);
            }
        }
        
        if (widget.type === 'card') {
            const cardEl = document.querySelector(`.widget-card[data-id="${widget.id}"]`);
            if (cardEl) {
                loadCardData(widget, cardEl);
            }
        }
    });
    
    showToast('Filter global berhasil diterapkan!', 'success');
}

function clearGlobalFilters() {
    document.getElementById('globalAngkatan').value = '';
    document.getElementById('globalKelas').value = '';
    applyGlobalFilters();
    showToast('Filter dikosongkan.', 'info');
}

// Rebuild and Render dashboard widgets
function buildDashboard() {
    currentWidgets.forEach(widget => {
        const canvasId = `chart-canvas-${widget.id}`;
        try {
            const existingChart = Chart.getChart(canvasId);
            if (existingChart) {
                existingChart.destroy();
            }
        } catch(e){}
    });
    
    const canvas = document.getElementById('dashboardCanvas');
    canvas.innerHTML = '';
    activeCharts = {};

    currentWidgets.forEach(widget => {
        const card = document.createElement('div');
        card.dataset.id = widget.id;
        
        // Calculate responsive grid column span
        let colSpanClass = '';
        if (widget.colSpan === 2) {
            colSpanClass = 'md:col-span-2';
        } else if (widget.colSpan === 3) {
            colSpanClass = 'md:col-span-2 lg:col-span-3';
        }
        
        card.className = `widget-card rounded-[2rem] p-6 relative flex flex-col justify-between cursor-pointer group select-none ${widget.id === selectedWidgetId ? 'selected' : ''} ${colSpanClass}`;
        
        card.addEventListener('click', (e) => {
            if (e.target.closest('.widget-controls') || e.target.closest('select') || e.target.closest('input')) return;
            selectWidget(widget.id);
        });

        // Header controls HTML - Conditionally hide dropdown on card type
        const controlsHTML = widget.type === 'card' ? `
            <div class="flex items-center gap-1.5 widget-controls opacity-40 group-hover:opacity-100 transition-opacity">
                <button onclick="deleteWidget('${widget.id}')" class="w-6 h-6 rounded-lg bg-slate-900/30 hover:bg-red-500/20 text-slate-400 hover:text-red-400 flex items-center justify-center transition-colors" title="Delete visual">
                    <span class="material-symbols-outlined text-[13px]">delete</span>
                </button>
            </div>
        ` : `
            <div class="flex items-center gap-1.5 widget-controls opacity-40 group-hover:opacity-100 transition-opacity">
                <select onchange="updateWidgetChartType('${widget.id}', this.value)" class="text-[9px] font-bold border-0 bg-slate-950/40 text-slate-300 rounded-lg px-2 py-1 focus:ring-0 cursor-pointer hover:bg-slate-900/60 transition-colors">
                    <option value="line" ${widget.type === 'line' ? 'selected' : ''}>Line</option>
                    <option value="bar" ${widget.type === 'bar' ? 'selected' : ''}>Bar</option>
                    <option value="doughnut" ${widget.type === 'doughnut' ? 'selected' : ''}>Doughnut</option>
                    <option value="pie" ${widget.type === 'pie' ? 'selected' : ''}>Pie</option>
                    <option value="radar" ${widget.type === 'radar' ? 'selected' : ''}>Radar</option>
                    <option value="polarArea" ${widget.type === 'polarArea' ? 'selected' : ''}>Polar</option>
                </select>
                <button onclick="deleteWidget('${widget.id}')" class="w-6 h-6 rounded-lg bg-slate-900/30 hover:bg-red-500/20 text-slate-400 hover:text-red-400 flex items-center justify-center transition-colors" title="Delete visual">
                    <span class="material-symbols-outlined text-[13px]">delete</span>
                </button>
            </div>
        `;

        if (widget.type === 'card') {
            card.innerHTML = `
                <div class="flex justify-between items-start gap-4 mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-slate-900/30 ${widget.color || 'text-slate-300'}">
                            <span class="material-symbols-outlined text-[18px]">${widget.icon || 'tag'}</span>
                        </div>
                        <h4 class="text-xs font-bold text-slate-100">${widget.title}</h4>
                    </div>
                    ${controlsHTML}
                </div>
                <div class="mt-2 flex flex-col">
                    <span class="text-2xl font-extrabold text-slate-100">${widget.val}</span>
                    <span class="text-[9px] text-text-muted font-bold mt-0.5 uppercase tracking-wider">KPI Agregat</span>
                </div>
            `;
            canvas.appendChild(card);
            loadCardData(widget, card);
        }
        else {
            card.innerHTML = `
                <div class="flex justify-between items-start gap-4 mb-4">
                    <div>
                        <h4 class="text-xs font-bold text-slate-100">${widget.title}</h4>
                        ${widget.params ? `
                            <p class="text-[9px] text-text-muted font-bold mt-0.5 uppercase">
                                Filter: <span class="filter-display">${widget.params.angkatan || 'Semua'} / ${widget.params.kelas || 'Semua'}</span>
                            </p>
                        ` : ''}
                    </div>
                    ${controlsHTML}
                </div>
                <div class="relative w-full overflow-hidden" style="height: ${widget.height || 176}px;">
                    <canvas id="chart-canvas-${widget.id}"></canvas>
                </div>
                <!-- Drag resize handle -->
                <div class="absolute bottom-2 right-2 w-4 h-4 cursor-se-resize flex items-center justify-center text-slate-500 hover:text-slate-300 select-none resize-handle opacity-0 group-hover:opacity-60 hover:opacity-100 transition-opacity" onmousedown="initResize(event, '${widget.id}')" ontouchstart="initResize(event, '${widget.id}')">
                    <span class="material-symbols-outlined text-[14px]">south_east</span>
                </div>
            `;
            canvas.appendChild(card);
            loadChartDataAndRender(widget);
        }
    });
}

function updateWidgetChartType(id, type) {
    const widget = currentWidgets.find(w => w.id === id);
    if (!widget) return;
    widget.type = type;
    saveDashboardState();
    buildDashboard();
    showToast(`Visualisasi diperbarui menjadi ${type.toUpperCase()}`, 'success');
}

// Load KPIs dynamically with active filters
function loadCardData(widget, cardEl) {
    const angkatan = document.getElementById('globalAngkatan') ? document.getElementById('globalAngkatan').value : '';
    const kelas = document.getElementById('globalKelas') ? document.getElementById('globalKelas').value : '';
    const url = `api_dw_tkj.php?type=summary&angkatan=${angkatan}&kelas=${encodeURIComponent(kelas)}`;
    
    fetch(url, { headers: { 'key': '<?= API_KEY ?>' } })
    .then(res => res.json())
    .then(data => {
        const results = data.results || {};
        let val = widget.val;
        if (widget.field === 'total_mahasiswa') {
            val = results.total_mahasiswa || 0;
            widget.color = 'text-primary-light'; widget.icon = 'group';
        } else if (widget.field === 'rata_rata_ipk') {
            val = parseFloat(results.rata_rata_ipk || 0).toFixed(2);
            widget.color = 'text-emerald-400'; widget.icon = 'monitoring';
        } else if (widget.field === 'total_cumlaude') {
            val = results.total_cumlaude || 0;
            widget.color = 'text-pink-400'; widget.icon = 'workspace_premium';
        }
        
        widget.val = val;
        cardEl.querySelector('.text-2xl').textContent = val;
    })
    .catch(err => console.error("Error loading KPI data:", err));
}

// API Loader for charts
function loadChartDataAndRender(widget) {
    const canvasId = `chart-canvas-${widget.id}`;
    const canvasEl = document.getElementById(canvasId);
    if (!canvasEl) return;
    
    try {
        const existingChart = Chart.getChart(canvasId) || Chart.getChart(canvasEl);
        if (existingChart) {
            existingChart.destroy();
        }
    } catch(e){}
    
    if (activeCharts[widget.id]) {
        try {
            activeCharts[widget.id].destroy();
        } catch(e){}
        delete activeCharts[widget.id];
    }
    
    const ctx = canvasEl.getContext('2d');
    
    let url = `api_dw_tkj.php?type=${widget.datasource}`;
    if (widget.params) {
        url += `&angkatan=${widget.params.angkatan}&kelas=${encodeURIComponent(widget.params.kelas)}`;
    }
    
    fetch(url, { headers: { 'key': '<?= API_KEY ?>' } })
    .then(res => res.json())
    .then(data => {
        if (!canvasEl.isConnected) return;

        const results = data.results || [];
        
        let labels = [];
        let datasetData = [];
        let datasetLabel = '';
        
        if (widget.datasource === 'chart_ipk') {
            labels = results.map(r => r.label || 'Sem');
            datasetData = results.map(r => parseFloat(r.ipk || 0));
            datasetLabel = 'IPK Rata-Rata';
        } else if (widget.datasource === 'chart_predikat') {
            labels = results.map(r => r.predikat || 'Predikat');
            datasetData = results.map(r => parseInt(r.jumlah || 0));
            datasetLabel = 'Jumlah Kelulusan';
        }
        
        const palette = ['#6366f1', '#ec4899', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6'];
        
        const chartConfig = {
            type: widget.type,
            data: {
                labels: labels,
                datasets: [{
                    label: datasetLabel,
                    data: datasetData,
                    backgroundColor: widget.type === 'line' || widget.type === 'radar' 
                        ? 'rgba(99, 102, 241, 0.15)' 
                        : palette.slice(0, labels.length),
                    borderColor: '#6366f1',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: widget.type === 'line'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: widget.type === 'doughnut' || widget.type === 'pie' || widget.type === 'polarArea',
                        position: 'bottom',
                        labels: { 
                            boxWidth: 10, 
                            color: '#94a3b8', 
                            font: { size: 9, weight: 'bold' } 
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { size: 10 },
                        bodyFont: { size: 10 }
                    }
                },
                scales: (widget.type === 'line' || widget.type === 'bar') ? {
                    y: {
                        ticks: { 
                            color: '#94a3b8', 
                            font: { size: 9, weight: 'bold' } 
                        },
                        grid: { color: 'rgba(255, 255, 255, 0.08)' }
                    },
                    x: {
                        ticks: { 
                            color: '#94a3b8', 
                            font: { size: 9, weight: 'bold' } 
                        },
                        grid: { display: false }
                    }
                } : (widget.type === 'radar') ? {
                    r: {
                        angleLines: { color: 'rgba(255, 255, 255, 0.08)' },
                        grid: { color: 'rgba(255, 255, 255, 0.08)' },
                        pointLabels: { color: '#94a3b8', font: { size: 9, weight: 'bold' } },
                        ticks: { display: false }
                    }
                } : {}
            }
        };
        
        try {
            const existingChart = Chart.getChart(canvasId) || Chart.getChart(canvasEl);
            if (existingChart) {
                existingChart.destroy();
            }
        } catch(e){}

        activeCharts[widget.id] = new Chart(ctx, chartConfig);
    })
    .catch(err => console.error("Error loading visual data:", err));
}

// Drag to Resize Charts Engine (Multi-directional: Height & Column-span)
function initResize(e, widgetId) {
    e.preventDefault();
    e.stopPropagation();
    
    const widget = currentWidgets.find(w => w.id === widgetId);
    if (!widget) return;
    
    const card = document.querySelector(`.widget-card[data-id="${widgetId}"]`);
    if (!card) return;
    
    const canvasId = `chart-canvas-${widgetId}`;
    const canvasEl = document.getElementById(canvasId);
    if (!canvasEl) return;
    const canvasWrapper = canvasEl.parentElement;
    
    const startX = e.clientX || (e.touches && e.touches[0].clientX);
    const startY = e.clientY || (e.touches && e.touches[0].clientY);
    const startHeight = parseInt(window.getComputedStyle(canvasWrapper).height) || 176;
    const startColSpan = widget.colSpan || 1;
    
    let currentColSpan = startColSpan;
    
    function onMouseMove(moveEvent) {
        if (moveEvent.cancelable) {
            moveEvent.preventDefault();
        }
        
        const currentX = moveEvent.clientX || (moveEvent.touches && moveEvent.touches[0].clientY);
        const currentY = moveEvent.clientY || (moveEvent.touches && moveEvent.touches[0].clientY);
        if (currentX === undefined || currentY === undefined) return;
        
        // 1. Vertical Resize (Height in pixels)
        const deltaY = currentY - startY;
        let newHeight = startHeight + deltaY;
        if (newHeight < 120) newHeight = 120;
        if (newHeight > 600) newHeight = 600;
        canvasWrapper.style.height = `${newHeight}px`;
        
        // 2. Horizontal Resize (Column Span: 1, 2, or 3)
        const deltaX = currentX - startX;
        let newColSpan = startColSpan;
        
        if (deltaX > 180) {
            newColSpan = Math.min(3, startColSpan + 1);
            if (deltaX > 380) {
                newColSpan = Math.min(3, startColSpan + 2);
            }
        } else if (deltaX < -180) {
            newColSpan = Math.max(1, startColSpan - 1);
            if (deltaX < -380) {
                newColSpan = Math.max(1, startColSpan - 2);
            }
        }
        
        if (newColSpan !== currentColSpan) {
            currentColSpan = newColSpan;
            
            // Remove current col-span classes
            card.classList.remove('md:col-span-2', 'lg:col-span-3');
            
            // Add new col-span classes
            if (currentColSpan === 2) {
                card.classList.add('md:col-span-2');
            } else if (currentColSpan === 3) {
                card.classList.add('md:col-span-2', 'lg:col-span-3');
            }
        }
        
        // Trigger Chart.js resize
        if (activeCharts[widgetId]) {
            activeCharts[widgetId].resize();
        }
    }
    
    function onMouseUp() {
        window.removeEventListener('mousemove', onMouseMove);
        window.removeEventListener('mouseup', onMouseUp);
        window.removeEventListener('touchmove', onMouseMove);
        window.removeEventListener('touchend', onMouseUp);
        
        const finalHeight = parseInt(canvasWrapper.style.height);
        widget.height = finalHeight;
        widget.colSpan = currentColSpan;
        saveDashboardState();
        
        // Rebuild dashboard to ensure grid flow snaps perfectly
        buildDashboard();
    }
    
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseup', onMouseUp);
    window.addEventListener('touchmove', onMouseMove, { passive: false });
    window.addEventListener('touchend', onMouseUp);
}

// Initial Build Call
buildDashboard();
</script>
</body>
</html>
