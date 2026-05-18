<?php
session_start();

// Redireksi jika belum login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
$res_api = callAPI(API_BASE . "?type=students");
// Pastikan $students selalu array agar tidak error di foreach
$students = is_array($res_api) ? $res_api : [];

$students_by_class = [];
if (!empty($students)) {
    foreach ($students as $mhs) {
        $kelas = empty($mhs['kelas']) ? 'Tidak Ada Kelas' : $mhs['kelas'];
        $status = strtolower($mhs['status_akademik'] ?? '');
        
        // Ganti nama kelas menjadi Tahun Lulus jika ia alumni
        if ($status === 'lulus' || $status === 'alumni' || strtolower($kelas) === 'alumni') {
            $tahun = ($mhs['tahun_lulus'] ?? '-') !== '-' ? $mhs['tahun_lulus'] : '';
            $kelas = 'Alumni' . ($tahun ? " (Lulusan $tahun)" : '');
        }
        
        $students_by_class[$kelas][] = $mhs;
    }
    
    // Urutkan key kelas (A-Z)
    ksort($students_by_class);
    
    // Urutkan tiap kelas berdasarkan NIM ASC
    foreach ($students_by_class as $k => &$mhs_list) {
        usort($mhs_list, function($a, $b) {
            return strcmp($a['nim'] ?? '', $b['nim'] ?? '');
        });
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Data Akademik – TKJ PNUP</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
    tailwind.config = {
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
        .badge-active   { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
        .badge-alumni   { background: #e0e7ff; color: #4f46e5; border: 1px solid #c7d2fe; }
        .badge-inactive { background: #fdf2f8; color: #db2777; border: 1px solid #fbcfe8; }
        
        .ipk-bar { height: 6px; border-radius: 999px; background: #e2e8f0; overflow: hidden; }
        .ipk-fill { 
            height: 100%; 
            border-radius: 999px; 
            background: linear-gradient(90deg, #6366f1, #ec4899); 
            transition: width 1s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        
        /* Custom scrollbars */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        .glass-modal {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body class="bg-background text-text-main min-h-screen flex">

<!-- ====== LAYOUT WRAPPER ====== -->
<div class="flex flex-1 w-full max-w-[1600px] mx-auto relative min-h-screen">

    <!-- ====== SIDEBAR ====== -->
    <aside class="w-24 bg-white border-r border-slate-100 flex flex-col items-center py-8 fixed left-0 top-0 h-screen z-[1000] justify-between hidden md:flex">
        <!-- Logo at Top -->
        <div class="flex flex-col items-center gap-10">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-pink-500 via-purple-500 to-indigo-500 flex items-center justify-center shadow-lg relative group cursor-pointer">
                <span class="material-symbols-outlined text-white text-2xl font-bold">school</span>
                <div class="absolute -right-3 top-1/2 -translate-y-1/2 w-1 h-6 bg-primary rounded-l-full hidden group-hover:block"></div>
            </div>
            
            <!-- Menu Navigation -->
            <nav class="flex flex-col gap-6 items-center w-full">
                <a class="w-12 h-12 rounded-xl flex items-center justify-center text-slate-400 hover:text-primary hover:bg-slate-50 transition-all relative group shrink-0" href="index.php" title="Dashboard">
                    <span class="material-symbols-outlined text-[22px]">grid_view</span>
                </a>
                <a class="w-12 h-12 rounded-xl flex items-center justify-center text-primary bg-indigo-50/80 shadow-premium-sm relative transition-all group shrink-0" href="akademik.php" title="Data Mahasiswa">
                    <span class="material-symbols-outlined text-[22px]">group</span>
                    <!-- Active marker vertical bar -->
                    <div class="absolute left-[-24px] top-1/2 -translate-y-1/2 w-1.5 h-8 bg-primary rounded-r-full"></div>
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
    <main class="flex-1 md:pl-[136px] p-6 md:p-10 w-full min-h-screen flex flex-col gap-8">

        <!-- ====== HEADER / TOP BAR ====== -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 w-full">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Data Akademik</h1>
                <p class="text-sm font-semibold text-text-muted mt-1 flex items-center gap-1.5">Klik nama mahasiswa untuk melihat grafik IPK per semester.</p>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Add Student Button -->
                <button onclick="openCrudModal('add')" class="bg-primary hover:bg-primary-dark text-white rounded-2xl py-3 px-6 shadow-purple-glow font-bold text-xs flex items-center gap-1.5 transition-all">
                    <span class="material-symbols-outlined text-[16px] font-bold">add</span> Tambah Mahasiswa
                </button>

                <!-- Admin Avatar -->
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
                            <a href="index.php" class="flex items-center gap-2.5 py-2 px-3 rounded-xl hover:bg-slate-50 text-xs font-semibold text-slate-600 hover:text-primary transition-all">
                                <span class="material-symbols-outlined text-[18px]">grid_view</span>
                                Dashboard
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

        <!-- ====== FILTER & SEARCH BAR ====== -->
        <div class="bg-white rounded-[2rem] p-6 shadow-premium flex flex-col xl:flex-row gap-4 justify-between items-stretch xl:items-center border border-slate-100/50">
            <!-- Search field -->
            <div class="relative flex items-center flex-1 max-w-md">
                <input id="searchInput" type="text" placeholder="Cari nama atau NIM..."
                       class="w-full bg-slate-50 border-0 rounded-2xl py-3 px-4 pl-11 text-xs font-semibold focus:ring-2 focus:ring-primary focus:bg-white text-slate-700 outline-none transition-all placeholder:text-slate-400"/>
                <span class="material-symbols-outlined text-slate-400 absolute left-4 text-[18px]">search</span>
            </div>

            <!-- Filters & Buttons group -->
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-slate-400 text-[18px]">filter_alt</span>
                    <select id="filterAngkatan" class="border-0 bg-slate-50 text-slate-600 rounded-2xl pl-4 pr-10 py-2.5 text-xs font-bold focus:ring-primary focus:bg-white cursor-pointer hover:bg-slate-100 transition-colors">
                        <option value="">Semua Angkatan</option>
                        <?php
                        $angkatan_list = array_unique(array_column($students, 'angkatan'));
                        rsort($angkatan_list);
                        foreach ($angkatan_list as $a): ?>
                            <option value="<?= $a ?>"><?= htmlspecialchars($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <select id="filterStatus" class="border-0 bg-slate-50 text-slate-600 rounded-2xl pl-4 pr-10 py-2.5 text-xs font-bold focus:ring-primary focus:bg-white cursor-pointer hover:bg-slate-100 transition-colors">
                    <option value="">Semua Status</option>
                    <option value="Aktif">Aktif</option>
                    <option value="Lulus">Lulus</option>
                    <option value="Tidak Aktif">Tidak Aktif</option>
                </select>

                <button onclick="resetFilter()" class="py-2.5 px-4 rounded-2xl text-xs font-bold border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors flex items-center gap-1">
                    <span class="material-symbols-outlined text-[14px]">restart_alt</span> Reset
                </button>

                <div class="bg-indigo-50 text-primary px-4 py-2.5 rounded-2xl text-xs font-extrabold shadow-premium-sm">
                    Menampilkan <span id="rowCount" class="text-indigo-700">0</span> mahasiswa
                </div>
            </div>
        </div>

        <!-- ====== STUDENT TABLE SECTIONS ====== -->
        <div id="studentsContainer" class="flex flex-col gap-8 w-full">
            <?php if (empty($students)): ?>
            <div class="bg-white rounded-[2rem] p-12 text-center text-text-muted shadow-premium border border-slate-100/50">
                <span class="material-symbols-outlined text-5xl block mb-3 text-slate-300">person_off</span>
                <p class="text-sm font-semibold">Data mahasiswa tidak ditemukan atau API tidak terhubung.</p>
            </div>
            <?php else: foreach ($students_by_class as $kelas => $mhs_list): ?>
            <div class="class-section flex flex-col gap-4" data-kelas="<?= htmlspecialchars($kelas) ?>">
                <h3 class="text-md font-extrabold text-slate-800 flex items-center gap-2 px-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-primary shadow-purple-glow"></span>
                    <?= htmlspecialchars($kelas) ?>
                </h3>

                <div class="bg-white rounded-[2rem] shadow-premium overflow-hidden border border-slate-100/50">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/60 text-slate-400 font-extrabold text-[10px] uppercase tracking-wider border-b border-slate-100">
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
                            <tbody class="divide-y divide-slate-50 font-medium text-slate-700">
                                <?php foreach ($mhs_list as $i => $mhs):
                                    $ipk_val = (float)($mhs['ipk'] ?? 0);
                                    $ipk_pct = min(100, ($ipk_val / 4.0) * 100);
                                    $status  = $mhs['status_akademik'] ?? '-';
                                    $badge   = match(strtolower($status)) {
                                        'aktif'       => 'badge-active',
                                        'alumni', 'lulus' => 'badge-alumni',
                                        default       => 'badge-inactive',
                                    };
                                    $predikat = $mhs['predikat'] ?? '-';
                                    $json_data = htmlspecialchars(json_encode($mhs), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr class="hover:bg-slate-50/50 transition-colors student-row group"
                                    data-nim="<?= htmlspecialchars($mhs['nim'] ?? '') ?>"
                                    data-nama="<?= htmlspecialchars(strtolower($mhs['nama_mahasiswa'] ?? '')) ?>"
                                    data-angkatan="<?= htmlspecialchars($mhs['angkatan'] ?? '') ?>"
                                    data-status="<?= htmlspecialchars($status) ?>">
                                    <td class="px-6 py-4 text-text-muted row-num text-center text-xs font-semibold"><?= $i + 1 ?></td>
                                    <td class="px-6 py-4 font-mono text-xs text-text-muted"><?= htmlspecialchars($mhs['nim'] ?? '-') ?></td>
                                    <td class="px-6 py-4 font-bold text-slate-800 cursor-pointer hover:text-primary transition-colors" 
                                        onclick="openChart(<?= $mhs['sk_mahasiswa'] ?>, '<?= htmlspecialchars(addslashes($mhs['nama_mahasiswa'] ?? '')) ?>')" 
                                        title="Lihat grafik IPK">
                                        <?= htmlspecialchars($mhs['nama_mahasiswa'] ?? '-') ?>
                                    </td>
                                    <td class="px-6 py-4 text-text-muted text-xs"><?= htmlspecialchars($mhs['angkatan'] ?? '-') ?></td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-1 w-24">
                                            <span class="font-extrabold text-slate-800 text-xs"><?= number_format($ipk_val, 2) ?></span>
                                            <div class="ipk-bar w-full"><div class="ipk-fill" style="width:<?= $ipk_pct ?>%"></div></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs font-semibold text-text-muted"><?= htmlspecialchars($predikat) ?></td>
                                    <td class="px-6 py-4"><span class="badge <?= $badge ?>"><?= htmlspecialchars($status) ?></span></td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button onclick="openCrudModal('edit', <?= $json_data ?>)" class="text-slate-400 hover:text-primary hover:bg-indigo-50 p-2 rounded-xl transition-all" title="Edit">
                                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                            </button>
                                            <button onclick="deleteStudent(<?= $mhs['sk_mahasiswa'] ?>)" class="text-slate-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-xl transition-all" title="Hapus">
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <!-- ====== SUMMARY SECTION ====== -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 w-full">
            <?php
            if (!empty($students)) {
                $aktif   = count(array_filter($students, fn($m) => strtolower($m['status_akademik'] ?? '') === 'aktif'));
                $alumni  = count(array_filter($students, function($m) {
                    $st = strtolower($m['status_akademik'] ?? '');
                    return $st === 'alumni' || $st === 'lulus';
                }));
                $ipk_arr = array_column($students, 'ipk');
                $ipk_max = $ipk_arr ? max($ipk_arr) : 0;
                $ipk_min = $ipk_arr ? min(array_filter($ipk_arr, fn($v) => $v > 0)) : 0;
            }
            $stats = [
                ['label' => 'Total Mahasiswa Aktif', 'val' => $aktif ?? 0, 'icon' => 'group', 'bg' => 'bg-indigo-50', 'color' => 'text-primary'],
                ['label' => 'Total Alumni', 'val' => $alumni ?? 0, 'icon' => 'workspace_premium', 'bg' => 'bg-pink-50', 'color' => 'text-accent-pink'],
                ['label' => 'IPK Tertinggi', 'val' => number_format((float)($ipk_max ?? 0), 2), 'icon' => 'trending_up', 'bg' => 'bg-emerald-50', 'color' => 'text-emerald-500'],
                ['label' => 'IPK Terendah', 'val' => number_format((float)($ipk_min ?? 0), 2), 'icon' => 'trending_down', 'bg' => 'bg-rose-50', 'color' => 'text-rose-500'],
            ];
            foreach ($stats as $s): ?>
            <div class="bg-white border border-slate-100 rounded-[2rem] p-6 flex flex-col gap-4 shadow-premium group hover:border-primary transition-all relative overflow-hidden">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center <?= $s['bg'] ?> <?= $s['color'] ?> group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-[22px] font-bold"><?= $s['icon'] ?></span>
                    </div>
                    <div>
                        <p class="text-[11px] sm:text-xs font-extrabold text-text-muted tracking-wide whitespace-nowrap"><?= $s['label'] ?></p>
                        <p class="text-2xl font-extrabold text-slate-800 mt-1"><?= $s['val'] ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ====== FOOTER ====== -->
        <footer class="mt-auto py-8 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs font-semibold text-text-muted">
            <p>&copy; 2026 Teknik Komputer dan Jaringan PNUP. Academic Data Integration.</p>
        </footer>

    </main>
</div>

<!-- ====== MOBILE BOTTOM NAV ====== -->
<nav class="md:hidden fixed bottom-6 left-6 right-6 h-16 bg-white/95 backdrop-blur-lg rounded-2xl shadow-card-shadow border border-slate-100/50 flex items-center justify-around px-4 z-[9999]">
    <a href="index.php" class="flex flex-col items-center justify-center text-slate-400 hover:text-primary">
        <span class="material-symbols-outlined text-[22px]">grid_view</span>
        <span class="text-[9px] font-bold mt-0.5">Dashboard</span>
    </a>
    <a href="akademik.php" class="flex flex-col items-center justify-center text-primary px-3 py-1.5 rounded-xl bg-indigo-50/80">
        <span class="material-symbols-outlined text-[22px]" style="font-variation-settings: 'FILL' 1">group</span>
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

<!-- ====== PREMIUM MODAL GRAFIK IPK ====== -->
<div id="chartModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[9999] hidden items-center justify-center p-4 transition-all">
    <div class="bg-white/95 rounded-[2.5rem] w-full max-w-2xl shadow-card-shadow overflow-hidden border border-white/50 glass-modal transition-all transform scale-95 duration-300">
        <div class="flex items-center justify-between px-8 py-6 border-b border-slate-100 bg-slate-50/50">
            <div>
                <h3 class="font-extrabold text-slate-800 text-lg" id="modalTitle">Grafik IPK</h3>
                <p class="text-xs text-text-muted font-bold mt-0.5">Perkembangan IPS & IPK per semester</p>
            </div>
            <button onclick="closeChart()" class="w-10 h-10 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-red-500 transition-colors flex items-center justify-center">
                <span class="material-symbols-outlined text-[20px] font-bold">close</span>
            </button>
        </div>
        <div class="p-8">
            <div id="modalLoading" class="flex flex-col items-center justify-center py-12 gap-3">
                <div class="w-8 h-8 border-3 border-primary border-t-transparent rounded-full animate-spin"></div>
                <p class="text-xs font-bold text-text-muted">Memuat data grafik...</p>
            </div>
            <div id="modalChart" class="hidden relative h-64 w-full">
                <canvas id="studentChart"></canvas>
            </div>
            <p id="modalEmpty" class="hidden text-center text-text-muted text-xs py-8 px-6 bg-slate-50 rounded-2xl leading-relaxed">
                <span class="material-symbols-outlined block text-3xl mb-2 text-slate-400">info</span>
                Grafik perkembangan IPS dan IPK belum tersedia untuk mahasiswa ini.<br>
                <span class="opacity-80 block mt-1">(Bisa jadi karena statusnya Alumni yang hanya memiliki data IPK Akhir, atau data semester belum diinput).</span>
            </p>
        </div>
    </div>
</div>

<!-- ====== PREMIUM MODAL CRUD MAHASISWA ====== -->
<div id="crudModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[9999] hidden items-center justify-center p-4 transition-all">
    <div class="bg-white/95 rounded-[2.5rem] w-full max-w-md shadow-card-shadow overflow-hidden border border-white/50 glass-modal transition-all transform scale-95 duration-300">
        <div class="flex items-center justify-between px-8 py-6 border-b border-slate-100 bg-slate-50/50">
            <h3 class="font-extrabold text-slate-800 text-lg" id="crudModalTitle">Tambah Mahasiswa</h3>
            <button onclick="closeCrudModal()" class="w-10 h-10 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-red-500 transition-colors flex items-center justify-center">
                <span class="material-symbols-outlined text-[20px] font-bold">close</span>
            </button>
        </div>
        <div class="p-8">
            <form id="crudForm" onsubmit="saveStudent(event)">
                <input type="hidden" id="crud_sk" name="sk_mahasiswa">
                <div class="flex flex-col gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">NIM</label>
                        <input type="text" id="crud_nim" name="nim" required class="w-full bg-slate-50 border-0 rounded-2xl py-3 px-4 text-xs font-semibold focus:ring-2 focus:ring-primary focus:bg-white text-slate-700 outline-none transition-all placeholder:text-slate-400">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nama Mahasiswa</label>
                        <input type="text" id="crud_nama" name="nama_mahasiswa" required class="w-full bg-slate-50 border-0 rounded-2xl py-3 px-4 text-xs font-semibold focus:ring-2 focus:ring-primary focus:bg-white text-slate-700 outline-none transition-all placeholder:text-slate-400">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Angkatan</label>
                        <input type="number" id="crud_angkatan" name="angkatan" required class="w-full bg-slate-50 border-0 rounded-2xl py-3 px-4 text-xs font-semibold focus:ring-2 focus:ring-primary focus:bg-white text-slate-700 outline-none transition-all placeholder:text-slate-400">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Kelas</label>
                        <input type="text" id="crud_kelas" name="kelas" required class="w-full bg-slate-50 border-0 rounded-2xl py-3 px-4 text-xs font-semibold focus:ring-2 focus:ring-primary focus:bg-white text-slate-700 outline-none transition-all placeholder:text-slate-400">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Status Akademik</label>
                        <select id="crud_status" name="status_akademik" class="w-full bg-slate-50 border-0 rounded-2xl py-3 pl-4 pr-10 text-xs font-bold focus:ring-2 focus:ring-primary focus:bg-white text-slate-700 outline-none transition-all cursor-pointer">
                            <option value="Aktif">Aktif</option>
                            <option value="Lulus">Lulus (Alumni)</option>
                            <option value="Tidak Aktif">Tidak Aktif</option>
                        </select>
                    </div>
                    
                    <div class="mt-4 flex justify-end gap-3 border-t border-slate-100 pt-6">
                        <button type="button" onclick="closeCrudModal()" class="py-3 px-5 rounded-2xl text-xs font-bold border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">Batal</button>
                        <button type="submit" class="bg-primary hover:bg-primary-dark text-white rounded-2xl py-3 px-6 shadow-purple-glow font-bold text-xs flex items-center gap-1.5 transition-all">
                            <span class="material-symbols-outlined text-[16px]" id="crudBtnIcon">save</span>
                            <span id="crudBtnText">Simpan</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

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

// ====== DATA MAHASISWA ======
const allStudents = <?= json_encode($students ?: []) ?>;
let activeChart = null;
let crudMode = 'add';

// ====== FILTER & SEARCH ======
function filterTable() {
    const q       = document.getElementById('searchInput').value.toLowerCase();
    const ang     = document.getElementById('filterAngkatan').value;
    const status  = document.getElementById('filterStatus').value.toLowerCase();
    const sections = document.querySelectorAll('.class-section');
    let totalCount = 0;

    sections.forEach(sec => {
        const rows = sec.querySelectorAll('.student-row');
        let secCount = 0;
        rows.forEach(row => {
            const nama    = row.dataset.nama;
            const nim     = row.dataset.nim.toLowerCase();
            const angk    = row.dataset.angkatan;
            const stat    = row.dataset.status.toLowerCase();
            const matchQ  = !q || nama.includes(q) || nim.includes(q);
            const matchA  = !ang || angk === ang;
            const matchS  = !status || stat.includes(status);
            const show    = matchQ && matchA && matchS;
            row.style.display = show ? '' : 'none';
            if (show) secCount++;
        });
        sec.style.display = secCount > 0 ? '' : 'none';
        totalCount += secCount;
    });

    document.getElementById('rowCount').textContent = totalCount;
    renumberRows();
}

function renumberRows() {
    document.querySelectorAll('.class-section').forEach(sec => {
        let n = 1;
        sec.querySelectorAll('.student-row').forEach(row => {
            if (row.style.display !== 'none') row.querySelector('.row-num').textContent = n++;
        });
    });
}

function resetFilter() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterAngkatan').value = '';
    document.getElementById('filterStatus').value = '';
    filterTable();
}

// ====== CRUD MODAL ======
function openCrudModal(mode, data = null) {
    crudMode = mode;
    const modal = document.getElementById('crudModal');
    modal.classList.replace('hidden', 'flex');
    setTimeout(() => {
        modal.querySelector('.glass-modal').classList.replace('scale-95', 'scale-100');
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
    modal.querySelector('.glass-modal').classList.replace('scale-100', 'scale-95');
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
            alert(json.message || 'Berhasil menyimpan data');
            location.reload();
        } else {
            alert('Error: ' + json.message);
        }
    } catch (err) {
        console.error("Save Student Error:", err);
        alert('Gagal menghubungi server.');
    }
}

async function deleteStudent(sk) {
    if (!confirm('Yakin ingin menghapus data mahasiswa ini?')) return;
    
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
            alert(json.message || 'Berhasil menghapus data');
            location.reload();
        } else {
            alert('Error: ' + json.message);
        }
    } catch (err) {
        console.error("Delete Student Error:", err);
        alert('Gagal menghubungi server.');
    }
}

// ====== MODAL GRAFIK ======
async function openChart(sk, nama) {
    const modal = document.getElementById('chartModal');
    modal.classList.replace('hidden', 'flex');
    setTimeout(() => {
        modal.querySelector('.glass-modal').classList.replace('scale-95', 'scale-100');
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
                        backgroundColor: 'rgba(99, 102, 241, 0.15)', // Light Indigo
                        borderColor: '#6366f1',
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
                    legend: { 
                        position: 'top', 
                        labels: { 
                            font: { family: 'Plus Jakarta Sans', size: 11, weight: 'bold' }, 
                            color: '#64748b',
                            usePointStyle: true,
                            pointStyle: 'circle'
                        } 
                    },
                    tooltip: { 
                        backgroundColor: '#1e293b',
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
    modal.querySelector('.glass-modal').classList.replace('scale-100', 'scale-95');
    setTimeout(() => {
        modal.classList.replace('flex', 'hidden');
        if (activeChart) { activeChart.destroy(); activeChart = null; }
    }, 200);
}

// Tutup modal klik di luar
document.getElementById('chartModal').addEventListener('click', function(e) {
    if (e.target === this) closeChart();
});

document.getElementById('crudModal').addEventListener('click', function(e) {
    if (e.target === this) closeCrudModal();
});

// Init
document.getElementById('searchInput').addEventListener('input', filterTable);
document.getElementById('filterAngkatan').addEventListener('change', filterTable);
document.getElementById('filterStatus').addEventListener('change', filterTable);
filterTable();
</script>
</body>
</html>