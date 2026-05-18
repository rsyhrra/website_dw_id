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
                    "background": "#f3f4f9", 
                    "surface": "#ffffff",    
                    "primary": "#6366f1",    
                    "accent-pink": "#ec4899",
                    "text-main": "#1e293b",  
                    "text-muted": "#64748b", 
                },
                fontFamily: {
                    sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
                },
                boxShadow: {
                    'premium': '0 8px 30px rgba(0, 0, 0, 0.02)',
                    'purple-glow': '0 10px 25px -5px rgba(99, 102, 241, 0.35)',
                    'card-shadow': '0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02)',
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
            background-color: #f3f4f9; 
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }
    </style>
</head>
<body class="bg-background text-text-main min-h-screen flex items-center justify-center relative overflow-hidden p-4">

<!-- Beautiful colorful gradient blobs in the background -->
<div class="absolute -left-20 -bottom-20 w-96 h-96 rounded-full bg-indigo-200/50 blur-3xl"></div>
<div class="absolute -right-20 -top-20 w-96 h-96 rounded-full bg-pink-200/50 blur-3xl"></div>
<div class="absolute left-1/3 top-1/4 w-72 h-72 rounded-full bg-purple-200/30 blur-3xl animate-pulse" style="animation-duration: 8s;"></div>

<!-- Main Login Card Container -->
<div class="w-full max-w-md z-10 transition-all duration-300">
    <!-- Brand Logo header -->
    <div class="flex flex-col items-center gap-3 mb-8">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-pink-500 via-purple-500 to-indigo-500 flex items-center justify-center shadow-lg">
            <span class="material-symbols-outlined text-white text-3xl font-bold">school</span>
        </div>
        <h2 class="text-xl font-extrabold text-slate-800 tracking-tight">TKJ PNUP Academic</h2>
        <p class="text-xs font-semibold text-text-muted">Academic Data & Analytics Portal</p>
    </div>

    <!-- Glassmorphic Login Card -->
    <div class="glass-card rounded-[2.5rem] p-8 md:p-10 shadow-card-shadow">
        <div class="mb-8">
            <h3 class="text-2xl font-extrabold text-slate-800">Selamat Datang</h3>
            <p class="text-xs font-bold text-text-muted mt-1.5">Masukkan username dan password admin Anda</p>
        </div>

        <?php if($error): ?>
        <div class="bg-rose-50 border border-rose-100 text-rose-600 rounded-2xl p-4 mb-6 text-xs font-bold flex items-center gap-2 transition-all">
            <span class="material-symbols-outlined text-[18px]">error</span>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="flex flex-col gap-5">
            <div>
                <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-wider mb-2">Username</label>
                <div class="relative flex items-center">
                    <input type="text" name="username" required placeholder="admin"
                           class="w-full bg-slate-50/70 border-0 rounded-2xl py-3.5 px-5 pl-12 text-xs font-semibold focus:ring-2 focus:ring-primary focus:bg-white text-slate-700 outline-none transition-all placeholder:text-slate-400"/>
                    <span class="material-symbols-outlined text-slate-400 absolute left-4 text-[18px]">person</span>
                </div>
            </div>

            <div>
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-wider">Password</label>
                    <a href="#" class="text-[10px] font-bold text-primary hover:underline">Lupa Password?</a>
                </div>
                <div class="relative flex items-center">
                    <input type="password" name="password" required placeholder="••••••••"
                           class="w-full bg-slate-50/70 border-0 rounded-2xl py-3.5 px-5 pl-12 text-xs font-semibold focus:ring-2 focus:ring-primary focus:bg-white text-slate-700 outline-none transition-all placeholder:text-slate-400"/>
                    <span class="material-symbols-outlined text-slate-400 absolute left-4 text-[18px]">lock</span>
                </div>
            </div>

            <div class="flex items-center gap-2 mt-2">
                <input type="checkbox" id="remember" name="remember" class="w-4 h-4 rounded text-primary focus:ring-primary border-slate-300">
                <label for="remember" class="text-xs font-semibold text-slate-500 cursor-pointer">Ingat saya di perangkat ini</label>
            </div>

            <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white rounded-2xl py-3.5 px-6 mt-4 shadow-purple-glow font-bold text-xs flex items-center justify-center gap-1.5 transition-all">
                <span class="material-symbols-outlined text-[16px]">login</span> Masuk Ke Portal
            </button>
        </form>
    </div>

    <!-- Small Footer -->
    <div class="text-center mt-8 text-[10px] font-semibold text-text-muted">
        <p>&copy; 2026 Teknik Komputer dan Jaringan PNUP. All rights reserved.</p>
    </div>
</div>

</body>
</html>
