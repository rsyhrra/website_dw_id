<?php
// File: index.php
require_once 'config.php';

// Ambil data dari API
$summary      = callAPI(API_BASE . "?type=summary");
$ipkData      = callAPI(API_BASE . "?type=chart_ipk");
$predikatData = callAPI(API_BASE . "?type=chart_predikat");

// Fallback jika API kosong
$total_mhs = $summary['total_mahasiswa'] ?? 0;
$avg_ipk   = number_format((float)($summary['rata_rata_ipk'] ?? 0), 2);
$cumlaude  = $summary['total_cumlaude'] ?? 0;

// Pencarian jurnal dari API (dinamis)
$search_query   = trim($_GET['q'] ?? '');
$search_results = [];
if ($search_query !== '') {
    $search_results = callAPI(API_BASE . "?type=search&q=" . urlencode($search_query));
}
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
                colors: {
                    "primary": "#2563eb", "primary-fixed": "#dbeafe", "primary-fixed-dim": "#bfdbfe",
                    "on-primary": "#ffffff", "primary-container": "#eff6ff", "on-primary-container": "#1e3a8a",
                    "secondary": "#0ea5e9", "secondary-container": "#e0f2fe", "on-secondary-container": "#0369a1",
                    "on-secondary": "#ffffff", "tertiary": "#4f46e5", "tertiary-fixed": "#e0e7ff",
                    "surface": "#ffffff", "on-surface": "#0f172a", "on-surface-variant": "#64748b",
                    "surface-container-lowest": "#ffffff", "surface-container-low": "#f8fafc",
                    "surface-container": "#f1f5f9", "surface-container-high": "#e2e8f0",
                    "surface-container-highest": "#cbd5e1", "background": "#f8fafc", "on-background": "#0f172a",
                    "outline": "#cbd5e1", "outline-variant": "#e2e8f0",
                    "error": "#ef4444", "error-container": "#fee2e2",
                },
                fontFamily: {
                    sans: ['Inter', 'sans-serif'],
                },
                boxShadow: {
                    'soft': '0 4px 20px -2px rgba(15, 23, 42, 0.05)',
                    'soft-sm': '0 2px 10px -1px rgba(15, 23, 42, 0.03)',
                }
            }
        }
    }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .material-symbols-outlined { font-family: 'Material Symbols Outlined'; font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .ambient-shadow-md { box-shadow: 0 2px 10px -1px rgba(15, 23, 42, 0.03); }
        .ambient-shadow-lg { box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05); }
        .stat-card { transition: transform .2s ease, border-color .2s ease; }
        .stat-card:hover { transform: translateY(-2px); border-color: #2563eb; }
        
        /* Custom scrollbar for clean look */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-background text-on-background min-h-screen flex flex-col">

<!-- ====== HEADER ====== -->
<header class="bg-surface/90 backdrop-blur-md border-b border-outline-variant sticky top-0 z-[1000] flex justify-between items-center w-full px-8 h-16 shadow-soft-sm">
    <div class="flex items-center gap-4">
        <h1 class="text-2xl font-bold text-primary tracking-tight">TKJ PNUP</h1>
    </div>
    <nav class="hidden md:flex gap-8">
        <a class="text-primary border-b-2 border-primary pb-1 font-semibold" href="index.php">Dashboard</a>
        <a class="text-on-surface-variant hover:text-primary font-medium transition-colors" href="akademik.php">Akademik</a>
    </nav>
    <div class="flex items-center gap-4">
        <button class="text-on-surface-variant hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-[22px]">notifications</span>
        </button>
        <img alt="Profile" class="w-9 h-9 rounded-full border border-outline-variant object-cover shadow-sm"
             src="https://ui-avatars.com/api/?name=Admin+TKJ&background=eff6ff&color=1e40af"/>
    </div>
</header>

<div class="flex flex-1 max-w-[1400px] mx-auto w-full">

    <!-- ====== SIDEBAR ====== -->
    <aside class="bg-surface border-r border-outline-variant w-64 hidden lg:flex flex-col fixed left-0 top-0 pt-20 pb-8 px-4 z-[900] h-screen shadow-soft-sm">
        <div class="mb-8 px-4">
            <h2 class="text-sm font-bold text-on-surface-variant uppercase tracking-wider">Portal Data</h2>
            <p class="text-xs text-on-surface-variant opacity-70 mt-1">Sistem Informasi 2026</p>
        </div>
        <nav class="flex-1 flex flex-col gap-1.5">
            <a class="flex items-center gap-3 px-4 py-2.5 bg-primary-container text-primary rounded-lg font-semibold text-sm transition-all" href="index.php">
                <span class="material-symbols-outlined text-[20px]">dashboard</span> Ringkasan
            </a>
            <a class="flex items-center gap-3 px-4 py-2.5 text-on-surface-variant hover:bg-surface-container hover:text-on-surface rounded-lg text-sm font-medium transition-all" href="akademik.php">
                <span class="material-symbols-outlined text-[20px]">groups</span> Data Mahasiswa
            </a>
        </nav>
        <div class="border-t border-outline-variant pt-4 mt-auto">
            <a class="flex items-center gap-3 px-4 py-2.5 text-error hover:bg-error-container rounded-lg text-sm font-medium transition-all" href="#">
                <span class="material-symbols-outlined text-[20px]">logout</span> Keluar
            </a>
        </div>
    </aside>

    <!-- ====== MAIN CONTENT ====== -->
    <main class="flex-1 lg:ml-64 p-4 md:p-8 w-full">

        <div class="mb-8">
            <h2 class="text-3xl font-bold text-primary mb-2">Dashboard Integrasi Data</h2>
            <p class="text-base text-on-surface-variant">Ikhtisar data akademik dan integrasi sistem terkini.</p>
        </div>

        <!-- STAT CARDS -->
        <section class="mb-10">
            <h3 class="text-lg font-semibold text-on-surface mb-4">Ringkasan Akademik</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

                <div class="stat-card bg-surface border border-outline-variant rounded-xl p-6 ambient-shadow-md relative overflow-hidden">
                    <div class="flex justify-between items-start relative z-10">
                        <div>
                            <p class="text-xs text-on-surface-variant mb-1 uppercase tracking-wider font-bold">Total Mahasiswa</p>
                            <h4 class="text-4xl font-bold text-primary"><?= htmlspecialchars($total_mhs) ?></h4>
                            <p class="text-xs text-secondary mt-2 font-medium">Aktif & Lulus</p>
                        </div>
                        <div class="bg-primary-container p-3 rounded-xl text-primary">
                            <span class="material-symbols-outlined text-[28px]" style="font-variation-settings:'FILL' 1">school</span>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-surface border border-outline-variant rounded-xl p-6 ambient-shadow-md relative overflow-hidden">
                    <div class="flex justify-between items-start relative z-10">
                        <div>
                            <p class="text-xs text-on-surface-variant mb-1 uppercase tracking-wider font-bold">Rata-Rata IPK</p>
                            <h4 class="text-4xl font-bold text-primary"><?= htmlspecialchars($avg_ipk) ?></h4>
                            <p class="text-xs text-secondary mt-2 font-medium">Dari seluruh semester</p>
                        </div>
                        <div class="bg-secondary-container p-3 rounded-xl text-secondary">
                            <span class="material-symbols-outlined text-[28px]" style="font-variation-settings:'FILL' 1">monitoring</span>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-surface border border-outline-variant rounded-xl p-6 ambient-shadow-md relative overflow-hidden">
                    <div class="flex justify-between items-start relative z-10">
                        <div>
                            <p class="text-xs text-on-surface-variant mb-1 uppercase tracking-wider font-bold">Lulusan Cum Laude</p>
                            <h4 class="text-4xl font-bold text-primary"><?= htmlspecialchars($cumlaude) ?></h4>
                            <p class="text-xs text-tertiary mt-2 font-medium">IPK ≥ 3.51</p>
                        </div>
                        <div class="bg-tertiary-fixed p-3 rounded-xl text-tertiary">
                            <span class="material-symbols-outlined text-[28px]" style="font-variation-settings:'FILL' 1">military_tech</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CHARTS -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-surface border border-outline-variant rounded-xl p-6 ambient-shadow-md">
                    <h4 class="text-lg font-semibold text-on-surface mb-1">Tren IPK Rata-Rata</h4>
                    <p class="text-xs text-on-surface-variant mb-6">Per semester, semua mahasiswa aktif</p>
                    <div class="relative h-64 w-full">
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>
                <div class="bg-surface border border-outline-variant rounded-xl p-6 ambient-shadow-md">
                    <h4 class="text-lg font-semibold text-on-surface mb-1">Distribusi Predikat Kelulusan</h4>
                    <p class="text-xs text-on-surface-variant mb-6">Dari seluruh data lulusan</p>
                    <div class="relative h-64 w-full flex justify-center">
                        <canvas id="doughnutChart"></canvas>
                    </div>
                </div>
            </div>
        </section>

        <!-- BOTTOM SECTION -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            <div class="xl:col-span-2 flex flex-col gap-8">

                <!-- PENCARIAN JURNAL (DINAMIS) -->
                <section class="bg-surface border border-outline-variant rounded-xl p-6 ambient-shadow-md">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="material-symbols-outlined text-primary text-[24px]" style="font-variation-settings:'FILL' 1">search</span>
                        <h3 class="text-lg font-semibold text-on-surface">Pencarian Referensi Akademik</h3>
                    </div>
                    <form action="" method="GET" class="flex gap-4 mb-6">
                        <input name="q" value="<?= htmlspecialchars($search_query) ?>"
                               class="flex-1 rounded-lg border border-outline-variant bg-surface px-4 py-2 text-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none"
                               placeholder="Masukkan kata kunci jurnal atau penulis..."/>
                        <button type="submit" class="bg-primary text-on-primary px-6 py-2 rounded-lg text-sm font-bold hover:opacity-90 transition-opacity flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">search</span> Cari
                        </button>
                    </form>

                    <div class="space-y-4">
                        <?php if ($search_query !== '' && empty($search_results)): ?>
                            <div class="text-center py-8 text-on-surface-variant text-sm">
                                <span class="material-symbols-outlined text-[32px] mb-2 block opacity-70">search_off</span>
                                Tidak ada hasil untuk "<strong><?= htmlspecialchars($search_query) ?></strong>"
                            </div>
                        <?php elseif (!empty($search_results)): ?>
                            <?php foreach ($search_results as $ref): ?>
                            <div class="border border-outline-variant rounded-lg p-4 hover:bg-surface-container-low transition-colors flex flex-col md:flex-row justify-between md:items-center gap-4">
                                <div>
                                    <h5 class="text-base text-primary font-semibold mb-1">
                                        <?= htmlspecialchars($ref['judul'] ?? '-') ?>
                                    </h5>
                                    <p class="text-sm text-on-surface-variant mb-2">
                                        <?= htmlspecialchars($ref['penulis'] ?? '-') ?> — <?= htmlspecialchars($ref['sumber'] ?? '') ?>, <?= htmlspecialchars($ref['tahun'] ?? '') ?>
                                    </p>
                                </div>
                                <?php if (!empty($ref['url_pdf'])): ?>
                                <a href="<?= htmlspecialchars($ref['url_pdf']) ?>" target="_blank"
                                   class="shrink-0 bg-primary-container text-primary px-4 py-2 rounded-lg text-sm font-bold hover:bg-primary-fixed transition-colors flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[18px]">download</span> Download PDF
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Default state: belum search -->
                            <div class="border border-outline-variant rounded-lg p-4 hover:bg-surface-container-low transition-colors flex flex-col md:flex-row justify-between md:items-center gap-4">
                                <div>
                                    <h5 class="text-base text-primary font-semibold mb-1">Implementasi Machine Learning pada Sistem Akademik</h5>
                                    <p class="text-sm text-on-surface-variant mb-2">A. Rahman, B. Susanto — Jurnal Informatika PNUP, 2023</p>
                                    <span class="inline-block bg-surface-container text-on-surface-variant px-2 py-1 rounded text-xs font-bold">Cited by 12</span>
                                </div>
                                <button class="shrink-0 bg-primary-container text-primary px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[18px]">download</span> Download PDF
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- UPLOAD FOTO PROFIL -->
                <section class="bg-surface border border-outline-variant rounded-xl p-6 ambient-shadow-md">
                    <div class="flex items-center gap-3 mb-6">
                        <span class="material-symbols-outlined text-primary text-[24px]" style="font-variation-settings:'FILL' 1">account_circle</span>
                        <h3 class="text-lg font-semibold text-on-surface">Profil & Upload Foto</h3>
                    </div>
                    <div class="flex flex-col md:flex-row gap-8 items-start">
                        <div class="flex flex-col items-center gap-4">
                            <div class="w-24 h-24 rounded-full bg-surface-container-highest flex items-center justify-center overflow-hidden border-4 border-surface shadow-sm" id="preview-wrapper">
                                <img alt="Current profile" class="w-full h-full object-cover" id="preview-img"
                                     src="https://ui-avatars.com/api/?name=Admin+TKJ&background=00236f&color=fff"/>
                            </div>
                            <div class="text-center">
                                <h4 class="text-base font-bold text-on-background">Dr. Administrator</h4>
                                <p class="text-sm text-on-surface-variant">admin.tkj@pnup.ac.id</p>
                            </div>
                        </div>
                        <div class="flex-1 w-full">
                            <div class="bg-surface-container p-4 rounded-lg mb-4 text-sm text-on-surface-variant border border-outline-variant border-dashed">
                                Format yang didukung: <strong>JPG, PNG</strong> (Maks. 2MB). Foto akan dikonversi ke Base64 di backend.
                            </div>
                            <form action="" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row items-center gap-4">
                                <input name="fileUpload" id="fileUpload" type="file" accept="image/*"
                                       class="block w-full text-sm text-on-surface-variant file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-container file:text-primary hover:file:bg-primary-fixed transition-colors cursor-pointer border border-outline-variant rounded-lg bg-surface"/>
                                <button type="submit" class="shrink-0 bg-primary text-on-primary px-6 py-2 rounded-lg text-sm font-bold hover:opacity-90 transition-opacity w-full sm:w-auto">
                                    Upload Foto
                                </button>
                            </form>
                        </div>
                    </div>
                </section>

            </div>

            <!-- BERITA TERKINI -->
            <div class="xl:col-span-1">
                <section class="bg-surface border border-outline-variant rounded-xl p-6 ambient-shadow-md h-full">
                    <div class="flex items-center gap-3 mb-6 border-b border-outline-variant pb-4">
                        <span class="material-symbols-outlined text-secondary text-[24px]" style="font-variation-settings:'FILL' 1">rss_feed</span>
                        <h3 class="text-lg font-semibold text-on-surface">Berita Terkini</h3>
                    </div>
                    <div class="flex flex-col gap-6 relative">
                        <div class="absolute left-3 top-2 bottom-2 w-px bg-outline-variant/30 z-0"></div>

                        <?php
                        // Daftar berita — sambungkan ke scrapper di sini jika sudah ada
                        $berita = [
                            ["judul" => "Face Detection using Python", "desc" => "Hasil ekstraksi web berita teknologi terpusat.", "sumber" => "tutscode.net", "warna" => "primary-fixed", "dot" => "primary"],
                            ["judul" => "Data Mining & Web Scrapping", "desc" => "Panduan praktis pengumpulan data otomatis untuk riset.", "sumber" => "tutscode.net", "warna" => "secondary-container", "dot" => "secondary"],
                            ["judul" => "IoT & Smart System 2026", "desc" => "Tren pengembangan sistem cerdas berbasis sensor.", "sumber" => "tekno.id", "warna" => "tertiary-fixed", "dot" => "tertiary"],
                        ];
                        foreach ($berita as $item): ?>
                        <div class="relative z-10 flex gap-4">
                            <div class="w-6 h-6 rounded-full bg-<?= $item['warna'] ?> border-2 border-surface flex-shrink-0 mt-1 flex items-center justify-center">
                                <div class="w-2 h-2 rounded-full bg-<?= $item['dot'] ?>"></div>
                            </div>
                            <div>
                                <h5 class="text-sm text-on-background font-semibold hover:text-primary transition-colors">
                                    <?= htmlspecialchars($item['judul']) ?>
                                </h5>
                                <p class="text-xs text-on-surface-variant mt-1"><?= htmlspecialchars($item['desc']) ?></p>
                                <span class="text-xs text-outline mt-2 block">Source: <?= htmlspecialchars($item['sumber']) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>
    </main>
</div>

<!-- FOOTER -->
<footer class="bg-surface-container-high border-t border-outline-variant w-full py-6 px-8 flex flex-col md:flex-row justify-between items-center gap-4 mt-auto">
    <p class="text-on-surface-variant text-sm">© 2026 Teknik Komputer dan Jaringan PNUP. Academic Data Integration.</p>
    <p class="text-xs text-on-surface-variant opacity-80">Powered by PHP + MySQL + Chart.js</p>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // ====== DATA DARI PHP ======
    const dataIPK  = <?= json_encode(array_values($ipkData ?: [])) ?>;
    const dataPred = <?= json_encode(array_values($predikatData ?: [])) ?>;

    // ====== FALLBACK ======
    const lineLabels = dataIPK.length > 0
        ? dataIPK.map(i => i.label || ('SK ' + i.sk_waktu))
        : ['Smt 1', 'Smt 2', 'Smt 3', 'Smt 4'];
    const lineData = dataIPK.length > 0
        ? dataIPK.map(i => parseFloat(i.ipk))
        : [3.10, 3.25, 3.32, 3.40];

    const pieLabels = dataPred.length > 0
        ? dataPred.map(i => i.predikat)
        : ['Cum Laude', 'Sangat Memuaskan', 'Memuaskan'];
    const pieData = dataPred.length > 0
        ? dataPred.map(i => parseInt(i.jumlah))
        : [142, 60, 12];

    const COLORS = ['#2563eb', '#0ea5e9', '#4f46e5', '#38bdf8', '#818cf8', '#7dd3fc'];

    // ====== LINE CHART ======
    new Chart(document.getElementById('lineChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: lineLabels,
            datasets: [{
                label: 'Rata-rata IPK',
                data: lineData,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,0.08)',
                borderWidth: 2.5,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#2563eb',
                pointRadius: 5,
                pointHoverRadius: 7,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { min: 2.8, max: 4.0, grid: { color: 'rgba(203,213,225,0.4)' }, ticks: { font: { family: 'Inter', size: 11 } } },
                x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 11 } } }
            },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ' IPK: ' + ctx.parsed.y.toFixed(2) } }
            }
        }
    });

    // ====== DOUGHNUT CHART ======
    new Chart(document.getElementById('doughnutChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieData,
                backgroundColor: COLORS.slice(0, pieLabels.length),
                borderWidth: 0,
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 16, usePointStyle: true, font: { family: 'Inter', size: 11 } }
                }
            }
        }
    });

    // ====== PREVIEW FOTO SEBELUM UPLOAD ======
    document.getElementById('fileUpload')?.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = ev => document.getElementById('preview-img').src = ev.target.result;
            reader.readAsDataURL(file);
        }
    });
});
</script>
</body>
</html>