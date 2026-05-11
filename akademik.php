<?php
// File: akademik.php
require_once 'config.php';

$students = callAPI(API_BASE . "?type=students");
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
                    "primary": "#00236f", "primary-fixed": "#dce1ff", "primary-fixed-dim": "#b6c4ff",
                    "on-primary": "#ffffff", "primary-container": "#1e3a8a", "on-primary-container": "#90a8ff",
                    "secondary": "#006c49", "secondary-container": "#6cf8bb", "on-secondary-container": "#00714d",
                    "on-secondary": "#ffffff", "tertiary": "#4b1c00", "tertiary-fixed": "#ffdbcb",
                    "surface": "#faf8ff", "on-surface": "#1a1b21", "on-surface-variant": "#444651",
                    "surface-container-lowest": "#ffffff", "surface-container-low": "#f4f3fa",
                    "surface-container": "#eeedf4", "surface-container-high": "#e9e7ef",
                    "surface-container-highest": "#e3e1e9", "background": "#faf8ff", "on-background": "#1a1b21",
                    "outline": "#757682", "outline-variant": "#c5c5d3",
                    "error": "#ba1a1a", "error-container": "#ffdad6",
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
        body { font-family: 'Inter', sans-serif; }
        .badge { display: inline-flex; align-items: center; padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
        .badge-active   { background: #6cf8bb; color: #004d33; }
        .badge-alumni   { background: #dce1ff; color: #00236f; }
        .badge-inactive { background: #ffdad6; color: #93000a; }
        tr.selected-row { background-color: #f4f3fa !important; }
        #chartModal { backdrop-filter: blur(4px); }
        .ipk-bar { height: 6px; border-radius: 999px; background: #dce1ff; }
        .ipk-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #006c49, #00236f); transition: width .5s ease; }
    </style>
</head>
<body class="bg-background text-on-background min-h-screen flex flex-col">

<!-- HEADER -->
<header class="bg-surface/80 backdrop-blur-md border-b border-outline-variant/30 sticky top-0 z-[1000] flex justify-between items-center w-full px-8 h-16">
    <div class="flex items-center gap-4">
        <h1 class="text-2xl font-bold text-primary">TKJ PNUP</h1>
    </div>
    <nav class="hidden md:flex gap-6">
        <a class="text-on-surface-variant hover:text-primary transition-colors" href="index.php">Dashboard</a>
        <a class="text-primary border-b-2 border-primary pb-1 font-bold" href="akademik.php">Academic</a>
    </nav>
    <img alt="Profile" class="w-10 h-10 rounded-full border-2 border-surface object-cover"
         src="https://ui-avatars.com/api/?name=Admin+TKJ&background=00236f&color=fff"/>
</header>

<div class="flex flex-1 max-w-[1280px] mx-auto w-full">

    <!-- SIDEBAR -->
    <aside class="bg-surface-container-low border-r border-outline-variant/20 w-64 hidden lg:flex flex-col fixed left-0 top-0 pt-20 pb-8 px-4 z-[900] h-screen">
        <div class="mb-8 px-4">
            <h2 class="text-lg font-black text-primary">Integrasi Data</h2>
            <p class="text-xs text-on-surface-variant mt-1">Academic Portal 2026</p>
        </div>
        <nav class="flex-1 flex flex-col gap-2">
            <a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-high rounded-lg text-sm hover:translate-x-1 transition-transform" href="index.php">
                <span class="material-symbols-outlined">dashboard</span> Overview
            </a>
            <a class="flex items-center gap-3 px-4 py-3 bg-secondary-container text-on-secondary-container rounded-lg font-bold text-sm" href="akademik.php">
                <span class="material-symbols-outlined">groups</span> Student Data
            </a>
        </nav>
        <div class="border-t border-outline-variant/20 pt-4">
            <a class="flex items-center gap-3 px-4 py-2 text-error hover:bg-error-container rounded-lg text-sm" href="#">
                <span class="material-symbols-outlined">logout</span> Log Out
            </a>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="flex-1 lg:ml-64 p-4 md:p-8 w-full">
        <div class="mb-6">
            <h2 class="text-3xl font-bold text-primary mb-2">Data Akademik Mahasiswa</h2>
            <p class="text-sm text-on-surface-variant">Klik nama mahasiswa untuk melihat grafik IPK per semester.</p>
        </div>

        <!-- FILTER & SEARCH -->
        <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-xl p-4 mb-6 flex flex-col sm:flex-row gap-4 items-start sm:items-center">
            <div class="flex items-center gap-2 flex-1">
                <span class="material-symbols-outlined text-on-surface-variant">search</span>
                <input id="searchInput" type="text" placeholder="Cari nama atau NIM..."
                       class="flex-1 border-none outline-none bg-transparent text-sm text-on-surface placeholder:text-on-surface-variant"/>
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
                <select id="filterStatus" class="border border-outline-variant rounded-lg px-3 py-1.5 text-sm bg-surface text-on-surface outline-none focus:border-primary">
                    <option value="">Semua Status</option>
                    <option value="Aktif">Aktif</option>
                    <option value="Alumni">Alumni</option>
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

        <!-- TABEL -->
        <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-xl overflow-hidden mb-6" style="box-shadow:0 4px 6px -1px rgba(0,0,0,.05)">
            <div class="overflow-x-auto">
                <table class="w-full text-sm" id="studentTable">
                    <thead>
                        <tr class="bg-surface-container text-on-surface-variant border-b border-outline-variant/30 text-left">
                            <th class="px-4 py-3 font-semibold">No</th>
                            <th class="px-4 py-3 font-semibold cursor-pointer hover:text-primary" onclick="sortTable('nim')">NIM ↕</th>
                            <th class="px-4 py-3 font-semibold cursor-pointer hover:text-primary" onclick="sortTable('nama_mahasiswa')">Nama ↕</th>
                            <th class="px-4 py-3 font-semibold">Angkatan</th>
                            <th class="px-4 py-3 font-semibold">Kelas</th>
                            <th class="px-4 py-3 font-semibold cursor-pointer hover:text-primary" onclick="sortTable('ipk')">IPK ↕</th>
                            <th class="px-4 py-3 font-semibold">Predikat</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-12 text-on-surface-variant">
                                <span class="material-symbols-outlined text-4xl block mb-2">person_off</span>
                                Data mahasiswa tidak ditemukan atau API tidak terhubung.
                            </td>
                        </tr>
                        <?php else: foreach ($students as $i => $mhs):
                            $ipk_val = (float)($mhs['ipk'] ?? 0);
                            $ipk_pct = min(100, ($ipk_val / 4.0) * 100);
                            $status  = $mhs['status_akademik'] ?? '-';
                            $badge   = match(strtolower($status)) {
                                'aktif'       => 'badge-active',
                                'alumni'      => 'badge-alumni',
                                default       => 'badge-inactive',
                            };
                            $predikat = $mhs['predikat'] ?? '-';
                        ?>
                        <tr class="border-b border-outline-variant/20 hover:bg-surface-container-low transition-colors student-row"
                            data-nim="<?= htmlspecialchars($mhs['nim'] ?? '') ?>"
                            data-nama="<?= htmlspecialchars(strtolower($mhs['nama_mahasiswa'] ?? '')) ?>"
                            data-angkatan="<?= htmlspecialchars($mhs['angkatan'] ?? '') ?>"
                            data-status="<?= htmlspecialchars($status) ?>"
                            data-sk="<?= htmlspecialchars($mhs['sk_mahasiswa'] ?? '') ?>"
                            data-ipk="<?= $ipk_val ?>">
                            <td class="px-4 py-3 text-on-surface-variant row-num"><?= $i + 1 ?></td>
                            <td class="px-4 py-3 font-mono text-xs"><?= htmlspecialchars($mhs['nim'] ?? '-') ?></td>
                            <td class="px-4 py-3 font-medium text-on-background"><?= htmlspecialchars($mhs['nama_mahasiswa'] ?? '-') ?></td>
                            <td class="px-4 py-3 text-on-surface-variant"><?= htmlspecialchars($mhs['angkatan'] ?? '-') ?></td>
                            <td class="px-4 py-3 text-on-surface-variant"><?= htmlspecialchars($mhs['kelas'] ?? '-') ?></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-1">
                                    <span class="font-bold text-primary"><?= number_format($ipk_val, 2) ?></span>
                                    <div class="ipk-bar w-20"><div class="ipk-fill" style="width:<?= $ipk_pct ?>%"></div></div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-on-surface-variant"><?= htmlspecialchars($predikat) ?></td>
                            <td class="px-4 py-3"><span class="badge <?= $badge ?>"><?= htmlspecialchars($status) ?></span></td>
                            <td class="px-4 py-3 text-center">
                                <button onclick="openChart(<?= $mhs['sk_mahasiswa'] ?>, '<?= htmlspecialchars(addslashes($mhs['nama_mahasiswa'] ?? '')) ?>')"
                                        class="text-primary hover:bg-primary-fixed p-1.5 rounded-lg transition-colors" title="Lihat grafik IPK">
                                    <span class="material-symbols-outlined text-lg">bar_chart</span>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
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
                ['label' => 'Mahasiswa Aktif', 'val' => $aktif ?? 0, 'icon' => 'person', 'color' => 'secondary'],
                ['label' => 'Alumni', 'val' => $alumni ?? 0, 'icon' => 'school', 'color' => 'primary'],
                ['label' => 'IPK Tertinggi', 'val' => number_format((float)($ipk_max ?? 0), 2), 'icon' => 'emoji_events', 'color' => 'tertiary'],
                ['label' => 'IPK Terendah', 'val' => number_format((float)($ipk_min ?? 0), 2), 'icon' => 'trending_down', 'color' => 'outline'],
            ];
            foreach ($stats as $s): ?>
            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-xl p-4 flex items-center gap-3" style="box-shadow:0 2px 4px rgba(0,0,0,.04)">
                <div class="p-2 rounded-lg bg-surface-container">
                    <span class="material-symbols-outlined text-<?= $s['color'] ?> text-xl"><?= $s['icon'] ?></span>
                </div>
                <div>
                    <p class="text-xs text-on-surface-variant"><?= $s['label'] ?></p>
                    <p class="text-xl font-bold text-primary"><?= $s['val'] ?></p>
                </div>
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
<div id="chartModal" class="fixed inset-0 bg-black/40 z-[9999] hidden items-center justify-center p-4">
    <div class="bg-surface-container-lowest rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-outline-variant/20 bg-surface-container-low">
            <div>
                <h3 class="font-bold text-primary text-base" id="modalTitle">Grafik IPK</h3>
                <p class="text-xs text-on-surface-variant">Perkembangan IPS & IPK per semester</p>
            </div>
            <button onclick="closeChart()" class="p-2 rounded-lg hover:bg-surface-container-high transition-colors">
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
            <p id="modalEmpty" class="hidden text-center text-on-surface-variant text-sm py-8">
                <span class="material-symbols-outlined block text-3xl mb-2">bar_chart</span>
                Belum ada data riwayat akademik untuk mahasiswa ini.
            </p>
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
    const rows    = document.querySelectorAll('.student-row');
    let count = 0;

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
        if (show) count++;
    });

    document.getElementById('rowCount').textContent = count;
    renumberRows();
}

function renumberRows() {
    let n = 1;
    document.querySelectorAll('.student-row').forEach(row => {
        if (row.style.display !== 'none') row.querySelector('.row-num').textContent = n++;
    });
}

function resetFilter() {
    document.getElementById('searchInput').value = '';
    document.getElementById('filterAngkatan').value = '';
    document.getElementById('filterStatus').value = '';
    filterTable();
}

// ====== SORT ======
function sortTable(key) {
    if (sortKey === key) sortAsc = !sortAsc; else { sortKey = key; sortAsc = true; }
    const tbody = document.getElementById('tableBody');
    const rows  = Array.from(document.querySelectorAll('.student-row'));
    rows.sort((a, b) => {
        let va = a.dataset[key] || a.querySelector('td:nth-child(' + (key === 'nim' ? 2 : key === 'nama_mahasiswa' ? 3 : 6) + ')')?.textContent || '';
        let vb = b.dataset[key] || b.querySelector('td:nth-child(' + (key === 'nim' ? 2 : key === 'nama_mahasiswa' ? 3 : 6) + ')')?.textContent || '';
        if (!isNaN(va) && !isNaN(vb)) { va = parseFloat(va); vb = parseFloat(vb); }
        return sortAsc ? (va > vb ? 1 : -1) : (va < vb ? 1 : -1);
    });
    rows.forEach(r => tbody.appendChild(r));
    filterTable();
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
                        backgroundColor: 'rgba(0, 108, 73, 0.25)',
                        borderColor: '#006c49',
                        borderWidth: 2,
                        borderRadius: 6,
                        type: 'bar',
                    },
                    {
                        label: 'IPK Kumulatif',
                        data: data.map(d => parseFloat(d.ipk || 0)),
                        borderColor: '#00236f',
                        backgroundColor: 'transparent',
                        borderWidth: 2.5,
                        tension: 0.4,
                        pointBackgroundColor: '#00236f',
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

// Init
document.getElementById('searchInput').addEventListener('input', filterTable);
document.getElementById('filterAngkatan').addEventListener('change', filterTable);
document.getElementById('filterStatus').addEventListener('change', filterTable);
filterTable();
</script>
</body>
</html>