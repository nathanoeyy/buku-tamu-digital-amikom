<?php
require_once __DIR__ . '/config/database.php';

$roleParam = $_GET['role'] ?? 'mahasiswa';
$role = in_array($roleParam, ['dosen', 'mahasiswa'], true) ? $roleParam : 'mahasiswa';

$error = '';
$success = '';
$namaInput = '';
$nomorIndukInput = '';
$emailInput = '';
$prodiInput = 'D3 Teknik Informatika';
$ruanganInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rolePost = $_POST['role'] ?? 'mahasiswa';
    $role = in_array($rolePost, ['dosen', 'mahasiswa'], true) ? $rolePost : 'mahasiswa';

    $namaInput = trim($_POST['nama_lengkap'] ?? '');
    $nomorIndukInput = trim($_POST['nomor_induk'] ?? '');
    $emailInput = trim($_POST['email'] ?? '');
    $prodiInput = trim($_POST['prodi'] ?? 'D3 Teknik Informatika');
    $ruanganInput = trim($_POST['ruangan'] ?? '');
    $passwordInput = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($namaInput === '' || $nomorIndukInput === '' || $emailInput === '' || $passwordInput === '') {
        $error = 'Semua field wajib diisi.';
    } elseif (strlen($passwordInput) < 6) {
        $error = 'Kata sandi minimal 6 karakter.';
    } elseif ($passwordInput !== $confirmPassword) {
        $error = 'Konfirmasi kata sandi tidak sama.';
    } else {
        $cekStmt = $pdo->prepare("SELECT id FROM users WHERE nomor_induk = :nim_nidn OR email = :email LIMIT 1");
        $cekStmt->execute([
            ':nim_nidn' => $nomorIndukInput,
            ':email' => $emailInput,
        ]);

        if ($cekStmt->fetch()) {
            $error = ($role === 'dosen' ? 'NIDN' : 'NIM') . ' atau email sudah terdaftar.';
        } else {
            $insertStmt = $pdo->prepare("
                INSERT INTO users (role, nomor_induk, nama_lengkap, email, password, prodi, ruangan, status_kehadiran)
                VALUES (:role, :nomor_induk, :nama_lengkap, :email, :password, :prodi, :ruangan, 'tersedia')
            ");
            $insertStmt->execute([
                ':role' => $role,
                ':nomor_induk' => $nomorIndukInput,
                ':nama_lengkap' => $namaInput,
                ':email' => $emailInput,
                ':password' => password_hash($passwordInput, PASSWORD_DEFAULT),
                ':prodi' => $prodiInput !== '' ? $prodiInput : 'D3 Teknik Informatika',
                ':ruangan' => $role === 'dosen' ? ($ruanganInput !== '' ? $ruanganInput : 'Ruang Dosen Kampus') : null,
            ]);

            $success = 'Akun berhasil dibuat! Silakan login untuk melanjutkan.';
            $namaInput = '';
            $nomorIndukInput = '';
            $emailInput = '';
            $ruanganInput = '';
        }
    }
}

$idLabel = $role === 'dosen' ? 'NIDN' : 'NIM';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun — Buku Tamu Digital Amikom</title>
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

    <header class="bg-white/95 dark:bg-slate-900/95 border-b border-purple-100 dark:border-slate-800">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="login.php?role=<?= htmlspecialchars($role) ?>" class="text-sm font-bold text-amikom-900 dark:text-purple-200 hover:text-amikom-700 transition">
                ← Kembali ke Login
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

    <main class="max-w-md w-full mx-auto px-4 py-10">
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-purple-100 dark:border-slate-800 p-6 sm:p-8 shadow-sm space-y-6">
            <div class="text-center space-y-1">
                <p class="text-xs font-bold uppercase tracking-wider text-amikom-700 dark:text-purple-300">
                    Universitas Amikom Yogyakarta
                </p>
                <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white">
                    Create an Account
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Pendaftaran peran: <span class="font-semibold uppercase"><?= htmlspecialchars($role) ?></span>
                </p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-medium">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium space-y-2">
                    <p><?= htmlspecialchars($success) ?></p>
                    <a href="login.php?role=<?= htmlspecialchars($role) ?>" class="inline-block font-bold underline">
                        Lanjut ke Halaman Login →
                    </a>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php?role=<?= htmlspecialchars($role) ?>" class="space-y-3.5">
                <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">

                <div>
                    <label for="nama_lengkap" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Full Name
                    </label>
                    <input
                        id="nama_lengkap"
                        type="text"
                        name="nama_lengkap"
                        value="<?= htmlspecialchars($namaInput) ?>"
                        required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-sm focus:outline-none focus:ring-2 focus:ring-amikom-600"
                    >
                </div>

                <div>
                    <label for="nomor_induk" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        <?= htmlspecialchars($idLabel) ?>
                    </label>
                    <input
                        id="nomor_induk"
                        type="text"
                        name="nomor_induk"
                        value="<?= htmlspecialchars($nomorIndukInput) ?>"
                        required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-sm focus:outline-none focus:ring-2 focus:ring-amikom-600"
                    >
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Email
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="<?= htmlspecialchars($emailInput) ?>"
                        required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-sm focus:outline-none focus:ring-2 focus:ring-amikom-600"
                    >
                </div>

                <div>
                    <label for="prodi" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Program Studi
                    </label>
                    <input
                        id="prodi"
                        type="text"
                        name="prodi"
                        value="<?= htmlspecialchars($prodiInput) ?>"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-sm focus:outline-none focus:ring-2 focus:ring-amikom-600"
                    >
                </div>

                <?php if ($role === 'dosen'): ?>
                    <div>
                        <label for="ruangan" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Ruangan / Gedung Kampus
                        </label>
                        <input
                            id="ruangan"
                            type="text"
                            name="ruangan"
                            value="<?= htmlspecialchars($ruanganInput) ?>"
                            placeholder="Contoh: Ruang Dosen Gedung 5 Lt. 2"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-sm focus:outline-none focus:ring-2 focus:ring-amikom-600"
                        >
                    </div>
                <?php endif; ?>

                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Password
                    </label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-sm focus:outline-none focus:ring-2 focus:ring-amikom-600"
                    >
                </div>

                <div>
                    <label for="confirm_password" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Konfirmasi Password
                    </label>
                    <input
                        id="confirm_password"
                        type="password"
                        name="confirm_password"
                        required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-sm focus:outline-none focus:ring-2 focus:ring-amikom-600"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full py-2.5 px-4 rounded-xl text-sm font-semibold bg-amikom-700 hover:bg-amikom-800 text-white shadow-sm transition"
                >
                    Continue
                </button>
            </form>

            <div class="pt-2 border-t border-slate-100 dark:border-slate-800 text-center text-xs text-slate-600 dark:text-slate-400">
                Have an Account?
                <a href="login.php?role=<?= htmlspecialchars($role) ?>" class="font-bold text-amikom-700 dark:text-purple-300 hover:underline">
                    Login
                </a>
            </div>
        </div>
    </main>

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