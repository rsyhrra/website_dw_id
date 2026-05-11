<?php
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
        body { font-family: 'Inter', sans-serif; }
        .badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; letter-spacing: 0.02em; }
        .badge-active   { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-alumni   { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .badge-inactive { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        tr.student-row:hover { background-color: #f8fafc !important; }
        #chartModal { backdrop-filter: blur(8px); }
        .ipk-bar { height: 6px; border-radius: 999px; background: #e2e8f0; overflow: hidden; }
        .ipk-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #3b82f6, #0ea5e9); transition: width 1s cubic-bezier(0.4, 0, 0.2, 1); }
        
        /* Custom scrollbar for clean look */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-background text-on-background min-h-screen flex flex-col">

<!-- HEADER -->
<header class="bg-surface/90 backdrop-blur-md border-b border-outline-variant sticky top-0 z-[1000] flex justify-between items-center w-full px-8 h-16 shadow-soft-sm">
    <div class="flex items-center gap-4">
        <h1 class="text-2xl font-bold text-primary tracking-tight">TKJ PNUP</h1>
    </div>
    <nav class="hidden md:flex gap-8">
        <a class="text-on-surface-variant hover:text-primary font-medium transition-colors" href="index.php">Dashboard</a>
        <a class="text-primary border-b-2 border-primary pb-1 font-semibold" href="akademik.php">Akademik</a>
    </nav>
    <img alt="Profile" class="w-9 h-9 rounded-full border border-outline-variant object-cover shadow-sm"
         src="https://ui-avatars.com/api/?name=Admin+TKJ&background=eff6ff&color=1e40af"/>
</header>

<div class="flex flex-1 max-w-[1400px] mx-auto w-full">

    <!-- SIDEBAR -->
    <aside class="bg-surface border-r border-outline-variant w-64 hidden lg:flex flex-col fixed left-0 top-0 pt-20 pb-8 px-4 z-[900] h-screen shadow-soft-sm">
        <div class="mb-8 px-4">
            <h2 class="text-sm font-bold text-on-surface-variant uppercase tracking-wider">Portal Data</h2>
            <p class="text-xs text-on-surface-variant opacity-70 mt-1">Sistem Informasi 2026</p>
        </div>
        <nav class="flex-1 flex flex-col gap-1.5">
            <a class="flex items-center gap-3 px-4 py-2.5 text-on-surface-variant hover:bg-surface-container hover:text-on-surface rounded-lg text-sm font-medium transition-all" href="index.php">
                <span class="material-symbols-outlined text-[20px]">dashboard</span> Ringkasan
            </a>
            <a class="flex items-center gap-3 px-4 py-2.5 bg-primary-container text-primary rounded-lg font-semibold text-sm transition-all" href="akademik.php">
                <span class="material-symbols-outlined text-[20px]">groups</span> Data Mahasiswa
            </a>
        </nav>
        <div class="border-t border-outline-variant pt-4 mt-auto">
            <a class="flex items-center gap-3 px-4 py-2.5 text-error hover:bg-error-container rounded-lg text-sm font-medium transition-all" href="#">
                <span class="material-symbols-outlined text-[20px]">logout</span> Keluar
            </a>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="flex-1 lg:ml-64 p-4 md:p-8 w-full">
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold text-primary mb-2">Data Akademik Mahasiswa</h2>
                <p class="text-sm text-on-surface-variant">Klik nama mahasiswa untuk melihat grafik IPK per semester.</p>
            </div>
            <button onclick="openCrudModal('add')" class="bg-primary text-on-primary px-4 py-2 rounded-lg font-semibold flex items-center gap-2 hover:bg-primary-container transition-colors shadow-md">
                <span class="material-symbols-outlined">add</span> Tambah Mahasiswa
            </button>
        </div>

        <!-- FILTER & SEARCH -->
        <div class="bg-surface border border-outline-variant rounded-xl p-4 mb-6 flex flex-col sm:flex-row gap-4 items-start sm:items-center shadow-soft-sm">
            <div class="flex items-center gap-2 flex-1 bg-surface-container px-3 py-2 rounded-lg border border-transparent focus-within:border-primary focus-within:bg-surface transition-colors">
                <span class="material-symbols-outlined text-on-surface-variant">search</span>
                <input id="searchInput" type="text" placeholder="Cari nama atau NIM..."
                       class="flex-1 border-none outline-none bg-transparent text-sm text-on-surface placeholder:text-on-surface-variant w-full"/>
            </div>
            <div class="flex gap-3 flex-wrap">
                <select id="filterAngkatan" class="border border-outline-variant rounded-lg px-3 py-1.5 text-sm bg-surface text-on-surface outline-none focus:border-primary">
                    <option value="">Semua Angkatan</option>
                    <?php
                    $angkatan_list = array_unique(array_column($students, 'angkatan'));
                    rsort($angkatan_list);
                    foreach ($angkatan_list as $a): ?>
                        <option value="<?= $a ?>"><?= htmlspecialchars($a) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filterStatus" class="border border-outline-variant rounded-lg px-3 py-2 text-sm bg-surface text-on-surface outline-none focus:border-primary cursor-pointer hover:border-outline">
                    <option value="">Semua Status</option>
                    <option value="Aktif">Aktif</option>
                    <option value="Lulus">Lulus</option>
                    <option value="Tidak Aktif">Tidak Aktif</option>
                </select>
                <button onclick="resetFilter()" class="text-xs text-on-surface-variant hover:text-primary px-2 py-1 rounded border border-outline-variant hover:border-primary transition-colors">
                    Reset
                </button>
            </div>
            <div class="text-xs text-on-surface-variant whitespace-nowrap">
                Menampilkan <span id="rowCount" class="font-bold text-primary">0</span> mahasiswa
            </div>
        </div>

        <!-- TABEL PER KELAS -->
        <div id="studentsContainer">
            <?php if (empty($students)): ?>
            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-xl p-12 text-center text-on-surface-variant mb-6">
                <span class="material-symbols-outlined text-4xl block mb-2">person_off</span>
                Data mahasiswa tidak ditemukan atau API tidak terhubung.
            </div>
            <?php else: foreach ($students_by_class as $kelas => $mhs_list): ?>
            <div class="class-section mb-10" data-kelas="<?= htmlspecialchars($kelas) ?>">
                <h3 class="text-lg font-bold text-on-surface mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">school</span> <?= htmlspecialchars($kelas) ?>
                </h3>
                <div class="bg-surface border border-outline-variant rounded-xl overflow-hidden shadow-soft">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-surface-container-low text-on-surface-variant border-b border-outline-variant text-left text-xs uppercase tracking-wider">
                                    <th class="px-5 py-4 font-semibold w-12 text-center">No</th>
                                    <th class="px-5 py-4 font-semibold">NIM</th>
                                    <th class="px-5 py-4 font-semibold">Nama Mahasiswa</th>
                                    <th class="px-5 py-4 font-semibold">Angkatan</th>
                                    <th class="px-5 py-4 font-semibold">IPK</th>
                                    <th class="px-5 py-4 font-semibold">Predikat</th>
                                    <th class="px-5 py-4 font-semibold">Status</th>
                                    <th class="px-5 py-4 font-semibold text-center w-28">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/60">
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
                                <tr class="hover:bg-surface-container-low transition-colors student-row group"
                                    data-nim="<?= htmlspecialchars($mhs['nim'] ?? '') ?>"
                                    data-nama="<?= htmlspecialchars(strtolower($mhs['nama_mahasiswa'] ?? '')) ?>"
                                    data-angkatan="<?= htmlspecialchars($mhs['angkatan'] ?? '') ?>"
                                    data-status="<?= htmlspecialchars($status) ?>">
                                    <td class="px-5 py-3.5 text-on-surface-variant row-num text-center"><?= $i + 1 ?></td>
                                    <td class="px-5 py-3.5 font-mono text-xs text-on-surface-variant"><?= htmlspecialchars($mhs['nim'] ?? '-') ?></td>
                                    <td class="px-5 py-3.5 font-medium text-on-surface cursor-pointer group-hover:text-primary transition-colors" onclick="openChart(<?= $mhs['sk_mahasiswa'] ?>, '<?= htmlspecialchars(addslashes($mhs['nama_mahasiswa'] ?? '')) ?>')" title="Lihat grafik IPK">
                                        <?= htmlspecialchars($mhs['nama_mahasiswa'] ?? '-') ?>
                                    </td>
                                    <td class="px-5 py-3.5 text-on-surface-variant"><?= htmlspecialchars($mhs['angkatan'] ?? '-') ?></td>
                                    <td class="px-5 py-3.5">
                                        <div class="flex flex-col gap-1.5 w-24">
                                            <span class="font-bold text-on-surface"><?= number_format($ipk_val, 2) ?></span>
                                            <div class="ipk-bar w-full"><div class="ipk-fill" style="width:<?= $ipk_pct ?>%"></div></div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 text-xs text-on-surface-variant"><?= htmlspecialchars($predikat) ?></td>
                                    <td class="px-5 py-3.5"><span class="badge <?= $badge ?>"><?= htmlspecialchars($status) ?></span></td>
                                    <td class="px-5 py-3.5 text-center">
                                        <div class="flex justify-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button onclick="openCrudModal('edit', <?= $json_data ?>)" class="text-on-surface-variant hover:text-primary hover:bg-primary-container p-1.5 rounded-md transition-colors" title="Edit">
                                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                            </button>
                                            <button onclick="deleteStudent(<?= $mhs['sk_mahasiswa'] ?>)" class="text-on-surface-variant hover:text-error hover:bg-error-container p-1.5 rounded-md transition-colors" title="Hapus">
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

        <!-- RINGKASAN BAWAH -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php
            if (!empty($students)) {
                $aktif   = count(array_filter($students, fn($m) => strtolower($m['status_akademik'] ?? '') === 'aktif'));
                $alumni  = count(array_filter($students, fn($m) => strtolower($m['status_akademik'] ?? '') === 'alumni'));
                $ipk_arr = array_column($students, 'ipk');
                $ipk_max = $ipk_arr ? max($ipk_arr) : 0;
                $ipk_min = $ipk_arr ? min(array_filter($ipk_arr, fn($v) => $v > 0)) : 0;
            }
            $stats = [
                ['label' => 'Total Mahasiswa Aktif', 'val' => $aktif ?? 0, 'icon' => 'group', 'color' => 'primary'],
                ['label' => 'Total Alumni', 'val' => $alumni ?? 0, 'icon' => 'workspace_premium', 'color' => 'secondary'],
                ['label' => 'IPK Tertinggi', 'val' => number_format((float)($ipk_max ?? 0), 2), 'icon' => 'trending_up', 'color' => 'tertiary'],
                ['label' => 'IPK Terendah', 'val' => number_format((float)($ipk_min ?? 0), 2), 'icon' => 'trending_down', 'color' => 'on-surface-variant'],
            ];
            foreach ($stats as $s): ?>
            <div class="bg-surface border border-outline-variant rounded-xl p-5 flex flex-col gap-3 shadow-soft-sm relative overflow-hidden group hover:border-primary transition-colors">
                <div class="flex items-center gap-3 relative z-10">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-surface-container-low group-hover:bg-primary-container transition-colors">
                        <span class="material-symbols-outlined text-<?= $s['color'] ?>"><?= $s['icon'] ?></span>
                    </div>
                    <p class="text-sm font-medium text-on-surface-variant"><?= $s['label'] ?></p>
                </div>
                <p class="text-3xl font-bold text-on-surface relative z-10"><?= $s['val'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<!-- FOOTER -->
<footer class="bg-surface-container-highest border-t border-outline-variant/30 w-full py-4 px-8 text-center mt-auto">
    <p class="text-on-surface-variant text-sm">© 2026 Teknik Komputer dan Jaringan PNUP</p>
</footer>

<!-- MODAL GRAFIK IPK -->
<div id="chartModal" class="fixed inset-0 bg-on-surface/20 z-[9999] hidden items-center justify-center p-4 transition-opacity">
    <div class="bg-surface rounded-2xl w-full max-w-2xl shadow-soft overflow-hidden border border-outline-variant">
        <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant bg-surface-container-low">
            <div>
                <h3 class="font-bold text-on-surface text-lg" id="modalTitle">Grafik IPK</h3>
                <p class="text-xs text-on-surface-variant mt-0.5">Perkembangan IPS & IPK per semester</p>
            </div>
            <button onclick="closeChart()" class="p-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container hover:text-error transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6">
            <div id="modalLoading" class="flex flex-col items-center justify-center py-12 gap-3">
                <div class="w-8 h-8 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
                <p class="text-sm text-on-surface-variant">Memuat data grafik...</p>
            </div>
            <div id="modalChart" class="hidden">
                <canvas id="studentChart" height="250"></canvas>
            </div>
            <p id="modalEmpty" class="hidden text-center text-on-surface-variant text-sm py-8 px-6">
                <span class="material-symbols-outlined block text-3xl mb-2 text-outline">info</span>
                Grafik perkembangan IPS dan IPK belum tersedia untuk mahasiswa ini.<br>
                <span class="text-xs mt-1 block opacity-80">(Bisa jadi karena statusnya Alumni yang hanya memiliki data IPK Akhir, atau data semester belum diinput).</span>
            </p>
        </div>
    </div>
</div>

<!-- MODAL CRUD MAHASISWA -->
<div id="crudModal" class="fixed inset-0 bg-on-surface/20 z-[9999] hidden items-center justify-center p-4 transition-opacity">
    <div class="bg-surface rounded-2xl w-full max-w-md shadow-soft overflow-hidden border border-outline-variant">
        <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant bg-surface-container-low">
            <h3 class="font-bold text-on-surface text-lg" id="crudModalTitle">Tambah Mahasiswa</h3>
            <button onclick="closeCrudModal()" class="p-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container hover:text-error transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-6">
            <form id="crudForm" onsubmit="saveStudent(event)">
                <input type="hidden" id="crud_sk" name="sk_mahasiswa">
                <div class="flex flex-col gap-4">
                    <div>
                        <label class="block text-sm font-medium text-on-surface mb-1">NIM</label>
                        <input type="text" id="crud_nim" name="nim" required class="w-full border border-outline-variant rounded-lg px-3 py-2 text-sm focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-on-surface mb-1">Nama Mahasiswa</label>
                        <input type="text" id="crud_nama" name="nama_mahasiswa" required class="w-full border border-outline-variant rounded-lg px-3 py-2 text-sm focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-on-surface mb-1">Angkatan</label>
                        <input type="number" id="crud_angkatan" name="angkatan" required class="w-full border border-outline-variant rounded-lg px-3 py-2 text-sm focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-on-surface mb-1">Kelas</label>
                        <input type="text" id="crud_kelas" name="kelas" required class="w-full border border-outline-variant rounded-lg px-3 py-2 text-sm focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-on-surface mb-1">Status Akademik</label>
                        <select id="crud_status" name="status_akademik" class="w-full border border-outline-variant rounded-lg px-3 py-2 text-sm focus:border-primary outline-none">
                            <option value="Aktif">Aktif</option>
                            <option value="Lulus">Lulus (Alumni)</option>
                            <option value="Tidak Aktif">Tidak Aktif</option>
                        </select>
                    </div>
                    <div class="mt-4 flex justify-end gap-3">
                        <button type="button" onclick="closeCrudModal()" class="px-4 py-2 rounded-lg text-sm font-medium text-on-surface-variant hover:bg-surface-container-high transition-colors">Batal</button>
                        <button type="submit" class="bg-primary text-on-primary px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-container transition-colors flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm" id="crudBtnIcon">save</span>
                            <span id="crudBtnText">Simpan</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ====== DATA MAHASISWA ======
const allStudents = <?= json_encode($students ?: []) ?>;
let sortKey = '', sortAsc = true;
let activeChart = null;

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
    document.getElementById('crudModal').classList.replace('hidden', 'flex');
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
    document.getElementById('crudModal').classList.replace('flex', 'hidden');
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
    document.getElementById('chartModal').classList.replace('hidden', 'flex');
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
                        backgroundColor: 'rgba(59, 130, 246, 0.2)', // blue-500 with opacity
                        borderColor: '#3b82f6',
                        borderWidth: 2,
                        borderRadius: 4,
                        type: 'bar',
                    },
                    {
                        label: 'IPK Kumulatif',
                        data: data.map(d => parseFloat(d.ipk || 0)),
                        borderColor: '#0f172a', // slate-900
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        tension: 0.3,
                        pointBackgroundColor: '#0f172a',
                        pointRadius: 5,
                        type: 'line',
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: { min: 0, max: 4.0, grid: { color: 'rgba(197,197,211,.2)' }, ticks: { font: { family: 'Inter', size: 11 } } },
                    x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 11 } } }
                },
                plugins: {
                    legend: { position: 'top', labels: { font: { family: 'Inter', size: 12 }, usePointStyle: true } },
                    tooltip: { callbacks: { label: ctx => ` ${ctx.dataset.label}: ${parseFloat(ctx.parsed.y).toFixed(2)}` } }
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
    document.getElementById('chartModal').classList.replace('flex', 'hidden');
    if (activeChart) { activeChart.destroy(); activeChart = null; }
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