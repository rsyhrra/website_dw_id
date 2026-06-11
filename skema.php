<?php
// File: skema.php
session_start();

// Redireksi jika belum login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Skema Data Warehouse – TKJ PNUP</title>
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
        /* Light mode overrides */
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
        body.light-mode nav.md\:hidden { background: rgba(241,245,249,0.9) !important; border-color: rgba(99,102,241,0.15) !important; }

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
        /* Custom scrollbars */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
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
</div>

<!-- Preloader -->
<div id="preloader">
    <div class="spinner"></div>
    <p class="mt-4 text-xs font-bold text-slate-400">Memuat Skema Data Warehouse...</p>
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
                <a class="w-12 h-12 rounded-xl flex items-center justify-center text-primary-light glass-btn-active shrink-0 relative group" href="skema.php" title="Skema Data Warehouse">
                    <span class="material-symbols-outlined text-[24px]">schema</span>
                    <span class="absolute right-0 top-1/2 -translate-y-1/2 w-1.5 h-6 bg-[#6366f1] rounded-l-full shadow-[0_0_12px_rgba(99,102,241,0.8)]"></span>
                    <span class="absolute left-full ml-4 px-2 py-1 bg-slate-800 text-[10px] text-white rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-50">Skema DW</span>
                </a>
                <a class="w-12 h-12 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-100 hover:bg-white/5 transition-all shrink-0 relative group" href="api_docs.php" title="Dokumentasi & Tester API">
                    <span class="material-symbols-outlined text-[24px]">api</span>
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
    <main class="flex-1 md:pl-[96px] p-6 md:p-8 pb-28 md:pb-8 w-full min-h-screen flex flex-col gap-6">

        <!-- Header / Toolbar -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 w-full glass-card rounded-2xl px-6 py-5">
            <div>
                <h1 class="text-base font-extrabold text-slate-100 tracking-tight flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#6366f1]">schema</span> Star Schema & Arsitektur DW
                </h1>
                <p class="text-xs font-bold text-text-muted mt-0.5">Dokumentasi dan visualisasi interaktif struktur database Data Warehouse.</p>
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
                            <a href="api_docs.php" class="flex items-center gap-2.5 py-2 px-3 rounded-xl hover:bg-white/5 text-xs font-bold text-slate-300 hover:text-white transition-all">
                                <span class="material-symbols-outlined text-[18px]">api</span> Dokumentasi API
                            </a>
                        </div>
                        <a href="logout.php" class="flex items-center gap-2.5 py-2.5 px-3 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-xs font-bold text-red-400 transition-all justify-center">
                            <span class="material-symbols-outlined text-[16px]">logout</span> Keluar
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Star Schema Interactive Graph (SVG) -->
        <div class="rounded-[2rem] p-6 glass-card flex flex-col gap-4">
            <h3 class="text-xs font-extrabold text-slate-100 uppercase tracking-wider">Visualisasi Star Schema DW</h3>
            <div class="overflow-x-auto w-full flex justify-center py-6 bg-slate-950/20 rounded-2xl">
                <!-- SVG diagram illustrating Star Schema -->
                <svg width="800" height="420" viewBox="0 0 800 420" fill="none" xmlns="http://www.w3.org/2000/svg" class="max-w-full">
                    <defs>
                        <linearGradient id="headerGrad" x1="0" y1="0" x2="1" y2="0">
                            <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.2"/>
                            <stop offset="100%" stop-color="#6366f1" stop-opacity="0.2"/>
                        </linearGradient>
                        <linearGradient id="headerGradFact" x1="0" y1="0" x2="1" y2="0">
                            <stop offset="0%" stop-color="#ec4899" stop-opacity="0.2"/>
                            <stop offset="100%" stop-color="#f43f5e" stop-opacity="0.2"/>
                        </linearGradient>
                    </defs>

                    <!-- Connection Lines with End Markers or just clean line connections -->
                    <!-- dim_mahasiswa_tkj.sk_mahasiswa -> fact_kelulusan_tkj.sk_mahasiswa (Green) -->
                    <path d="M 260 95 L 540 115" stroke="#4ade80" stroke-width="2" stroke-linecap="round" />
                    <!-- dim_mahasiswa_tkj.sk_mahasiswa -> fact_ringkasan_akademik.sk_mahasiswa (Green) -->
                    <path d="M 260 95 L 540 315" stroke="#4ade80" stroke-width="2" stroke-linecap="round" />
                    
                    <!-- dim_waktu.sk_waktu -> fact_kelulusan_tkj.sk_waktu (Blue) -->
                    <path d="M 260 305 L 540 135" stroke="#6366f1" stroke-width="2" stroke-linecap="round" />
                    <!-- dim_waktu.sk_waktu -> fact_ringkasan_akademik.sk_waktu (Blue) -->
                    <path d="M 260 305 L 540 335" stroke="#6366f1" stroke-width="2" stroke-linecap="round" />

                    <!-- TABLE 1: dim_mahasiswa_tkj (Top Left) -->
                    <g transform="translate(40, 40)">
                        <!-- Main Card -->
                        <rect width="220" height="170" rx="10" fill="var(--card-bg)" stroke="#6366f1" stroke-width="1.5" />
                        <!-- Header Bar -->
                        <path d="M 0 10 A 10 10 0 0 1 10 0 L 210 0 A 10 10 0 0 1 220 10 L 220 35 L 0 35 Z" fill="url(#headerGrad)" stroke="#6366f1" stroke-width="1" />
                        <!-- Header Text -->
                        <text x="15" y="22" fill="#818cf8" font-size="12" font-weight="800" font-family="sans-serif">dim_mahasiswa_tkj</text>
                        
                        <!-- Columns -->
                        <!-- sk_mahasiswa -->
                        <text x="15" y="55" fill="#f8fafc" font-size="10" font-family="monospace">🔑 sk_mahasiswa : int(11)</text>
                        <!-- nim -->
                        <text x="15" y="75" fill="#94a3b8" font-size="10" font-family="monospace">👤 nim : varchar(15)</text>
                        <!-- nama_mahasiswa -->
                        <text x="15" y="95" fill="#94a3b8" font-size="10" font-family="monospace">👤 nama_mahasiswa : varchar(100)</text>
                        <!-- angkatan -->
                        <text x="15" y="115" fill="#94a3b8" font-size="10" font-family="monospace"># angkatan : int(11)</text>
                        <!-- kelas -->
                        <text x="15" y="135" fill="#94a3b8" font-size="10" font-family="monospace">📄 kelas : varchar(50)</text>
                        <!-- status_akademik -->
                        <text x="15" y="155" fill="#94a3b8" font-size="10" font-family="monospace">⚙️ status_akademik : varchar(20)</text>
                    </g>

                    <!-- TABLE 2: dim_waktu (Bottom Left) -->
                    <g transform="translate(40, 250)">
                        <!-- Main Card -->
                        <rect width="220" height="130" rx="10" fill="var(--card-bg)" stroke="#6366f1" stroke-width="1.5" />
                        <!-- Header Bar -->
                        <path d="M 0 10 A 10 10 0 0 1 10 0 L 210 0 A 10 10 0 0 1 220 10 L 220 35 L 0 35 Z" fill="url(#headerGrad)" stroke="#6366f1" stroke-width="1" />
                        <!-- Header Text -->
                        <text x="15" y="22" fill="#818cf8" font-size="12" font-weight="800" font-family="sans-serif">dim_waktu</text>
                        
                        <!-- Columns -->
                        <!-- sk_waktu -->
                        <text x="15" y="55" fill="#f8fafc" font-size="10" font-family="monospace">🔑 sk_waktu : int(11)</text>
                        <!-- tahun_ajaran -->
                        <text x="15" y="75" fill="#94a3b8" font-size="10" font-family="monospace">📅 tahun_ajaran : varchar(20)</text>
                        <!-- tipe_semester -->
                        <text x="15" y="95" fill="#94a3b8" font-size="10" font-family="monospace">📅 tipe_semester : varchar(20)</text>
                        <!-- tahun -->
                        <text x="15" y="115" fill="#94a3b8" font-size="10" font-family="monospace"># tahun : int(11)</text>
                    </g>

                    <!-- TABLE 3: fact_kelulusan_tkj (Top Right) -->
                    <g transform="translate(540, 40)">
                        <!-- Main Card -->
                        <rect width="220" height="170" rx="10" fill="var(--card-bg)" stroke="#ec4899" stroke-width="1.5" />
                        <!-- Header Bar -->
                        <path d="M 0 10 A 10 10 0 0 1 10 0 L 210 0 A 10 10 0 0 1 220 10 L 220 35 L 0 35 Z" fill="url(#headerGradFact)" stroke="#ec4899" stroke-width="1" />
                        <!-- Header Text -->
                        <text x="15" y="22" fill="#f472b6" font-size="12" font-weight="800" font-family="sans-serif">fact_kelulusan_tkj</text>
                        
                        <!-- Columns -->
                        <!-- id_fact_kelulusan -->
                        <text x="15" y="55" fill="#f8fafc" font-size="10" font-family="monospace">🔑 id_fact_kelulusan : int(11)</text>
                        <!-- sk_mahasiswa -->
                        <text x="15" y="75" fill="#f1f5f9" font-size="10" font-family="monospace">🔗 sk_mahasiswa : int(11)</text>
                        <!-- sk_waktu -->
                        <text x="15" y="95" fill="#f1f5f9" font-size="10" font-family="monospace">🔗 sk_waktu : int(11)</text>
                        <!-- ipk_akhir -->
                        <text x="15" y="115" fill="#f472b6" font-size="10" font-weight="bold" font-family="monospace">📊 ipk_akhir : decimal(4,2)</text>
                        <!-- lama_studi_semester -->
                        <text x="15" y="135" fill="#f472b6" font-size="10" font-weight="bold" font-family="monospace">📊 lama_studi : int(11)</text>
                        <!-- predikat -->
                        <text x="15" y="155" fill="#94a3b8" font-size="10" font-family="monospace">⚙️ predikat : varchar(50)</text>
                    </g>

                    <!-- TABLE 4: fact_ringkasan_akademik (Bottom Right) -->
                    <g transform="translate(540, 240)">
                        <!-- Main Card -->
                        <rect width="220" height="150" rx="10" fill="var(--card-bg)" stroke="#ec4899" stroke-width="1.5" />
                        <!-- Header Bar -->
                        <path d="M 0 10 A 10 10 0 0 1 10 0 L 210 0 A 10 10 0 0 1 220 10 L 220 35 L 0 35 Z" fill="url(#headerGradFact)" stroke="#ec4899" stroke-width="1" />
                        <!-- Header Text -->
                        <text x="15" y="22" fill="#f472b6" font-size="12" font-weight="800" font-family="sans-serif">fact_ringkasan_akademik</text>
                        
                        <!-- Columns -->
                        <!-- id_fact_akademik -->
                        <text x="15" y="55" fill="#f8fafc" font-size="10" font-family="monospace">🔑 id_fact_akademik : int(11)</text>
                        <!-- sk_mahasiswa -->
                        <text x="15" y="75" fill="#f1f5f9" font-size="10" font-family="monospace">🔗 sk_mahasiswa : int(11)</text>
                        <!-- sk_waktu -->
                        <text x="15" y="95" fill="#f1f5f9" font-size="10" font-family="monospace">🔗 sk_waktu : int(11)</text>
                        <!-- ips -->
                        <text x="15" y="115" fill="#f472b6" font-size="10" font-weight="bold" font-family="monospace">📊 ips : decimal(4,2)</text>
                        <!-- ipk -->
                        <text x="15" y="135" fill="#f472b6" font-size="10" font-weight="bold" font-family="monospace">📊 ipk : decimal(4,2)</text>
                    </g>
                </svg>
            </div>
        </div>

        <!-- ETL Flow & Schema Descriptions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
            <!-- Table Info Card -->
            <div class="rounded-[2rem] p-6 glass-card flex flex-col gap-4 text-xs font-bold leading-relaxed">
                <h3 class="text-xs font-extrabold text-slate-100 uppercase tracking-wider">Detail Komponen Skema</h3>
                <ul class="flex flex-col gap-3">
                    <li class="p-3 bg-slate-900/30 rounded-xl border border-white/5">
                        <strong class="text-indigo-400">Dimension Tables</strong>
                        <p class="text-text-muted mt-1 font-semibold text-[11px]">Menyimpan data kontekstual (Mahasiswa, Waktu Semester) untuk analisis slice & dice.</p>
                    </li>
                    <li class="p-3 bg-slate-900/30 rounded-xl border border-white/5">
                        <strong class="text-pink-400">Fact Tables</strong>
                        <p class="text-text-muted mt-1 font-semibold text-[11px]">Menyimpan metrics kuantitatif (IPK, IPS) beserta foreign keys yang menghubungkannya dengan dimensi.</p>
                    </li>
                </ul>
            </div>

            <!-- ETL Process Info Card -->
            <div class="rounded-[2rem] p-6 glass-card flex flex-col gap-4 text-xs font-bold leading-relaxed">
                <h3 class="text-xs font-extrabold text-slate-100 uppercase tracking-wider">Proses ETL (Extract-Transform-Load)</h3>
                <div class="flex flex-col gap-3">
                    <div class="flex items-start gap-3">
                        <span class="w-6 h-6 rounded-full bg-[#6366f1]/20 text-primary-light flex items-center justify-center font-extrabold shrink-0">1</span>
                        <div>
                            <strong class="text-slate-100">Extract</strong>
                            <p class="text-text-muted font-semibold text-[11px] mt-0.5">Pengambilan data mentah mahasiswa dan semester dari sistem operasional akademik (SIAKAD).</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="w-6 h-6 rounded-full bg-[#6366f1]/20 text-primary-light flex items-center justify-center font-extrabold shrink-0">2</span>
                        <div>
                            <strong class="text-slate-100">Transform</strong>
                            <p class="text-text-muted font-semibold text-[11px] mt-0.5">Pembersihan data (cleaning), agregasi IPK, pembentukan surrogate keys (sk_mahasiswa, sk_waktu).</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="w-6 h-6 rounded-full bg-[#6366f1]/20 text-primary-light flex items-center justify-center font-extrabold shrink-0">3</span>
                        <div>
                            <strong class="text-slate-100">Load</strong>
                            <p class="text-text-muted font-semibold text-[11px] mt-0.5">Memuat data ke dalam tabel fakta dan dimensi yang siap dikonsumsi oleh dashboard analitik visual ini.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="mt-auto py-6 border-t border-white/5 flex flex-col sm:flex-row justify-between items-center gap-4 text-[10px] font-bold text-text-muted">
            <p>&copy; 2026 Teknik Komputer dan Jaringan PNUP. Arsitektur Data Warehouse.</p>
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

window.addEventListener('load', () => {
    applyTheme();
    setTimeout(() => {
        const pre = document.getElementById('preloader');
        if(pre) {
            pre.style.opacity = '0';
            setTimeout(() => pre.style.display = 'none', 500);
        }
    }, 500);
});

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
    }, 300);
}

// Close dropdown on outside click
window.addEventListener('click', () => {
    const dropdown = document.getElementById('profileDropdown');
    if (dropdown && !dropdown.classList.contains('hidden')) {
        dropdown.classList.add('hidden');
    }
});
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
    <a href="skema.php" class="flex flex-col items-center justify-center text-primary-light px-2 py-1 rounded-xl bg-white/5">
        <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1">schema</span>
        <span class="text-[8px] font-bold mt-0.5">Skema</span>
    </a>
    <a href="api_docs.php" class="flex flex-col items-center justify-center text-slate-400 hover:text-slate-200">
        <span class="material-symbols-outlined text-[20px]">api</span>
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
