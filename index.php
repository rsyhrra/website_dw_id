<?php
// File: index.php
session_start();

// Redireksi jika belum login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

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
    <!-- Use Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "background": "#f3f4f9", // Sleek light background
                    "surface": "#ffffff",    // Pure white cards
                    "primary": "#6366f1",    // Vibrant indigo/purple
                    "primary-light": "#818cf8",
                    "primary-dark": "#4f46e5",
                    "accent-pink": "#ec4899",
                    "accent-blue": "#3b82f6",
                    "text-main": "#1e293b",  // Slate-800
                    "text-muted": "#64748b", // Slate-500
                    "border-light": "#f1f5f9", // Soft light borders
                },
                fontFamily: {
                    sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
                },
                boxShadow: {
                    'premium': '0 8px 30px rgba(0, 0, 0, 0.02)',
                    'premium-sm': '0 4px 15px rgba(0, 0, 0, 0.01)',
                    'purple-glow': '0 10px 25px -5px rgba(99, 102, 241, 0.25)',
                    'card-shadow': '0 20px 25px -5px rgba(0, 0, 0, 0.03), 0 10px 10px -5px rgba(0, 0, 0, 0.01)',
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
        body { 
            font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; 
            background-color: #f3f4f9; 
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        /* Custom scrollbars */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        /* Preloader */
        #preloader {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #f3f4f9; z-index: 99999;
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
<body class="bg-background text-text-main min-h-screen flex">
<!-- Preloader -->
<div id="preloader">
    <div class="spinner"></div>
    <p class="mt-4 text-sm font-bold text-slate-500">Memuat Data...</p>
</div>

<!-- ====== LAYOUT WRAPPER ====== -->
<div class="flex flex-1 w-full max-w-[1600px] mx-auto relative min-h-screen">

    <!-- ====== SIDEBAR ====== -->
    <!-- Matches reference image: minimalist white panel on the left with rounded logo and neat active/inactive states -->
    <aside class="w-24 bg-white border-r border-slate-100 flex flex-col items-center py-8 fixed left-0 top-0 h-screen z-[1000] justify-between hidden md:flex">
        <!-- Logo at Top -->
        <div class="flex flex-col items-center gap-10">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-pink-500 via-purple-500 to-indigo-500 flex items-center justify-center shadow-lg relative group cursor-pointer">
                <span class="material-symbols-outlined text-white text-2xl font-bold">school</span>
                <div class="absolute -right-3 top-1/2 -translate-y-1/2 w-1 h-6 bg-primary rounded-l-full hidden group-hover:block"></div>
            </div>
            
            <!-- Menu Navigation -->
            <nav class="flex flex-col gap-6 items-center w-full">
                <a class="w-12 h-12 rounded-xl flex items-center justify-center text-primary bg-indigo-50/80 shadow-premium-sm relative transition-all group shrink-0" href="index.php" title="Dashboard">
                    <span class="material-symbols-outlined text-[22px]">grid_view</span>
                    <!-- Active marker vertical bar -->
                    <div class="absolute left-[-24px] top-1/2 -translate-y-1/2 w-1.5 h-8 bg-primary rounded-r-full"></div>
                </a>
                <a class="w-12 h-12 rounded-xl flex items-center justify-center text-slate-400 hover:text-primary hover:bg-slate-50 transition-all relative group shrink-0" href="akademik.php" title="Data Mahasiswa">
                    <span class="material-symbols-outlined text-[22px]">group</span>
                </a>
            </nav>
        </div>

        <!-- Bottom Icons -->
        <div class="flex flex-col gap-6 w-full px-4 items-center">
            <button onclick="openSettingsModal()" class="w-12 h-12 rounded-xl flex items-center justify-center text-slate-400 hover:text-primary hover:bg-slate-50 transition-all focus:outline-none" title="Pengaturan">
                <span class="material-symbols-outlined text-[22px]">settings</span>
            </button>
            <a class="w-12 h-12 rounded-xl flex items-center justify-center text-red-400 hover:text-red-600 hover:bg-red-50 transition-all" href="logout.php" title="Logout">
                <span class="material-symbols-outlined text-[22px]">logout</span>
            </a>
        </div>
    </aside>

    <!-- ====== MAIN CONTENT AREA ====== -->
    <!-- Displace by sidebar width on desktop -->
    <main class="flex-1 md:pl-[136px] p-6 md:p-10 w-full min-h-screen flex flex-col gap-8">

        <!-- ====== HEADER / TOP BAR ====== -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 w-full">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Overview</h1>
                <p class="text-sm font-semibold text-text-muted mt-1 flex items-center gap-1.5">Welcome back, Admin! <span class="text-xs font-medium bg-indigo-50 text-primary px-2 py-0.5 rounded-full">TKJ PNUP</span></p>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Search input container matching reference design -->
                <form action="" method="GET" class="relative flex items-center">
                    <input name="q" value="<?= htmlspecialchars($search_query) ?>"
                           type="text" 
                           placeholder="Search references..."
                           class="w-64 md:w-80 bg-white border-0 rounded-full px-5 py-2.5 pl-11 text-sm shadow-premium focus:ring-2 focus:ring-primary focus:bg-white text-slate-700 outline-none transition-all placeholder:text-slate-400"/>
                    <span class="material-symbols-outlined text-slate-400 absolute left-4 text-[20px]">search</span>
                    <?php if($search_query): ?>
                        <a href="index.php" class="absolute right-4 text-xs text-slate-400 hover:text-slate-600">Clear</a>
                    <?php endif; ?>
                </form>

                <!-- Notifications -->
                <button class="w-11 h-11 rounded-full bg-white flex items-center justify-center shadow-premium text-slate-500 hover:text-primary transition-colors relative">
                    <span class="material-symbols-outlined text-[22px]">notifications</span>
                    <span class="absolute top-3 right-3 w-2.5 h-2.5 bg-accent-pink rounded-full border-2 border-white"></span>
                </button>

                <!-- Admin Avatar with purple accent border -->
                <div class="relative flex items-center">
                    <button onclick="toggleProfileDropdown(event)" class="w-11 h-11 rounded-full p-0.5 bg-gradient-to-tr from-pink-500 to-indigo-500 shadow-premium hover:scale-105 transition-transform outline-none focus:outline-none">
                        <img alt="Profile" class="w-full h-full rounded-full object-cover border border-white"
                             src="https://ui-avatars.com/api/?name=Admin+TKJ&background=ffffff&color=6366f1"/>
                    </button>
                    
                    <!-- Profile Dropdown Box -->
                    <div id="profileDropdown" class="hidden absolute right-0 top-14 w-64 glass-card rounded-2xl shadow-card-shadow p-5 border border-slate-100 flex flex-col gap-4 z-[9999]">
                        <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                            <div class="w-10 h-10 rounded-full p-0.5 bg-gradient-to-tr from-pink-500 to-indigo-500">
                                <img alt="Profile" class="w-full h-full rounded-full object-cover border border-white" src="https://ui-avatars.com/api/?name=Admin+TKJ&background=ffffff&color=6366f1"/>
                            </div>
                            <div class="text-left">
                                <h4 class="text-xs font-bold text-slate-800">Admin TKJ</h4>
                                <span class="text-[9px] font-bold text-text-muted bg-slate-50 px-2 py-0.5 rounded-full mt-0.5 inline-block">Administrator</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-2">
                            <a href="akademik.php" class="flex items-center gap-2.5 py-2 px-3 rounded-xl hover:bg-slate-50 text-xs font-semibold text-slate-600 hover:text-primary transition-all">
                                <span class="material-symbols-outlined text-[18px]">group</span>
                                Data Mahasiswa
                            </a>
                            <button onclick="openSettingsModal()" class="flex items-center gap-2.5 py-2 px-3 rounded-xl hover:bg-slate-50 text-xs font-semibold text-slate-600 hover:text-primary w-full text-left transition-all">
                                <span class="material-symbols-outlined text-[18px]">settings</span>
                                Pengaturan
                            </button>
                        </div>
                        <a href="logout.php" class="flex items-center gap-2.5 py-2.5 px-3 rounded-xl bg-red-50 hover:bg-rose-100 text-xs font-bold text-rose-600 transition-all justify-center">
                            <span class="material-symbols-outlined text-[16px]">logout</span> Keluar
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ====== DASHBOARD GRID ====== -->
        <div class="flex flex-col lg:grid lg:grid-cols-12 gap-8 items-start w-full">

            <!-- LEFT COLUMN (Span 4) - Houses primary gradient card, recent activity list, and actions -->
            <div class="lg:col-span-4 flex flex-col gap-8 w-full order-2 lg:order-1">
                
                <!-- GRADIENT PRIMARY STAT CARD (Current Balance adaptation) -->
                <div class="w-full bg-gradient-to-br from-pink-500 via-purple-500 to-indigo-600 rounded-[2rem] p-8 text-white shadow-purple-glow relative overflow-hidden aspect-[1.58/1] max-md:aspect-auto max-md:py-8 flex flex-col justify-between group cursor-pointer transition-transform hover:scale-[1.01]">
                    <!-- Abstract geometric background overlay -->
                    <div class="absolute inset-0 opacity-10 bg-[radial-gradient(circle_at_30%_20%,rgba(255,255,255,1),transparent_60%)]"></div>
                    <div class="absolute -right-16 -top-16 w-44 h-44 rounded-full bg-white/10 blur-xl"></div>
                    
                    <div class="flex justify-between items-start z-10">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-white/70">Rata-Rata IPK</p>
                            <h2 class="text-4xl font-extrabold mt-1.5 tracking-tight"><?= htmlspecialchars($avg_ipk) ?></h2>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center border border-white/20">
                            <span class="material-symbols-outlined text-white text-[28px]">monitoring</span>
                        </div>
                    </div>
                    
                    <div class="z-10 mt-auto">
                        <p class="text-xs font-medium tracking-widest text-white/60 mb-1">PROGRAM STUDI</p>
                        <div class="flex justify-between items-end">
                            <div>
                                <h3 class="text-sm font-bold tracking-wide">Teknik Komputer & Jaringan</h3>
                                <p class="text-[10px] text-white/70 mt-0.5">Politeknik Negeri Ujung Pandang</p>
                            </div>
                            <div class="flex flex-col items-end">
                                <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">PNUP</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TRANSACTION-STYLE LIST (Aktivitas Akademik / Berita Terkini) -->
                <!-- Styled exactly like the Transactions list in reference image -->
                <div class="bg-white rounded-[2rem] p-8 shadow-premium flex flex-col gap-6">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-bold text-slate-800">Berita Terkini</h3>
                        <a href="#" class="w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 hover:text-primary transition-colors">
                            <span class="material-symbols-outlined text-[20px]">sort</span>
                        </a>
                    </div>

                    <div class="flex flex-col gap-5">
                        <?php
                        $berita = [
                            ["judul" => "Face Detection using Python", "desc" => "Hasil ekstraksi web berita teknologi.", "sumber" => "tutscode.net", "icon" => "north_east", "bg" => "bg-pink-50", "color" => "text-pink-500"],
                            ["judul" => "Data Mining & Web Scrapping", "desc" => "Panduan praktis pengumpulan data otomatis.", "sumber" => "tutscode.net", "icon" => "north_east", "bg" => "bg-purple-50", "color" => "text-purple-500"],
                            ["judul" => "IoT & Smart System 2026", "desc" => "Tren pengembangan sistem cerdas berbasis sensor.", "sumber" => "tekno.id", "icon" => "check", "bg" => "bg-indigo-50", "color" => "text-indigo-500"],
                        ];
                        foreach ($berita as $item): ?>
                        <div class="flex items-center justify-between group cursor-pointer">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl <?= $item['bg'] ?> flex items-center justify-center transition-transform group-hover:scale-105">
                                    <span class="material-symbols-outlined <?= $item['color'] ?> text-[20px] font-bold"><?= $item['icon'] ?></span>
                                </div>
                                <div class="max-w-[170px] md:max-w-[200px]">
                                    <h4 class="text-sm font-bold text-slate-800 truncate group-hover:text-primary transition-colors"><?= htmlspecialchars($item['judul']) ?></h4>
                                    <p class="text-xs text-text-muted truncate mt-0.5"><?= htmlspecialchars($item['desc']) ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold text-slate-600 block"><?= htmlspecialchars($item['sumber']) ?></span>
                                <span class="text-[10px] text-text-muted">News</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- QUICK ACTIONS / PROFILE PHOTO UPLOAD (Quick Transfer adaptation) -->
                <div class="bg-white rounded-[2rem] p-8 shadow-premium flex flex-col gap-6">
                    <h3 class="text-lg font-bold text-slate-800">Quick Profile Upload</h3>
                    
                    <div class="flex flex-col gap-6 items-center">
                        <div class="flex items-center justify-center gap-2">
                            <!-- Circular avatar list like the Quick Transfer avatars -->
                            <div class="flex -space-x-3 overflow-hidden">
                                <img class="inline-block h-9 w-9 rounded-full ring-2 ring-white" src="https://images.unsplash.com/photo-1491528323818-fdd1faba62cc?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt=""/>
                                <img class="inline-block h-9 w-9 rounded-full ring-2 ring-white" src="https://images.unsplash.com/photo-1550525811-e5869dd03032?ixlib=rb-1.2.1&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt=""/>
                                <img class="inline-block h-9 w-9 rounded-full ring-2 ring-white" src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2.25&w=256&h=256&q=80" alt=""/>
                                <img class="inline-block h-9 w-9 rounded-full ring-2 ring-white" src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt=""/>
                            </div>
                            <button class="w-9 h-9 rounded-full border-2 border-dashed border-slate-300 flex items-center justify-center text-slate-400 hover:border-primary hover:text-primary transition-all">
                                <span class="material-symbols-outlined text-[18px]">add</span>
                            </button>
                        </div>

                        <!-- Elegant File Input Panel -->
                        <form action="" method="POST" enctype="multipart/form-data" class="w-full flex flex-col gap-4">
                            <div class="relative w-full">
                                <input name="fileUpload" id="fileUpload" type="file" accept="image/*"
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"/>
                                <div class="w-full py-4 px-5 border border-dashed border-slate-200 hover:border-primary/50 rounded-2xl flex items-center gap-3 bg-slate-50 transition-colors">
                                    <span class="material-symbols-outlined text-slate-400 text-[20px]">cloud_upload</span>
                                    <span class="text-xs font-semibold text-slate-500 truncate" id="file-label-text">Select new profile image...</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3 w-full">
                                <button type="button" class="py-3 px-4 rounded-2xl text-xs font-bold border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                                    Save Draft
                                </button>
                                <button type="submit" class="py-3 px-4 rounded-2xl text-xs font-bold bg-primary hover:bg-primary-dark text-white shadow-purple-glow transition-all">
                                    Upload Photo
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN (Span 8) - Wide Banner, Statistics Grid, and Charts -->
            <div class="lg:col-span-8 flex flex-col gap-8 w-full order-1 lg:order-2">
                
                <!-- WIDE PROMOTIONAL BANNER CARD (Cashback adaptation) -->
                <!-- Vibrant, high-contrast, beautiful gradient circles and character card details -->
                <div class="w-full bg-white rounded-[2rem] p-8 md:p-10 shadow-premium flex flex-col md:flex-row items-center justify-between gap-6 relative overflow-hidden border border-slate-100/50">
                    <!-- Elegant light purple & pink gradient blobs in the background -->
                    <div class="absolute -right-10 top-0 w-48 h-48 rounded-full bg-pink-100/40 blur-3xl"></div>
                    <div class="absolute right-32 bottom-0 w-44 h-44 rounded-full bg-indigo-100/40 blur-3xl"></div>
                    
                    <div class="flex-1 flex flex-col items-start gap-4 z-10">
                        <div class="flex items-center gap-2 bg-indigo-50 text-primary px-3 py-1.5 rounded-full">
                            <span class="material-symbols-outlined text-[16px]">verified</span>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider">Integrasi Data Akademik</span>
                        </div>
                        <h2 class="text-2xl md:text-3xl font-extrabold text-slate-800 leading-tight">
                            Platform Integrasi Portal TKJ PNUP
                        </h2>
                        <p class="text-xs md:text-sm text-text-muted font-medium max-w-md leading-relaxed">
                            Kelola data mahasiswa aktif, tren kelulusan cum laude, dan referensi akademik secara real-time dari API Warehouse terpusat.
                        </p>
                        <a href="akademik.php" class="bg-primary hover:bg-primary-dark text-white font-bold text-xs py-3 px-6 rounded-2xl shadow-purple-glow transition-all mt-2">
                            Mulai Kelola Data
                        </a>
                    </div>
                    
                    <!-- Right side illustration - Beautiful floating spheres and profile graphic -->
                    <div class="relative w-44 h-40 flex items-center justify-center shrink-0">
                        <!-- Floating Glassmorphic Spheres -->
                        <div class="absolute -left-4 top-2 w-10 h-10 rounded-full bg-gradient-to-tr from-pink-400 to-rose-300 shadow-md animate-bounce" style="animation-duration: 4s;"></div>
                        <div class="absolute right-0 bottom-4 w-12 h-12 rounded-full bg-gradient-to-br from-indigo-400 to-purple-300 shadow-md animate-bounce" style="animation-duration: 6s;"></div>
                        <!-- Main Graphic Badge -->
                        <div class="w-32 h-32 rounded-[2rem] bg-gradient-to-tr from-purple-100 to-indigo-50 flex items-center justify-center border-4 border-white shadow-lg relative z-10">
                            <span class="material-symbols-outlined text-primary text-[52px]" style="font-variation-settings: 'FILL' 1">school</span>
                        </div>
                    </div>
                </div>

                <!-- TRANSFER & CONVERSION STATS PANEL -->
                <!-- Two equal columns structured like the reference panels -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    <!-- Card 1: Quick Search Referensi Jurnal (Transfer adaptation) -->
                    <div class="bg-white rounded-[2rem] p-8 shadow-premium flex flex-col gap-5 border border-slate-100/50">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-xl bg-indigo-50 flex items-center justify-center text-primary">
                                    <span class="material-symbols-outlined text-[18px]">find_in_page</span>
                                </div>
                                <h3 class="text-sm font-bold text-slate-800">Quick Reference Search</h3>
                            </div>
                            <span class="material-symbols-outlined text-slate-300 text-[20px]">more_horiz</span>
                        </div>

                        <!-- Beautiful Input/Search field styling -->
                        <form action="" method="GET" class="flex flex-col gap-4">
                            <div class="relative w-full">
                                <input name="q" value="<?= htmlspecialchars($search_query) ?>"
                                       type="text" 
                                       placeholder="Enter research keywords..."
                                       class="w-full bg-slate-50 border-slate-100 rounded-2xl py-3 px-4 text-xs font-semibold focus:ring-primary focus:bg-white text-slate-700 outline-none transition-all placeholder:text-slate-400"/>
                                <span class="material-symbols-outlined text-slate-400 absolute right-4 top-3 text-[18px]">manage_search</span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <p class="text-[10px] text-text-muted font-semibold">Enter key author or paper titles</p>
                                <button type="submit" class="w-10 h-10 rounded-full bg-primary hover:bg-primary-dark text-white flex items-center justify-center shadow-premium transition-all">
                                    <span class="material-symbols-outlined text-[18px] font-bold">arrow_forward</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Card 2: Quick Overview (Conversion adaptation) -->
                    <div class="bg-white rounded-[2rem] p-8 shadow-premium flex flex-col gap-5 border border-slate-100/50">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-xl bg-pink-50 flex items-center justify-center text-pink-500">
                                    <span class="material-symbols-outlined text-[18px]">insights</span>
                                </div>
                                <h3 class="text-sm font-bold text-slate-800">Kelulusan & Alumni</h3>
                            </div>
                            <span class="material-symbols-outlined text-slate-300 text-[20px]">more_horiz</span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <!-- Box 1 -->
                            <div class="bg-slate-50/70 p-4 rounded-2xl flex flex-col gap-1">
                                <span class="text-[10px] text-text-muted font-bold uppercase tracking-wider">Cum Laude</span>
                                <span class="text-xl font-extrabold text-slate-800"><?= htmlspecialchars($cumlaude) ?></span>
                                <span class="text-[9px] font-medium text-pink-500 mt-1">IPK &ge; 3.51</span>
                            </div>
                            <!-- Box 2 -->
                            <div class="bg-slate-50/70 p-4 rounded-2xl flex flex-col gap-1">
                                <span class="text-[10px] text-text-muted font-bold uppercase tracking-wider">Total Mahasiswa</span>
                                <span class="text-xl font-extrabold text-slate-800"><?= htmlspecialchars($total_mhs) ?></span>
                                <span class="text-[9px] font-medium text-primary mt-1">Aktif & Alumni</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- REFERENCE SEARCH RESULTS / DYNAMIC SECTION -->
                <?php if($search_query !== ''): ?>
                <div class="bg-white rounded-[2rem] p-8 shadow-premium flex flex-col gap-6">
                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">search</span>
                        Search Results for "<strong><?= htmlspecialchars($search_query) ?></strong>"
                    </h3>
                    <div class="flex flex-col gap-4">
                        <?php if (empty($search_results)): ?>
                            <div class="text-center py-8 text-text-muted text-sm bg-slate-55 rounded-2xl">
                                <span class="material-symbols-outlined text-[36px] mb-2 block opacity-70">search_off</span>
                                No dynamic reference records found.
                            </div>
                        <?php else: foreach ($search_results as $ref): ?>
                            <div class="border border-slate-100 rounded-2xl p-5 hover:bg-slate-50/50 transition-colors flex flex-col md:flex-row justify-between md:items-center gap-4">
                                <div>
                                    <h5 class="text-sm font-bold text-slate-800 mb-1">
                                        <?= htmlspecialchars($ref['judul'] ?? '-') ?>
                                    </h5>
                                    <p class="text-xs text-text-muted">
                                        <?= htmlspecialchars($ref['penulis'] ?? '-') ?> &mdash; <?= htmlspecialchars($ref['sumber'] ?? '') ?>, <?= htmlspecialchars($ref['tahun'] ?? '') ?>
                                    </p>
                                </div>
                                <?php if (!empty($ref['url_pdf'])): ?>
                                <a href="<?= htmlspecialchars($ref['url_pdf']) ?>" target="_blank"
                                   class="shrink-0 bg-indigo-50 hover:bg-indigo-100 text-primary px-4 py-2.5 rounded-xl text-xs font-bold transition-colors flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[16px]">download</span> PDF
                                </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- DUAL CHART CANVAS SECTION (My Activity adaptation) -->
                <!-- Features line chart and doughnut chart side-by-side or stacked cleanly -->
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                    <!-- Line Chart Card -->
                    <div class="bg-white rounded-[2rem] p-8 shadow-premium flex flex-col gap-6">
                        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-800">Tren IPK Rata-Rata</h3>
                                <p class="text-[10px] text-text-muted font-medium mt-0.5">Per semester, mahasiswa aktif</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <select id="trendAngkatan" onchange="updateTrendChart()" class="border-0 bg-slate-50 text-slate-600 rounded-xl pl-3 pr-8 py-1.5 text-[11px] font-bold focus:ring-primary focus:bg-white cursor-pointer hover:bg-slate-100 transition-colors">
                                    <option value="">Semua Angkatan</option>
                                    <?php foreach(($summary['angkatan_list'] ?? []) as $a): ?>
                                        <option value="<?= $a ?>"><?= htmlspecialchars($a) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select id="trendKelas" onchange="updateTrendChart()" class="border-0 bg-slate-50 text-slate-600 rounded-xl pl-3 pr-8 py-1.5 text-[11px] font-bold focus:ring-primary focus:bg-white cursor-pointer hover:bg-slate-100 transition-colors">
                                    <option value="">Semua Kelas</option>
                                    <?php foreach(($summary['kelas_list'] ?? []) as $k): ?>
                                        <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($k) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="relative h-64 w-full">
                            <canvas id="lineChart"></canvas>
                        </div>
                    </div>

                    <!-- Doughnut Chart Card -->
                    <div class="bg-white rounded-[2rem] p-8 shadow-premium flex flex-col gap-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-base font-bold text-slate-800">Distribusi Predikat</h3>
                                <p class="text-[10px] text-text-muted font-medium mt-0.5">Dari seluruh data kelulusan alumni</p>
                            </div>
                            <span class="material-symbols-outlined text-slate-300 text-[20px]">pie_chart</span>
                        </div>
                        <div class="relative h-64 w-full flex justify-center items-center">
                            <canvas id="doughnutChart"></canvas>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- ====== FOOTER ====== -->
        <footer class="mt-auto py-8 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs font-semibold text-text-muted">
            <p>&copy; 2026 Teknik Komputer dan Jaringan PNUP. Academic Data Integration.</p>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>System online</span>
            </div>
        </footer>

    </main>

</div>

<!-- ====== MOBILE BOTTOM NAV ====== -->
<nav class="md:hidden fixed bottom-6 left-6 right-6 h-16 bg-white/95 backdrop-blur-lg rounded-2xl shadow-card-shadow border border-slate-100/50 flex items-center justify-around px-4 z-[9999]">
    <a href="index.php" class="flex flex-col items-center justify-center text-primary px-3 py-1.5 rounded-xl bg-indigo-50/80">
        <span class="material-symbols-outlined text-[22px]" style="font-variation-settings: 'FILL' 1">grid_view</span>
        <span class="text-[9px] font-bold mt-0.5">Dashboard</span>
    </a>
    <a href="akademik.php" class="flex flex-col items-center justify-center text-slate-400 hover:text-primary">
        <span class="material-symbols-outlined text-[22px]">group</span>
        <span class="text-[9px] font-bold mt-0.5">Akademik</span>
    </a>
    <button onclick="openSettingsModal()" class="flex flex-col items-center justify-center text-slate-400 hover:text-primary focus:outline-none">
        <span class="material-symbols-outlined text-[22px]">settings</span>
        <span class="text-[9px] font-bold mt-0.5">Settings</span>
    </button>
    <a href="logout.php" class="flex flex-col items-center justify-center text-red-400 hover:text-red-600">
        <span class="material-symbols-outlined text-[22px]">logout</span>
        <span class="text-[9px] font-bold mt-0.5">Keluar</span>
    </a>
</nav>

<!-- ====== SETTINGS MODAL ====== -->
<div id="settingsModal" class="hidden fixed inset-0 z-[10000] items-center justify-center p-4">
    <!-- Backdrop with blur -->
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeSettingsModal()"></div>
    
    <!-- Modal Content -->
    <div class="glass-modal glass-card rounded-[2.5rem] p-8 md:p-10 shadow-card-shadow max-w-md w-full relative z-10 scale-95 transition-all duration-300">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h3 class="text-xl font-extrabold text-slate-800">Pengaturan Sistem</h3>
                <p class="text-xs font-bold text-text-muted mt-1">Konfigurasi & informasi portal akademik</p>
            </div>
            <button onclick="closeSettingsModal()" class="w-8 h-8 rounded-full bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition-colors">
                <span class="material-symbols-outlined text-[18px]">close</span>
            </button>
        </div>
        
        <div class="flex flex-col gap-6">
            <!-- Section 1: API Config -->
            <div class="bg-slate-50/50 p-4 rounded-2xl border border-slate-100 flex flex-col gap-3">
                <span class="text-[10px] text-text-muted font-bold uppercase tracking-wider">Koneksi API Warehouse</span>
                <div class="flex flex-col gap-1">
                    <span class="text-xs font-semibold text-slate-500">API Endpoint</span>
                    <span class="text-xs font-bold text-slate-800 break-all select-all bg-white px-3 py-2 rounded-xl border border-slate-100 mt-1"><?= API_BASE ?></span>
                </div>
            </div>

            <!-- Section 2: Account Details -->
            <div class="flex flex-col gap-3">
                <span class="text-[10px] text-text-muted font-bold uppercase tracking-wider">Informasi Akun</span>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-xs font-semibold text-slate-500">Role Pengguna</span>
                    <span class="text-xs font-bold text-primary">Administrator</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-xs font-semibold text-slate-500">Username</span>
                    <span class="text-xs font-bold text-slate-800">admin</span>
                </div>
            </div>

            <!-- Section 3: Customization -->
            <div class="flex flex-col gap-3">
                <span class="text-[10px] text-text-muted font-bold uppercase tracking-wider">Preferensi Tampilan</span>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-semibold text-slate-500">Tema Gelap (Beta)</span>
                    <button class="w-10 h-6 rounded-full bg-slate-200 p-0.5 flex items-center transition-colors focus:outline-none" id="darkModeToggle" onclick="toggleDarkMode()">
                        <div class="w-5 h-5 rounded-full bg-white shadow-md transform translate-x-0 transition-transform" id="darkModeKnob"></div>
                    </button>
                </div>
            </div>
        </div>
        
        <button onclick="closeSettingsModal()" class="w-full bg-primary hover:bg-primary-dark text-white rounded-2xl py-3.5 mt-8 font-bold text-xs shadow-purple-glow transition-all">
            Simpan & Selesai
        </button>
    </div>
</div>

    </main>

</div>

<!-- ====== PROFILE PREVIEW & SCRIPT ====== -->
<script>
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

let isDarkMode = false;
function toggleDarkMode() {
    isDarkMode = !isDarkMode;
    const knob = document.getElementById('darkModeKnob');
    const toggle = document.getElementById('darkModeToggle');
    if (isDarkMode) {
        knob.classList.replace('translate-x-0', 'translate-x-4');
        toggle.classList.replace('bg-slate-200', 'bg-primary');
        document.documentElement.classList.add('dark');
    } else {
        knob.classList.replace('translate-x-4', 'translate-x-0');
        toggle.classList.replace('bg-primary', 'bg-slate-200');
        document.documentElement.classList.remove('dark');
    }
}

// Close dropdown on outside click
document.addEventListener('click', function() {
    const dropdown = document.getElementById('profileDropdown');
    if (dropdown) dropdown.classList.add('hidden');
});

// ====== PRELOADER ======
window.addEventListener('load', () => {
    setTimeout(() => {
        const pre = document.getElementById('preloader');
        if(pre) {
            pre.style.opacity = '0';
            setTimeout(() => pre.style.display = 'none', 500);
        }
    }, 500);
});

document.addEventListener('DOMContentLoaded', function () {
    // Dynamic File Input label change
    const fileUploadInput = document.getElementById('fileUpload');
    const fileLabelText = document.getElementById('file-label-text');
    if (fileUploadInput && fileLabelText) {
        fileUploadInput.addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : "Select new profile image...";
            fileLabelText.textContent = fileName;
        });
    }

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

    const COLORS = ['#6366f1', '#ec4899', '#3b82f6', '#818cf8', '#f43f5e', '#10b981'];

    // ====== LINE CHART ======
    window.trendChartInstance = new Chart(document.getElementById('lineChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: lineLabels,
            datasets: [{
                label: 'Rata-rata IPK',
                data: lineData,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.04)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#6366f1',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { 
                    min: 2.8, 
                    max: 4.0, 
                    grid: { color: '#f1f5f9' }, 
                    border: { display: false },
                    ticks: { font: { family: 'Plus Jakarta Sans', size: 10, weight: 'bold' }, color: '#64748b' } 
                },
                x: { 
                    grid: { display: false }, 
                    border: { display: false },
                    ticks: { font: { family: 'Plus Jakarta Sans', size: 10, weight: 'bold' }, color: '#64748b' } 
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: { 
                    backgroundColor: '#1e293b',
                    titleFont: { family: 'Plus Jakarta Sans', weight: 'bold' },
                    bodyFont: { family: 'Plus Jakarta Sans' },
                    callbacks: { label: ctx => ' IPK: ' + ctx.parsed.y.toFixed(2) } 
                }
            }
        }
    });

    // ====== DYNAMIC TREND FILTER FUNCTION ======
    window.updateTrendChart = function() {
        const angkatan = document.getElementById('trendAngkatan').value;
        const kelas = document.getElementById('trendKelas').value;
        
        fetch('api_dw_tkj.php?type=chart_ipk&angkatan=' + angkatan + '&kelas=' + encodeURIComponent(kelas), {
            headers: {
                'key': '<?= API_KEY ?>'
            }
        })
        .then(res => res.json())
        .then(data => {
            const results = data.results || [];
            
            const newLabels = results.map(r => r.label);
            const newData = results.map(r => parseFloat(r.ipk));
            
            window.trendChartInstance.data.labels = newLabels;
            window.trendChartInstance.data.datasets[0].data = newData;
            window.trendChartInstance.update();
        })
        .catch(err => console.error('Error fetching trend data:', err));
    };

    // ====== DOUGHNUT CHART ======
    new Chart(document.getElementById('doughnutChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieData,
                backgroundColor: COLORS.slice(0, pieLabels.length),
                borderWidth: 4,
                borderColor: '#ffffff',
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { 
                        padding: 16, 
                        usePointStyle: true, 
                        pointStyle: 'circle',
                        font: { family: 'Plus Jakarta Sans', size: 10, weight: 'bold' },
                        color: '#64748b'
                    }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { family: 'Plus Jakarta Sans', weight: 'bold' },
                    bodyFont: { family: 'Plus Jakarta Sans' }
                }
            }
        }
    });
});
</script>
</body>
</html>
