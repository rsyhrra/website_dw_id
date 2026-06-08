<?php
// File: login.php
session_start();
require_once 'config.php';

// Redireksi jika sudah login
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Kredensial default: admin / admin
    if ($username === 'admin' && $password === 'admin') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['username'] = $username;
        header("Location: index.php");
        exit();
    } else {
        $error = 'Username atau Password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Login – Portal Akademik TKJ</title>
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
        /* Fix browser autofill override */
        .glass-input:-webkit-autofill,
        .glass-input:-webkit-autofill:hover,
        .glass-input:-webkit-autofill:focus {
            -webkit-text-fill-color: #e2e8f0 !important;
            -webkit-box-shadow: 0 0 0px 1000px rgba(15, 23, 42, 0.7) inset !important;
            transition: background-color 5000s ease-in-out 0s;
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

<!-- Main Login Card Container -->
<div class="w-full max-w-md z-10 transition-all duration-300">
    <!-- Brand Logo header -->
    <div class="flex flex-col items-center gap-3 mb-8">
        <div class="w-16 h-16 rounded-2xl glass-card flex items-center justify-center text-primary">
            <span class="material-symbols-outlined text-4xl font-bold">school</span>
        </div>
        <h2 class="text-xl font-extrabold text-slate-100 tracking-tight mt-1">TKJ PNUP Academic</h2>
        <p class="text-[10px] font-bold text-text-muted uppercase tracking-wider">Academic Data & Analytics Portal</p>
    </div>

    <!-- Glassmorphic Login Card -->
    <div class="glass-card rounded-[2.5rem] p-8 md:p-10">
        <div class="mb-8">
            <h3 class="text-2xl font-extrabold text-slate-100">Selamat Datang</h3>
            <p class="text-xs font-bold text-text-muted mt-1.5">Masukkan username dan password admin Anda</p>
        </div>

        <?php if($error): ?>
        <div class="bg-red-500/10 border border-red-500/20 text-red-400 rounded-2xl p-4 mb-6 text-xs font-bold flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">error</span>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="flex flex-col gap-6">
            <div>
                <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-2.5">Username</label>
                <div class="relative flex items-center">
                    <input type="text" name="username" required placeholder="admin"
                           class="w-full border-0 rounded-2xl py-3.5 px-5 pl-12 text-xs font-semibold focus:ring-0 outline-none transition-all placeholder:text-slate-500 glass-input"/>
                    <span class="material-symbols-outlined text-slate-500 absolute left-4 text-[18px]">person</span>
                </div>
            </div>

            <div>
                <div class="flex justify-between items-center mb-2.5">
                    <label class="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Password</label>
                    <a href="#" class="text-[10px] font-bold text-primary hover:underline">Lupa Password?</a>
                </div>
                <div class="relative flex items-center">
                    <input type="password" name="password" required placeholder="••••••••"
                           class="w-full border-0 rounded-2xl py-3.5 px-5 pl-12 text-xs font-semibold focus:ring-0 outline-none transition-all placeholder:text-slate-500 glass-input"/>
                    <span class="material-symbols-outlined text-slate-500 absolute left-4 text-[18px]">lock</span>
                </div>
            </div>

            <div class="flex items-center gap-2 mt-2">
                <input type="checkbox" id="remember" name="remember" class="w-4 h-4 rounded text-primary focus:ring-0 border-0 bg-slate-900/40 glass-input">
                <label for="remember" class="text-xs font-semibold text-slate-400 cursor-pointer">Ingat saya di perangkat ini</label>
            </div>

            <button type="submit" class="w-full rounded-2xl py-4 mt-4 font-extrabold text-xs flex items-center justify-center gap-1.5 glass-btn uppercase tracking-wider">
                <span class="material-symbols-outlined text-[16px]">login</span> Masuk Ke Portal
            </button>
        </form>
    </div>

    <!-- Small Footer -->
    <div class="text-center mt-10 text-[10px] font-bold text-text-muted">
        <p>&copy; 2026 Teknik Komputer dan Jaringan PNUP. All rights reserved.</p>
    </div>
</div>

<script>
window.addEventListener('load', () => {
    setTimeout(() => {
        const pre = document.getElementById('preloader');
        if(pre) {
            pre.style.opacity = '0';
            setTimeout(() => pre.style.display = 'none', 500);
        }
    }, 500);
});
</script>
</body>
</html>
