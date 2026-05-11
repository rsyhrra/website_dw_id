<?php
// Memanggil config.php untuk pengaturan API_BASE dan fungsi callAPI
require_once 'config.php';

// Menarik semua data mahasiswa dari API
$students = callAPI(API_BASE . "?type=students");

// Mengekstrak daftar kelas unik untuk Dropdown Filter secara dinamis
$daftar_kelas = [];
if (!empty($students)) {
    $daftar_kelas = array_unique(array_column($students, 'kelas'));
    sort($daftar_kelas);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>TKJ PNUP - Akademik</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            theme: {
                extend: {
                    "colors": {
                        "primary": "#00236f", "primary-fixed": "#dce1ff", "secondary": "#006c49",
                        "background": "#faf8ff", "surface": "#ffffff", "error": "#ba1a1a"
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined { font-family: 'Material Symbols Outlined'; }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-background text-slate-900 min-h-screen flex flex-col">

<header class="bg-white/80 backdrop-blur-md border-b border-slate-200 shadow-sm sticky top-0 z-[1000] flex justify-between items-center w-full px-8 h-16">
    <div class="flex items-center gap-4">
        <h1 class="text-2xl font-bold text-primary">TKJ PNUP</h1>
        <div class="hidden md:flex items-center gap-2 bg-emerald-50 text-emerald-700 px-3 py-1 rounded-full text-xs font-bold">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Live Data API
        </div>
    </div>
    <nav class="hidden md:flex gap-8">
        <a class="text-slate-500 hover:text-primary transition-colors font-medium" href="index.php">Dashboard</a>
        <a class="text-primary border-b-2 border-primary pb-1 font-bold" href="akademik.php">Academic</a>
    </nav>
    <div class="flex items-center gap-4">
        <button class="text-slate-400 hover:text-primary"><span class="material-symbols-outlined">notifications</span></button>
        <img class="w-10 h-10 rounded-full border border-slate-200" src="https://ui-avatars.com/api/?name=Admin+TKJ&background=00236f&color=fff"/>
    </div>
</header>

<div class="flex flex-1 max-w-[1440px] mx-auto w-full">
    <aside class="bg-white border-r border-slate-200 w-64 hidden lg:flex flex-col fixed left-0 top-0 pt-20 pb-8 px-4 h-screen z-[900]">
        <nav class="flex-1 flex flex-col gap-2">
            <a class="flex items-center gap-3 px-4 py-3 text-slate-500 hover:bg-slate-50 rounded-lg text-sm transition-all" href="index.php">
                <span class="material-symbols-outlined">dashboard</span> Overview
            </a>
            <a class="flex items-center gap-3 px-4 py-3 bg-primary-fixed text-primary rounded-lg font-bold text-sm" href="akademik.php">
                <span class="material-symbols-outlined">groups</span> Student Data
            </a>
        </nav>
    </aside>

    <main class="flex-1 lg:ml-64 p-8 w-full">
        <div class="flex justify-between items-end mb-8">
            <div>
                <h2 class="text-3xl font-bold text-primary mb-2">Student Data Management</h2>
                [cite_start]<p class="text-slate-500">Kelola data terpusat dan pantau historis akademik mahasiswa[cite: 1, 2].</p>
            </div>
            <button onclick="openCrudModal('add')" class="bg-primary text-white px-6 py-2.5 rounded-xl font-bold flex items-center gap-2 hover:opacity-90 shadow-lg transition-all">
                <span class="material-symbols-outlined text-[20px]">add</span> Tambah Data
            </button>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col md:flex-row gap-4 items-center mb-8">
            <div class="flex items-center gap-3 min-w-[280px] w-full md:w-auto">
                <select id="classFilter" onchange="filterAndShow()" class="w-full bg-slate-50 border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold text-primary focus:ring-primary focus:border-primary outline-none">
                    <option value="">-- Pilih Kelas Terlebih Dahulu --</option>
                    <option value="ALL">Semua Kelas</option>
                    <?php foreach($daftar_kelas as $kls): ?>
                        <option value="<?= htmlspecialchars($kls) ?>">Kelas <?= htmlspecialchars($kls) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="relative w-full">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                <input id="searchInput" onkeyup="filterAndShow()" type="text" class="w-full pl-12 pr-4 py-2.5 bg-slate-50 border-slate-200 rounded-xl text-sm outline-none focus:ring-1 focus:ring-primary" placeholder="Cari berdasarkan NIM atau Nama..."/>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left" id="studentTable">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-slate-500 text-xs font-bold uppercase tracking-wider select-none">
                            <th class="px-6 py-4 cursor-pointer hover:text-primary" onclick="sortTable(0)">NIM ↕</th>
                            <th class="px-6 py-4 cursor-pointer hover:text-primary" onclick="sortTable(1)">Nama Mahasiswa ↕</th>
                            <th class="px-6 py-4 cursor-pointer hover:text-primary" onclick="sortTable(2)">Angkatan ↕</th>
                            <th class="px-6 py-4 cursor-pointer hover:text-primary" onclick="sortTable(3)">Kelas ↕</th>
                            <th class="px-6 py-4 cursor-pointer hover:text-primary" onclick="sortTable(4)">IPK Terakhir ↕</th>
                            <th class="px-6 py-4 cursor-pointer hover:text-primary" onclick="sortTable(5)">Status ↕</th>
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <tr id="emptyState">
                            <td colspan="7" class="py-20 text-center text-slate-400">
                                <span class="material-symbols-outlined text-6xl mb-3 text-slate-200">ads_click</span>
                                <p class="font-medium">Silakan pilih kelas untuk menampilkan data.</p>
                            </td>
                        </tr>
                        <?php if (!empty($students)): foreach($students as $s): 
                            $initials = strtoupper(substr($s['nama_mahasiswa'], 0, 1) . substr(explode(' ', $s['nama_mahasiswa'])[1] ?? '', 0, 1));
                        ?>
                        <tr class="student-row hover:bg-slate-50 cursor-pointer transition-all" style="display: none;" 
                            data-kelas="<?= htmlspecialchars($s['kelas'] ?? '') ?>"
                            onclick="openHistoryModal(<?= $s['sk_mahasiswa'] ?>, '<?= addslashes($s['nama_mahasiswa']) ?>')">
                            <td class="px-6 py-4 font-mono font-medium text-slate-600"><?= $s['nim'] ?></td>
                            <td class="px-6 py-4 flex items-center gap-3">
                                <div class="h-9 w-9 rounded-full bg-blue-50 text-primary flex items-center justify-center font-bold text-xs border border-blue-100"><?= $initials ?></div>
                                <span class="font-bold text-slate-800"><?= htmlspecialchars($s['nama_mahasiswa']) ?></span>
                            </td>
                            <td class="px-6 py-4 text-slate-600"><?= $s['angkatan'] ?></td>
                            <td class="px-6 py-4"><span class="font-black text-primary"><?= htmlspecialchars($s['kelas'] ?? '-') ?></span></td>
                            <td class="px-6 py-4 font-bold text-emerald-600"><?= number_format((float)$s['ipk'], 2) ?></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-[11px] font-black uppercase <?= ($s['status_akademik'] == 'Aktif') ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                                    <?= htmlspecialchars($s['status_akademik']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center" onclick="event.stopPropagation();">
                                <div class="flex gap-2 justify-center">
                                    <button onclick="openCrudModal('edit', '<?= $s['nim'] ?>', '<?= addslashes($s['nama_mahasiswa']) ?>', '<?= $s['angkatan'] ?>', '<?= $s['kelas'] ?>')" class="p-2 text-primary hover:bg-primary-fixed rounded-lg transition-colors"><span class="material-symbols-outlined text-[20px]">edit</span></button>
                                    <button onclick="deleteStudent('<?= $s['nim'] ?>')" class="p-2 text-error hover:bg-red-50 rounded-lg transition-colors"><span class="material-symbols-outlined text-[20px]">delete</span></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<div id="historyModal" class="fixed inset-0 z-[2000] hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl overflow-hidden animate-in fade-in zoom-in duration-300">
        <div class="flex justify-between items-center p-8 border-b">
            <div>
                <h3 id="historyModalTitle" class="text-2xl font-bold text-primary">Data Historis</h3>
                [cite_start]<p class="text-sm text-slate-500">Perkembangan IPS dan IPK dari tabel Data Warehouse[cite: 16].</p>
            </div>
            <button onclick="closeModal('historyModal')" class="bg-slate-100 p-2 rounded-full hover:text-error transition-all"><span class="material-symbols-outlined">close</span></button>
        </div>
        <div class="p-8"><div class="h-[400px] w-full"><canvas id="historyChart"></canvas></div></div>
    </div>
</div>

<div id="crudModal" class="fixed inset-0 z-[2000] hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="p-6 border-b flex justify-between items-center">
            <h3 id="crudModalTitle" class="text-xl font-bold text-primary">Form Mahasiswa</h3>
            <button onclick="closeModal('crudModal')"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form id="crudForm" onsubmit="handleSave(event)" class="p-6 space-y-4">
            <input type="hidden" id="crudAction">
            <div><label class="text-xs font-bold text-slate-500 uppercase">Nomor Induk Mahasiswa</label>
                <input type="text" id="inNim" required class="w-full mt-1 border-slate-200 rounded-xl focus:ring-primary"></div>
            <div><label class="text-xs font-bold text-slate-500 uppercase">Nama Lengkap Mahasiswa</label>
                <input type="text" id="inNama" required class="w-full mt-1 border-slate-200 rounded-xl focus:ring-primary"></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="text-xs font-bold text-slate-500 uppercase">Angkatan</label>
                    <input type="number" id="inAngkatan" required class="w-full mt-1 border-slate-200 rounded-xl focus:ring-primary"></div>
                <div><label class="text-xs font-bold text-slate-500 uppercase">Kelas</label>
                    <input type="text" id="inKelas" required class="w-full mt-1 border-slate-200 rounded-xl focus:ring-primary"></div>
            </div>
            <div class="pt-4 flex gap-3">
                <button type="button" onclick="closeModal('crudModal')" class="flex-1 py-3 font-bold text-slate-500 bg-slate-50 rounded-xl">Batal</button>
                <button type="submit" class="flex-1 py-3 font-bold text-white bg-primary rounded-xl shadow-md">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<script>
    // 1. FILTER & SEARCH LOGIC
    function filterAndShow() {
        const q = document.getElementById("searchInput").value.toLowerCase();
        const kls = document.getElementById("classFilter").value;
        const rows = document.querySelectorAll(".student-row");
        const empty = document.getElementById("emptyState");
        let count = 0;

        rows.forEach(r => {
            const matchesKls = (kls === "ALL") || (r.dataset.kelas === kls);
            const matchesSearch = r.innerText.toLowerCase().includes(q);
            
            if (kls !== "" && matchesKls && matchesSearch) {
                r.style.display = ""; count++;
            } else { r.style.display = "none"; }
        });
        empty.style.display = (kls === "" || count === 0) ? "" : "none";
    }

    // 2. GENERAL SORTING LOGIC 
    let dir = false;
    function sortTable(n) {
        const tbody = document.querySelector("#studentTable tbody");
        const rows = Array.from(tbody.querySelectorAll(".student-row"));
        dir = !dir;
        rows.sort((a, b) => {
            let vA = a.cells[n].innerText.trim(), vB = b.cells[n].innerText.trim();
            if (!isNaN(vA) && !isNaN(vB)) { vA = parseFloat(vA); vB = parseFloat(vB); }
            return dir ? (vA > vB ? 1 : -1) : (vA < vB ? 1 : -1);
        });
        rows.forEach(r => tbody.appendChild(r));
    }

    // 3. POP-UP HISTORIS LOGIC 
    let chart = null;
    async function openHistoryModal(sk, nama) {
        document.getElementById('historyModal').classList.remove('hidden');
        document.getElementById('historyModalTitle').innerText = 'Riwayat Akademik: ' + nama;
        try {
            const res = await fetch('api_dw_tkj.php?type=chart_ipk_mhs&sk=' + sk, { headers: {'key':'TKJ-PNUP-2026-SECRET'} });
            const data = await res.json();
            const labels = data.results.map(i => i.tipe_semester + ' ' + i.tahun_ajaran);
            
            if (chart) chart.destroy();
            chart = new Chart(document.getElementById('historyChart'), {
                type: 'line',
                data: { labels, datasets: [
                    { label:'IPK Kumulatif', data:data.results.map(i=>i.ipk), borderColor:'#00236f', backgroundColor:'rgba(0,35,111,0.1)', fill:true, tension:0.4 },
                    { label:'IPS Semester', data:data.results.map(i=>i.ips), borderColor:'#006c49', borderDash:[5,5], tension:0.4 }
                ]},
                options: { responsive:true, maintainAspectRatio:false, scales:{y:{min:0, max:4}}, plugins:{tooltip:{mode:'index', intersect:false}} }
            });
        } catch (e) { console.error(e); }
    }

    // 4. CRUD FORM LOGIC [cite: 12]
    function openCrudModal(act, nim='', nama='', ang='', kls='') {
        document.getElementById('crudModal').classList.remove('hidden');
        document.getElementById('crudAction').value = act;
        document.getElementById('inNim').value = nim; document.getElementById('inNama').value = nama;
        document.getElementById('inAngkatan').value = ang; document.getElementById('inKelas').value = kls;
        document.getElementById('inNim').readOnly = (act === 'edit');
        document.getElementById('crudModalTitle').innerText = (act === 'edit') ? 'Update Data Mahasiswa' : 'Tambah Mahasiswa';
    }

    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
    function handleSave(e) { e.preventDefault(); alert('Request SIMPAN dikirim ke Backend.'); closeModal('crudModal'); }
    function deleteStudent(n) { if(confirm('Hapus data NIM '+n+'?')) alert('Request DELETE dikirim.'); }
</script>
</body>
</html>