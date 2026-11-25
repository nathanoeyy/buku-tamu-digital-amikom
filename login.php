<?php
require_once __DIR__ . '/config/database.php';

$roleParam = $_GET['role'] ?? 'mahasiswa';
$role = in_array($roleParam, ['dosen', 'mahasiswa'], true) ? $roleParam : 'mahasiswa';

$error = '';
$nomorIndukInput = '';
$emailInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rolePost = $_POST['role'] ?? 'mahasiswa';
    $role = in_array($rolePost, ['dosen', 'mahasiswa'], true) ? $rolePost : 'mahasiswa';

    $nomorIndukInput = trim($_POST['nomor_induk'] ?? '');
    $emailInput = trim($_POST['email'] ?? '');
    $passwordInput = $_POST['password'] ?? '';

    if ($nomorIndukInput === '' || $emailInput === '' || $passwordInput === '') {
        $error = 'Lengkapi' . ($role === 'dosen' ? 'NIDN' : 'NIM') . ', email, dan kata sandi terlebih dahulul.';
    } else {
        $stmt = $pdo->prepare("
            SELECT id, role, nomor_induk, nama_lengkap, email, password, prodi, ruangan, status_kehadiran
            FROM users
            WHERE role = :role AND nomor_induk = :nomor_induk AND email = :email
            LIMIT 1
        ");
        $stmt->execute([
            ':role' => $role,
            ':nomor_induk' => $nomorIndukInput,
            ':email' => $emailInput,
        ]);
        $user = $stmt->fetch();

        if ($user && password_verify($passwordInput, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nomor_induk'] = $user['nomor_induk'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['prodi'] = $user['prodi'];

            if ($user['role'] === 'dosen') {
                header('Location: dosen/dashboard.php');
                exit;
            }
            header('Location: mahasiswa/dashboard.php');
            exit;
        }

        $error = 'Data login tidak cocok. Periksa kembali' . ($role === 'dosen' ? 'NIDN' : 'NIM') . ', email, dan kata sandi.';
    }
}

$idLabel = $role === 'dosen' ? 'NIDN' : 'NIM';
$judulRole = $role === 'dosen' ? 'Login Dosen Pengajar' : 'Login Mahasiswa';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($judulRole) ?> — Buku Tamu Digital Amikom</title>
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
            <a href="pilih-peran.php" class="text-sm font-bold text-amikom-900 dark:text-purple-200 hover:text-amikom-700 transition">
                ← Ganti Peran
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
                    <?= htmlspecialchars($judulRole) ?>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Akun uji coba password: <span class="font-semibold">password123</span>
                </p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-medium">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php?role=<?= htmlspecialchars($role) ?>" class="space-y-4">
                <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">

                <div>
                    <label for="nomor_induk" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Masukkan <?= htmlspecialchars($idLabel) ?>
                    </label>
                    <input
                        id="nomor_induk"
                        type="text"
                        name="nomor_induk"
                        value="<?= htmlspecialchars($nomorIndukInput) ?>"
                        placeholder="<?= $role === 'dosen' ? 'Contoh: 0512048801' : 'Contoh: 25.01.5250' ?>"
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
                        placeholder="<?= $role === 'dosen' ? 'nama@amikom.ac.id' : 'nama@students.amikom.ac.id' ?>"
                        required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-sm focus:outline-none focus:ring-2 focus:ring-amikom-600"
                    >
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Password
                    </label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Masukkan kata sandi"
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

            <div class="pt-2 border-t border-slate-100 dark:border-slate-800 text-center text-xs text-slate-600 dark:text-slate-400 space-y-2">
                <p>
                    Belum punya akun?
                    <a href="register.php?role=<?= htmlspecialchars($role) ?>" class="font-bold text-amikom-700 dark:text-purple-300 hover:underline">
                        Daftar Akun Baru
                    </a>
                </p>
                <p>
                    <a href="index.php" class="hover:text-amikom-700">Kembali ke Halaman Utama</a>
                </p>
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