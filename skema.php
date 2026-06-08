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
                </div>
            </div>
        </div>

        <!-- Star Schema Interactive Graph (SVG) -->
        <div class="rounded-[2rem] p-6 glass-card flex flex-col gap-4">
            <h3 class="text-xs font-extrabold text-slate-100 uppercase tracking-wider">Visualisasi Star Schema DW</h3>
            <div class="overflow-x-auto w-full flex justify-center py-6 bg-slate-950/20 rounded-2xl">
                <!-- SVG diagram illustrating Star Schema -->
                <svg width="800" height="420" viewBox="0 0 800 420" fill="none" xmlns="http://www.w3.org/2000/svg" class="max-w-full">
                    <!-- Lines connecting Dimensions to Facts -->
                    <path d="M 230 110 L 350 200" stroke="#818cf8" stroke-width="2" stroke-dasharray="4" />
                    <path d="M 230 310 L 350 220" stroke="#818cf8" stroke-width="2" stroke-dasharray="4" />
                    <path d="M 570 210 L 470 210" stroke="#818cf8" stroke-width="2" stroke-dasharray="4" />

                    <!-- Dimension Table: Mahasiswa -->
                    <rect x="30" y="50" width="200" height="120" rx="12" fill="rgba(99, 102, 241, 0.12)" stroke="#6366f1" stroke-width="1.5"/>
                    <text x="130" y="75" fill="#f8fafc" font-size="11" font-weight="bold" text-anchor="middle">dim_mahasiswa_tkj</text>
                    <line x1="30" y1="85" x2="230" y2="85" stroke="#6366f1" stroke-width="1"/>
                    <text x="45" y="105" fill="#94a3b8" font-size="10" font-family="monospace">🔑 sk_mahasiswa (PK)</text>
                    <text x="45" y="120" fill="#94a3b8" font-size="10">🔹 nim</text>
                    <text x="45" y="135" fill="#94a3b8" font-size="10">🔹 nama_mahasiswa</text>
                    <text x="45" y="150" fill="#94a3b8" font-size="10">🔹 status_akademik</text>

                    <!-- Dimension Table: Waktu -->
                    <rect x="30" y="250" width="200" height="120" rx="12" fill="rgba(99, 102, 241, 0.12)" stroke="#6366f1" stroke-width="1.5"/>
                    <text x="130" y="275" fill="#f8fafc" font-size="11" font-weight="bold" text-anchor="middle">dim_waktu</text>
                    <line x1="30" y1="285" x2="230" y2="285" stroke="#6366f1" stroke-width="1"/>
                    <text x="45" y="305" fill="#94a3b8" font-size="10" font-family="monospace">🔑 sk_waktu (PK)</text>
                    <text x="45" y="320" fill="#94a3b8" font-size="10">🔹 tipe_semester</text>
                    <text x="45" y="335" fill="#94a3b8" font-size="10">🔹 tahun_ajaran</text>

                    <!-- Fact Table: Ringkasan Akademik -->
                    <rect x="310" y="140" width="200" height="140" rx="12" fill="rgba(236, 72, 153, 0.15)" stroke="#ec4899" stroke-width="2"/>
                    <text x="410" y="165" fill="#f8fafc" font-size="12" font-weight="bold" text-anchor="middle">fact_ringkasan_akademik</text>
                    <line x1="310" y1="175" x2="510" y2="175" stroke="#ec4899" stroke-width="1"/>
                    <text x="325" y="195" fill="#f1f5f9" font-size="10" font-family="monospace">🔑 sk_mahasiswa (FK)</text>
                    <text x="325" y="210" fill="#f1f5f9" font-size="10" font-family="monospace">🔑 sk_waktu (FK)</text>
                    <text x="325" y="230" fill="#ec4899" font-size="10" font-weight="bold">📊 ips (Measure)</text>
                    <text x="325" y="245" fill="#ec4899" font-size="10" font-weight="bold">📊 ipk (Measure)</text>

                    <!-- Fact Table: Kelulusan (OLAP Side Fact) -->
                    <rect x="570" y="150" width="200" height="120" rx="12" fill="rgba(236, 72, 153, 0.12)" stroke="#ec4899" stroke-width="1"/>
                    <text x="670" y="175" fill="#f8fafc" font-size="11" font-weight="bold" text-anchor="middle">fact_kelulusan_tkj</text>
                    <line x1="570" y1="185" x2="770" y2="185" stroke="#ec4899" stroke-width="1"/>
                    <text x="585" y="205" fill="#94a3b8" font-size="10" font-family="monospace">🔑 sk_mahasiswa (FK)</text>
                    <text x="585" y="220" fill="#94a3b8" font-size="10">🔹 ipk_akhir</text>
                    <text x="585" y="235" fill="#94a3b8" font-size="10">🔹 predikat</text>
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

function openSettingsModal() {
    document.getElementById('settingsModal').classList.replace('hidden', 'flex');
}
function closeSettingsModal() {
    document.getElementById('settingsModal').classList.replace('flex', 'hidden');
}
</script>
</body>
</html>
