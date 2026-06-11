<?php
// File: register.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'config.php';

// Redireksi jika sudah login
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Semua field wajib diisi!';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok!';
    } elseif (strlen($password) < 5) {
        $error = 'Password minimal 5 karakter!';
    } else {
        // Cek apakah username sudah terdaftar
        $stmt = $conn->prepare("SELECT id_user FROM admin WHERE nama = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $error = 'Username sudah terdaftar!';
            } else {
                $stmt->close();
                
                // Buat API Key default untuk user baru
                $new_key = "TKJ-PNUP-" . strtoupper(bin2hex(random_bytes(10)));
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                // Simpan ke database
                $stmt_insert = $conn->prepare("INSERT INTO admin (nama, password, key_token) VALUES (?, ?, ?)");
                if ($stmt_insert) {
                    $stmt_insert->bind_param("sss", $username, $hashed_password, $new_key);
                    if ($stmt_insert->execute()) {
                        $success = 'Registrasi berhasil! Silakan login.';
                    } else {
                        $error = 'Gagal menyimpan ke database.';
                    }
                    $stmt_insert->close();
                } else {
                    $error = 'Gagal mempersiapkan statement pendaftaran.';
                }
            }
        } else {
            $error = 'Gagal mempersiapkan statement verifikasi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Registrasi – Portal Akademik TKJ</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    "primary": "#6366f1",    
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
        body { 
            font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; 
            background: radial-gradient(circle at 50% 0%, #1e1b4b 0%, #0f172a 100%);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 24px 60px -15px rgba(0, 0, 0, 0.5);
        }
        .glass-input {
            background: rgba(15, 23, 42, 0.55) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #e2e8f0 !important;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            transition: all 0.3s ease;
        }
        .glass-input:focus {
            background: rgba(15, 23, 42, 0.7) !important;
            border-color: rgba(99, 102, 241, 0.55) !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
            color: #e2e8f0 !important;
        }
        .glass-input::placeholder {
            color: #64748b !important;
            opacity: 1;
        }
        .glass-btn {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.8), rgba(236, 72, 153, 0.8));
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #ffffff;
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.2);
            transition: all 0.3s ease;
        }
        .glass-btn:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.95), rgba(236, 72, 153, 0.95));
            box-shadow: 0 12px 32px rgba(99, 102, 241, 0.35);
            transform: translateY(-1px);
        }
        .glass-btn:active {
            transform: translateY(1px);
        }

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
<body class="text-text-main min-h-screen flex items-center justify-center relative p-4 select-none overflow-hidden">

<!-- Background Glow Blobs -->
<div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
    <div class="absolute top-[10%] left-[20%] w-[300px] h-[300px] rounded-full bg-indigo-600/20 blur-[80px]"></div>
    <div class="absolute bottom-[20%] right-[20%] w-[350px] h-[350px] rounded-full bg-pink-600/15 blur-[90px]"></div>
</div>

<!-- Preloader -->
<div id="preloader">
    <div class="spinner"></div>
    <p class="mt-4 text-xs font-bold text-slate-400">Memuat Antarmuka...</p>
</div>

<!-- Main Card Container -->
<div class="w-full max-w-md z-10 transition-all duration-300">
    <!-- Brand Logo header -->
    <div class="flex flex-col items-center gap-3 mb-8">
        <div class="w-16 h-16 rounded-2xl glass-card flex items-center justify-center text-primary">
            <span class="material-symbols-outlined text-4xl font-bold">school</span>
        </div>
        <h2 class="text-xl font-extrabold text-slate-100 tracking-tight mt-1">TKJ PNUP Academic</h2>
        <p class="text-[10px] font-bold text-text-muted uppercase tracking-wider">Academic Data & Analytics Portal</p>
    </div>

    <!-- Glassmorphic Card -->
    <div class="glass-card rounded-[2.5rem] p-8 md:p-10">
        <div class="mb-8">
            <h3 class="text-2xl font-extrabold text-slate-100">Daftar Akun Baru</h3>
            <p class="text-xs font-bold text-text-muted mt-1.5">Buat kredensial admin baru untuk masuk ke portal</p>
        </div>

        <?php if($error): ?>
        <div class="bg-red-500/10 border border-red-500/20 text-red-400 rounded-2xl p-4 mb-6 text-xs font-bold flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">error</span>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if($success): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-2xl p-4 mb-6 text-xs font-bold flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">check_circle</span>
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="flex flex-col gap-6">
            <div>
                <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-2.5">Username</label>
                <div class="relative flex items-center">
                    <input type="text" name="username" required placeholder="admin"
                           class="w-full border-0 rounded-2xl py-3.5 px-5 pl-12 text-xs font-semibold focus:ring-0 outline-none transition-all placeholder:text-slate-500 glass-input"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"/>
                    <span class="material-symbols-outlined text-slate-500 absolute left-4 text-[18px]">person</span>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-2.5">Password</label>
                <div class="relative flex items-center">
                    <input type="password" id="password" name="password" required placeholder="••••••••"
                           class="w-full border-0 rounded-2xl py-3.5 px-5 pl-12 pr-12 text-xs font-semibold focus:ring-0 outline-none transition-all placeholder:text-slate-500 glass-input"/>
                    <span class="material-symbols-outlined text-slate-500 absolute left-4 text-[18px]">lock</span>
                    <button type="button" onclick="togglePasswordVisibility('password', 'eyeIconPassword')" class="absolute right-4 text-slate-500 hover:text-slate-300 focus:outline-none flex items-center">
                        <span id="eyeIconPassword" class="material-symbols-outlined text-[18px]">visibility</span>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-2.5">Konfirmasi Password</label>
                <div class="relative flex items-center">
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••"
                           class="w-full border-0 rounded-2xl py-3.5 px-5 pl-12 pr-12 text-xs font-semibold focus:ring-0 outline-none transition-all placeholder:text-slate-500 glass-input"/>
                    <span class="material-symbols-outlined text-slate-500 absolute left-4 text-[18px]">lock</span>
                    <button type="button" onclick="togglePasswordVisibility('confirm_password', 'eyeIconConfirm')" class="absolute right-4 text-slate-500 hover:text-slate-300 focus:outline-none flex items-center">
                        <span id="eyeIconConfirm" class="material-symbols-outlined text-[18px]">visibility</span>
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full rounded-2xl py-4 mt-4 font-extrabold text-xs flex items-center justify-center gap-1.5 glass-btn uppercase tracking-wider">
                <span class="material-symbols-outlined text-[16px]">how_to_reg</span> Registrasi Akun
            </button>
        </form>

        <div class="mt-8 text-center">
            <p class="text-xs text-text-muted font-semibold">
                Sudah memiliki akun? 
                <a href="login.php" class="text-primary hover:underline font-bold ml-1">Masuk disini</a>
            </p>
        </div>
    </div>
</div>

<script>
// Hide preloader
window.addEventListener('load', () => {
    setTimeout(() => {
        const pre = document.getElementById('preloader');
        if(pre) {
            pre.style.opacity = '0';
            setTimeout(() => pre.style.display = 'none', 500);
        }
    }, 500);
});

// Toggle password visibility
function togglePasswordVisibility(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = 'visibility_off';
    } else {
        input.type = 'password';
        icon.textContent = 'visibility';
    }
}
</script>
</body>
</html>
