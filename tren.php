<?php
// File: tren.php
session_start();

// Redireksi jika belum login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

// Ambil summary untuk data angkatan list
$summary = callAPI(API_BASE . "?type=summary");
$angkatan_list = $summary['angkatan_list'] ?? [];

// Ambil data tren per angkatan secara total
$cohort_data = callAPI(API_BASE . "?type=tren_angkatan");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Analisis Tren IPK Cohort – TKJ PNUP</title>
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
        .glass-input {
            background: var(--input-bg) !important;
            border: 1px solid var(--input-border) !important;
            color: var(--text-main) !important;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            transition: all 0.3s ease;
        }
        body.light-mode .glass-input { color: #1e1b4b !important; }
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
    <p class="mt-4 text-xs font-bold text-slate-400">Memuat Tren Cohort...</p>
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
                <a class="w-12 h-12 rounded-xl flex items-center justify-center text-primary-light glass-btn-active shrink-0 relative group" href="tren.php" title="Analisis Tren Cohort">
                    <span class="material-symbols-outlined text-[24px]">timeline</span>
                    <span class="absolute right-0 top-1/2 -translate-y-1/2 w-1.5 h-6 bg-[#6366f1] rounded-l-full shadow-[0_0_12px_rgba(99,102,241,0.8)]"></span>
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
                    <span class="material-symbols-outlined text-[#6366f1]">timeline</span> Analisis Tren IPK Cohort (Angkatan)
                </h1>
                <p class="text-xs font-bold text-text-muted mt-0.5">Analisis performa mahasiswa antar angkatan (Roll Up / Drill Down secara waktu).</p>
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

        <!-- Main Trend Line Chart -->
        <div class="rounded-[2rem] p-6 glass-card flex flex-col gap-4">
            <div class="flex justify-between items-center">
                <h3 class="text-xs font-extrabold text-slate-100 uppercase tracking-wider">Tren Rata-rata IPK per Semester per Angkatan</h3>
                <span class="text-[10px] text-pink-400 font-bold px-2 py-0.5 bg-pink-500/10 rounded-full">OLAP Roll-Up</span>
            </div>
            <div class="relative h-96 w-full">
                <canvas id="cohortTrendChart"></canvas>
            </div>
        </div>

        <!-- Cohort Summary Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full">
            <?php
            // Calculate aggregate statistics for active cohort groups
            $grouped = [];
            foreach ($cohort_data as $data) {
                $grouped[$data['angkatan']][] = $data['avg_ipk'];
            }
            ?>
            <?php foreach ($grouped as $ang => $ipks): 
                $latest_ipk = end($ipks);
                $initial_ipk = reset($ipks);
                $growth = $latest_ipk - $initial_ipk;
                $growth_text = $growth >= 0 ? "+" . number_format($growth, 2) : number_format($growth, 2);
                $growth_color = $growth >= 0 ? "text-emerald-400" : "text-rose-400";
            ?>
            <div class="rounded-[2rem] p-6 glass-card flex flex-col gap-4">
                <div class="flex justify-between items-center border-b border-white/5 pb-2">
                    <h4 class="text-xs font-extrabold text-slate-100">Angkatan <?= htmlspecialchars($ang) ?></h4>
                    <span class="text-[9px] font-bold text-text-muted">Summary Cohort</span>
                </div>
                <div class="flex justify-between items-center mt-2">
                    <div>
                        <p class="text-[10px] text-text-muted font-bold">IPK Terakhir</p>
                        <p class="text-2xl font-extrabold text-slate-100 mt-1"><?= number_format($latest_ipk, 2) ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-text-muted font-bold">Perubahan IPK</p>
                        <p class="text-sm font-extrabold <?= $growth_color ?> mt-1"><?= $growth_text ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <footer class="mt-auto py-6 border-t border-white/5 flex flex-col sm:flex-row justify-between items-center gap-4 text-[10px] font-bold text-text-muted">
            <p>&copy; 2026 Teknik Komputer dan Jaringan PNUP. Laporan Analisis Tren.</p>
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
    renderChart();
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
    renderChart();
});

function openSettingsModal() {
    document.getElementById('settingsModal').classList.replace('hidden', 'flex');
}
function closeSettingsModal() {
    document.getElementById('settingsModal').classList.replace('flex', 'hidden');
}

// Chart.js Cohort Trend Drawing
let trendChartObj = null;

function renderChart() {
    const textColor = isDarkMode ? '#94a3b8' : '#6366f1';
    const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.05)' : 'rgba(99, 102, 241, 0.1)';

    const rawData = <?= json_encode($cohort_data) ?>;
    
    // Group rawData by Angkatan
    const cohorts = {};
    const semesters = new Set();
    
    rawData.forEach(d => {
        semesters.add(d.label);
        if (!cohorts[d.angkatan]) {
            cohorts[d.angkatan] = [];
        }
        cohorts[d.angkatan].push({ label: d.label, val: d.avg_ipk });
    });

    const semesterList = Array.from(semesters).sort(); // Sort semesters to align axes

    // Prepare datasets
    const colors = ['#6366f1', '#ec4899', '#3b82f6', '#10b981', '#f59e0b'];
    let colorIndex = 0;
    
    const datasets = Object.keys(cohorts).map(ang => {
        // Map average IPK values aligned to sorted semesterList
        const dataAligned = semesterList.map(semLabel => {
            const match = cohorts[ang].find(item => item.label === semLabel);
            return match ? match.val : null;
        });

        const color = colors[colorIndex++ % colors.length];
        return {
            label: 'Angkatan ' + ang,
            data: dataAligned,
            borderColor: color,
            backgroundColor: 'transparent',
            borderWidth: 3,
            tension: 0.3,
            pointBackgroundColor: color,
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 5,
        };
    });

    const ctx = document.getElementById('cohortTrendChart').getContext('2d');
    if (trendChartObj) trendChartObj.destroy();
    
    trendChartObj = new Chart(ctx, {
        type: 'line',
        data: {
            labels: semesterList,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    min: 0,
                    max: 4.0,
                    grid: { color: gridColor },
                    ticks: { color: textColor, font: { weight: 'bold' } }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: textColor, font: { weight: 'bold' } }
                }
            },
            plugins: {
                legend: { labels: { color: textColor, font: { weight: 'bold' } } }
            }
        }
    });
}
</script>
</body>
</html>
