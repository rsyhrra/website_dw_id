<?php
// File: index.php
require_once 'config.php'; // Panggil config.php yang memuat pengaturan URL dan API_KEY

// Menarik data dari Endpoint API menggunakan fungsi callAPI
$summary = callAPI(API_BASE . "?type=summary");
$ipkData = callAPI(API_BASE . "?type=chart_ipk");
$predikatData = callAPI(API_BASE . "?type=chart_predikat");

// Fallback data (berjaga-jaga jika API gagal diakses / API kosong)
$total_mhs = $summary['total_mahasiswa'] ?? 0;
$avg_ipk = number_format((float)($summary['rata_rata_ipk'] ?? 0), 2);
$cumlaude = $summary['total_cumlaude'] ?? 0;

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>TKJ PNUP Academic Dashboard</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "primary-fixed": "#dce1ff", "primary": "#00236f", "surface-bright": "#faf8ff",
                        "on-error": "#ffffff", "surface-container-lowest": "#ffffff", "primary-fixed-dim": "#b6c4ff",
                        "inverse-on-surface": "#f1f0f7", "on-tertiary-fixed-variant": "#773205", "tertiary-fixed-dim": "#ffb691",
                        "tertiary-container": "#6e2c00", "surface-container-high": "#e9e7ef", "surface-variant": "#e3e1e9",
                        "error": "#ba1a1a", "surface-container": "#eeedf4", "inverse-primary": "#b6c4ff",
                        "on-background": "#1a1b21", "background": "#faf8ff", "on-tertiary-fixed": "#341100",
                        "on-secondary-fixed": "#002113", "surface-dim": "#dad9e1", "tertiary-fixed": "#ffdbcb",
                        "outline": "#757682", "secondary": "#006c49", "on-tertiary-container": "#f39461",
                        "on-surface": "#1a1b21", "on-primary-container": "#90a8ff", "on-secondary-fixed-variant": "#005236",
                        "surface-container-low": "#f4f3fa", "on-tertiary": "#ffffff", "secondary-container": "#6cf8bb",
                        "secondary-fixed-dim": "#4edea3", "on-secondary": "#ffffff", "on-surface-variant": "#444651",
                        "secondary-fixed": "#6ffbbe", "surface": "#faf8ff", "on-primary": "#ffffff",
                        "surface-container-highest": "#e3e1e9", "outline-variant": "#c5c5d3", "surface-tint": "#4059aa",
                        "on-primary-fixed": "#00164e", "on-secondary-container": "#00714d", "primary-container": "#1e3a8a",
                        "on-primary-fixed-variant": "#264191", "inverse-surface": "#2f3036", "on-error-container": "#93000a",
                        "error-container": "#ffdad6", "tertiary": "#4b1c00"
                    },
                    "fontFamily": {
                        "body-sm": ["Inter"], "label-md": ["Inter"], "headline-md": ["Inter"],
                        "title-lg": ["Inter"], "display-lg": ["Inter"], "headline-lg": ["Inter"],
                        "body-md": ["Inter"], "headline-lg-mobile": ["Inter"]
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .material-symbols-outlined { font-family: 'Material Symbols Outlined'; }
        body { font-family: 'Inter', sans-serif; background-color: #faf8ff; }
        .ambient-shadow-md { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); }
        .ambient-shadow-lg { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02); }
    </style>
</head>
<body class="bg-background text-on-background min-h-screen flex flex-col">

<header class="bg-surface/80 backdrop-blur-md border-b border-outline-variant/30 shadow-sm docked full-width top-0 sticky z-[1000] flex justify-between items-center w-full px-8 h-16">
    <div class="flex items-center gap-4">
        <h1 class="text-2xl font-bold text-primary">TKJ PNUP</h1>
        <div class="hidden md:flex items-center gap-2 bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full text-xs font-medium">
            <span class="w-2 h-2 rounded-full bg-secondary animate-pulse"></span>
            Live Data API
        </div>
    </div>
    <nav class="hidden md:flex gap-6">
        <a class="text-primary border-b-2 border-primary pb-1 font-bold transition-colors duration-200" href="#">Dashboard</a>
        <a class="text-on-surface-variant hover:text-primary transition-colors duration-200" href="akademik.php">Academic</a>
    </nav>
    <div class="flex items-center gap-4">
        <button class="text-on-surface-variant hover:text-primary transition-colors duration-200">
            <span class="material-symbols-outlined">notifications</span>
        </button>
        <img alt="Profile" class="w-10 h-10 rounded-full border-2 border-surface object-cover" src="https://ui-avatars.com/api/?name=Admin+TKJ&background=00236f&color=fff"/>
    </div>
