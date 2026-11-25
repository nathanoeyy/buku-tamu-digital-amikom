<?php
require_once __DIR__ . '/config/database.php';

if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
    if ($_SESSION['role'] === 'dosen') {
        header('Location: dosen/dashboard.php');
        exit;
    }

    if ($_SESSION['role'] === 'mahasiswa') {
        header('Location: mahasiswa/dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Peran — Buku Tamu Digital Amikom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        amikom: {
                            50: '#faf5ff',
                            100: '#f3e8ff',
                            500: '#9333ea',
                            600: '#7e22ce',
                            700: '#6b21a8',
                            800: '#581c87',
                            900: '#3b0764',
                        }
                    }
                }
            }
        };
    </script>
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 transition-colors duration-200 min-h-screen flex flex-col justify-between">

    <!-- Header Ringkas -->
    <header class="bg-white/95 dark:bg-slate-900/95 border-b border-purple-100 dark:border-slate-800">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="index.php" class="text-sm font-bold text-amikom-900 dark:text-purple-200 hover:text-amikom-700 transition">
                ← Kembali ke Beranda
            </a>
            <button
                type="button"
                id="themeToggleBtn"
                class="px-3 py-1.5 rounded-lg text-xs font-medium border border-purple-200 dark:border-slate-700 text-amikom-800 dark:text-purple-200 hover:bg-purple-50 dark:hover:bg-slate-800 transition"
            >
                Mode: <span id="themeLabel">Terang</span>
            </button>
        </div>
    </header>

    <!-- Konten Utama Pilih Peran -->
    <main class="max-w-4xl w-full mx-auto px-4 sm:px-6 py-10">
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-purple-100 dark:border-slate-800 p-6 sm:p-10 shadow-sm space-y-8">
            <div class="text-center space-y-2 max-w-xl mx-auto">
                <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-amikom-800 dark:bg-purple-950 dark:text-purple-200">
                    Universitas Amikom Yogyakarta
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">
                    BUKU TAMU DIGITAL
                </h1>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Silakan pilih peran akun untuk masuk ke sistem layanan kunjungan dan jadwal dosen.
                </p>
            </div>

            <!-- Pilihan Dosen Pengajar vs Mahasiswa -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <a
                    href="login.php?role=dosen"
                    class="group rounded-2xl border-2 border-purple-200 dark:border-slate-800 hover:border-amikom-700 bg-slate-50/70 dark:bg-slate-950/60 p-6 flex flex-col justify-between gap-5 transition shadow-xs hover:shadow-md"
                >
                    <div class="space-y-2">
                        <div class="w-11 h-11 rounded-xl bg-amikom-700 text-white font-bold flex items-center justify-center text-sm">
                            DSN
                        </div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-amikom-700 dark:group-hover:text-purple-300 transition">
                            DOSEN PENGAJAR
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                            Masuk menggunakan NIDN untuk memperbarui status kehadiran di kampus, jadwal ketersediaan, dan antrean mahasiswa.
                        </p>
                    </div>
                    <span class="inline-flex items-center justify-center w-full py-2.5 px-4 rounded-xl text-sm font-semibold bg-amikom-700 group-hover:bg-amikom-800 text-white transition">
                        Masuk sebagai Dosen
                    </span>
                </a>

                <a
                    href="login.php?role=mahasiswa"
                    class="group rounded-2xl border-2 border-purple-200 dark:border-slate-800 hover:border-amikom-700 bg-slate-50/70 dark:bg-slate-950/60 p-6 flex flex-col justify-between gap-5 transition shadow-xs hover:shadow-md"
                >
                    <div class="space-y-2">
                        <div class="w-11 h-11 rounded-xl bg-purple-100 dark:bg-purple-950 text-amikom-800 dark:text-purple-200 font-bold flex items-center justify-center text-sm">
                            MHS
                        </div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-amikom-700 dark:group-hover:text-purple-300 transition">
                            MAHASISWA
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                            Masuk menggunakan NIM untuk melihat jadwal dosen, membuat janji temu, mengambil nomor antrean, dan tanda tangan digital.
                        </p>
                    </div>
                    <span class="inline-flex items-center justify-center w-full py-2.5 px-4 rounded-xl text-sm font-semibold border border-amikom-700 text-amikom-800 dark:text-purple-200 group-hover:bg-amikom-700 group-hover:text-white transition">
                        Masuk sebagai Mahasiswa
                    </span>
                </a>
            </div>
        </div>
    </main>

    <!-- Footer Ringkas -->
    <footer class="py-5 text-center text-xs text-slate-500 dark:text-slate-400">
        Privacy Policy • Terms &amp; Conditions • Universitas Amikom Yogyakarta
    </footer>

    <script>
        (function () {
            const root = document.documentElement;
            const btn = document.getElementById('themeToggleBtn');
            const label = document.getElementById('themeLabel');

            if (localStorage.getItem('bt_theme') === 'dark') {
                root.classList.add('dark');
                label.textContent = 'Gelap';
            }

            btn.addEventListener('click', function () {
                const isDark = root.classList.toggle('dark');
                localStorage.setItem('bt_theme', isDark ? 'dark' : 'light');
                label.textContent = isDark ? 'Gelap' : 'Terang';
            });
        })();
    </script>
</body>
</html>