</header>

<div class="flex flex-1 max-w-[1280px] mx-auto w-full">
    <aside class="bg-surface-container-low border-r border-outline-variant/20 shadow-sm h-screen w-64 hidden lg:flex flex-col fixed left-0 top-0 pt-20 pb-8 px-4 z-[900]">
        <div class="mb-8 px-4">
            <h2 class="text-lg font-black text-primary">Integrasi Data</h2>
            <p class="text-xs text-on-surface-variant mt-1">Academic Portal 2026</p>
        </div>
        <nav class="flex-1 flex flex-col gap-2">
            <a class="flex items-center gap-3 px-4 py-3 bg-secondary-container text-on-secondary-container rounded-lg font-bold text-sm hover:translate-x-1 transition-transform" href="#">
                <span class="material-symbols-outlined">dashboard</span> Overview
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-high rounded-lg text-sm hover:translate-x-1 transition-transform" href="#">
                <span class="material-symbols-outlined">groups</span> Student Data
            </a>
        </nav>
        <div class="flex flex-col gap-2 border-t border-outline-variant/20 pt-4">
            <a class="flex items-center gap-3 px-4 py-2 text-error hover:bg-error-container rounded-lg text-sm transition-colors" href="#">
                <span class="material-symbols-outlined">logout</span> Log Out
            </a>
        </div>
    </aside>

    <main class="flex-1 lg:ml-64 p-4 md:p-8 w-full">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-primary mb-2">Dashboard Integrasi Data</h2>
            <p class="text-base text-on-surface-variant">Ikhtisar data akademik dan integrasi sistem terkini.</p>
        </div>

        <section class="mb-12">
            <h3 class="text-lg font-semibold text-on-background mb-4">Ringkasan Akademik</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-xl p-6 ambient-shadow-md hover:scale-[1.02] transition-all">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-xs text-on-surface-variant mb-1 uppercase tracking-wider font-bold">Total Mahasiswa</p>
                            <h4 class="text-4xl font-bold text-primary"><?= $total_mhs ?></h4>
                        </div>
                        <div class="bg-primary-fixed p-3 rounded-lg text-primary">
                            <span class="material-symbols-outlined text-3xl" data-weight="fill">school</span>
                        </div>
                    </div>
                </div>
                <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-xl p-6 ambient-shadow-md hover:scale-[1.02] transition-all">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-xs text-on-surface-variant mb-1 uppercase tracking-wider font-bold">Rata-Rata IPK</p>
                            <h4 class="text-4xl font-bold text-primary"><?= $avg_ipk ?></h4>
                        </div>
                        <div class="bg-secondary-container p-3 rounded-lg text-secondary">
                            <span class="material-symbols-outlined text-3xl" data-weight="fill">monitoring</span>
                        </div>
                    </div>
                </div>
                <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-xl p-6 ambient-shadow-md hover:scale-[1.02] transition-all">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-xs text-on-surface-variant mb-1 uppercase tracking-wider font-bold">Lulusan Cum Laude</p>
                            <h4 class="text-4xl font-bold text-primary"><?= $cumlaude ?></h4>
                        </div>
                        <div class="bg-tertiary-fixed p-3 rounded-lg text-tertiary">
                            <span class="material-symbols-outlined text-3xl" data-weight="fill">military_tech</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-xl p-6 ambient-shadow-md">
                    <h4 class="text-lg font-semibold text-on-background mb-6">Tren IPK Mahasiswa</h4>
                    <div class="relative h-64 w-full">
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>
                <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-xl p-6 ambient-shadow-md">
                    <h4 class="text-lg font-semibold text-on-background mb-6">Distribusi Predikat Kelulusan</h4>
                    <div class="relative h-64 w-full flex justify-center">
                        <canvas id="doughnutChart"></canvas>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            
            <div class="xl:col-span-2 flex flex-col gap-8">
                <section class="bg-surface-container-lowest border border-outline-variant/30 rounded-xl p-6 ambient-shadow-md">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="material-symbols-outlined text-primary text-2xl" data-weight="fill">search</span>
                        <h3 class="text-lg font-semibold text-on-background">Pencarian Referensi Akademik</h3>
                    </div>
                    <form action="" method="GET" class="flex gap-4 mb-6">
                        <input name="q" class="flex-1 rounded-lg border border-outline-variant bg-surface px-4 py-2 text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none" placeholder="Masukkan kata kunci jurnal atau penulis..." type="text"/>
                        <button type="submit" class="bg-primary text-on-primary px-6 py-2 rounded-lg text-sm font-bold hover:bg-primary-container transition-colors shadow-sm flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">search</span> Cari Jurnal
                        </button>
                    </form>
                    <div class="space-y-4">
                        <div class="border border-outline-variant/20 rounded-lg p-4 hover:bg-surface-container-low transition-colors flex flex-col md:flex-row justify-between md:items-center gap-4">
                            <div>
                                <h5 class="text-lg text-primary font-semibold mb-1">Implementasi Machine Learning pada Sistem Akademik</h5>
                                <p class="text-sm text-on-surface-variant mb-2">A. Rahman, B. Susanto - Jurnal Informatika PNUP, 2023</p>
                                <span class="inline-block bg-surface-container-high text-on-surface-variant px-2 py-1 rounded text-xs font-bold">Cited by 12</span>
                            </div>
                            <button class="shrink-0 bg-surface-container text-primary border border-outline-variant/30 px-4 py-2 rounded-lg text-sm font-bold hover:bg-surface-variant transition-colors flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm">download</span> Download PDF
                            </button>
                        </div>
                    </div>
                </section>

                <section class="bg-surface-container-lowest border border-outline-variant/30 rounded-xl p-6 ambient-shadow-md">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="material-symbols-outlined text-primary text-2xl" data-weight="fill">account_circle</span>
                        <h3 class="text-lg font-semibold text-on-background">Profil & Upload Foto API</h3>
                    </div>
                    <div class="flex flex-col md:flex-row gap-8 items-start">
                        <div class="flex flex-col items-center gap-4">
                            <div class="w-24 h-24 rounded-full bg-surface-container-highest flex items-center justify-center overflow-hidden border-4 border-surface shadow-sm">
                                <img alt="Current profile" class="w-full h-full object-cover" src="https://ui-avatars.com/api/?name=Admin+TKJ&background=00236f&color=fff"/>
                            </div>
                            <div class="text-center">
                                <h4 class="text-lg font-bold text-on-background">Dr. Administrator</h4>
                                <p class="text-sm text-on-surface-variant">admin.tkj@pnup.ac.id</p>
                            </div>
                        </div>
                        <div class="flex-1 w-full">
                            <div class="bg-surface-container-low p-4 rounded-lg mb-4 text-sm text-on-surface-variant border border-outline-variant/20 border-dashed">
                                <code class="text-xs text-outline block mb-2">// Form ini menggunakan multipart/form-data untuk dikonversi menjadi Base64 di backend (Modul V).</code>
                                <p>Unggah foto profil baru untuk memperbarui identitas sistem. Format didukung: JPG, PNG (Max 2MB).</p>
                            </div>
                            <form action="" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row items-center gap-4">
                                <input name="fileUpload" class="block w-full text-sm text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-fixed file:text-on-primary-fixed hover:file:bg-primary-fixed-dim transition-colors cursor-pointer border border-outline-variant/30 rounded-lg bg-surface" id="profile_pic" type="file" accept="image/*"/>
                                <button type="submit" class="shrink-0 bg-primary text-on-primary px-6 py-2 rounded-lg text-sm font-bold hover:bg-primary-container transition-colors shadow-sm w-full sm:w-auto">
                                    Upload Foto
                                </button>
                            </form>
                        </div>
                    </div>
                </section>
            </div>

            <div class="xl:col-span-1">
                <section class="bg-surface-container-lowest border border-outline-variant/30 rounded-xl p-6 ambient-shadow-md h-full">
                    <div class="flex items-center gap-3 mb-6 border-b border-outline-variant/20 pb-4">
                        <span class="material-symbols-outlined text-secondary text-2xl" data-weight="fill">rss_feed</span>
                        <h3 class="text-lg font-semibold text-on-background">Berita Terkini (Scrapping)</h3>
                    </div>
                    <div class="flex flex-col gap-6 relative">
                        <div class="absolute left-3 top-2 bottom-2 w-px bg-outline-variant/30 z-0"></div>
                        <div class="relative z-10 flex gap-4">
                            <div class="w-6 h-6 rounded-full bg-primary-fixed border-2 border-surface flex-shrink-0 mt-1 flex items-center justify-center">
                                <div class="w-2 h-2 rounded-full bg-primary"></div>
                            </div>
                            <div>
                                <h5 class="text-base text-on-background font-semibold hover:text-primary transition-colors">Face Detection using Python</h5>
                                <p class="text-sm text-on-surface-variant mt-1">Hasil ekstraksi web berita teknologi terpusat.</p>
                                <span class="text-xs text-outline mt-2 block">Source: tutscode.net</span>
                            </div>
                        </div>
                        <div class="relative z-10 flex gap-4">
                            <div class="w-6 h-6 rounded-full bg-secondary-container border-2 border-surface flex-shrink-0 mt-1 flex items-center justify-center">
                                <div class="w-2 h-2 rounded-full bg-secondary"></div>
                            </div>
                            <div>
                                <h5 class="text-base text-on-background font-semibold hover:text-primary transition-colors">Data Mining & Web Scrapping</h5>
                                <p class="text-sm text-on-surface-variant mt-1">Panduan praktis pengumpulan data otomatis untuk riset.</p>
                                <span class="text-xs text-outline mt-2 block">Source: tutscode.net</span>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>
</div>

<footer class="bg-surface-container-highest border-t border-outline-variant/30 w-full py-6 px-8 flex flex-col md:flex-row justify-between items-center gap-4 mt-auto">
    <p class="text-on-surface-variant text-sm">© 2026 Teknik Komputer dan Jaringan PNUP. Academic Data Integration.</p>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mengambil data JSON dari PHP yang ditarik lewat API Backend
        const dataIPK = <?= json_encode($ipkData) ?>;
        const dataPred = <?= json_encode($predikatData) ?>;

        // Validasi apakah data tidak kosong (fallback jika gagal load API)
        const lineLabels = dataIPK.length > 0 ? dataIPK.map(i => 'SK Waktu ' + i.sk_waktu) : ['Smt 1', 'Smt 2', 'Smt 3'];
        const lineData = dataIPK.length > 0 ? dataIPK.map(i => i.ipk) : [3.1, 3.25, 3.3];

        const pieLabels = dataPred.length > 0 ? dataPred.map(i => i.predikat) : ['Cum Laude', 'Sangat Memuaskan', 'Memuaskan'];
        const pieData = dataPred.length > 0 ? dataPred.map(i => i.jumlah) : [142, 60, 12];

        // Render Line Chart
        const ctxLine = document.getElementById('lineChart').getContext('2d');
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: lineLabels,
                datasets: [{
                    label: 'IPK Berjalan',
                    data: lineData,
                    borderColor: '#00236f',
                    backgroundColor: 'rgba(0, 35, 111, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#006c49',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { min: 3.0, max: 4.0, grid: { color: 'rgba(197, 197, 211, 0.2)' } },
                    x: { grid: { display: false } }
                },
                plugins: { legend: { display: false } }
            }
        });

        // Render Doughnut Chart
        const ctxDoughnut = document.getElementById('doughnutChart').getContext('2d');
        new Chart(ctxDoughnut, {
            type: 'doughnut',
            data: {
                labels: pieLabels,
                datasets: [{
                    data: pieData,
                    backgroundColor: ['#00236f', '#006c49', '#ffdbcb'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 20, usePointStyle: true, font: { family: 'Inter', size: 12 } }
                    }
                }
            }
        });
    });
</script>
</body>
</html>s