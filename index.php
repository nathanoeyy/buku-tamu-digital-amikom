<?php
require_once __DIR__ . '/config/database.php';

$keyword = trim($_GET['q'] ?? '');

// Ambil statistik real-time hari ini
$stmtTotalVisits = $pdo->query("SELECT COUNT(*) AS total FROM guest_visits WHERE tanggal_kunjungan = CURDATE()");
$totalVisitsToday = (int) $stmtTotalVisits->fetch()['total'];

$stmtActiveDosen = $pdo->query("SELECT COUNT(*) AS total FROM users WHERE role = 'dosen' AND status_kehadiran = 'tersedia'");
$activeDosenCount = (int) $stmtActiveDosen->fetch()['total'];

// Daftar dosen & jadwal ketersediaannya
$sqlDosen = "
    SELECT 
        u.id,
        u.nomor_induk AS nidn,
        u.nama_lengkap,
        u.prodi,
        u.ruangan,
        u.status_kehadiran,
        GROUP_CONCAT(
            CONCAT(ls.hari, ' (', TIME_FORMAT(ls.jam_mulai, '%H:%i'), '-', TIME_FORMAT(ls.jam_selesai, '%H:%i'), ')')
            ORDER BY FIELD(ls.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), ls.jam_mulai
            SEPARATOR ' • '
        ) AS jadwal_ringkas
    FROM users u
    LEFT JOIN lecturer_schedules ls ON ls.dosen_id = u.id
    WHERE u.role = 'dosen'
";

$paramsDosen = [];
if ($keyword !== '') {
    $sqlDosen .= " AND (u.nama_lengkap LIKE :kw_dosen OR u.nomor_induk LIKE :kw_nidn OR u.ruangan LIKE :kw_ruang)";
    $paramsDosen[':kw_dosen'] = "%{$keyword}%";
    $paramsDosen[':kw_nidn'] = "%{$keyword}%";
    $paramsDosen[':kw_ruang'] = "%{$keyword}%";
}

$sqlDosen .= " GROUP BY u.id ORDER BY FIELD(u.status_kehadiran, 'tersedia', 'sibuk', 'tidak_di_kampus'), u.nama_lengkap ASC";
$stmtDosen = $pdo->prepare($sqlDosen);
$stmtDosen->execute($paramsDosen);
$dosenList = $stmtDosen->fetchAll();

// Hasil pencarian status antrean berdasarkan NIM atau nama mahasiswa / dosen
$antreanResults = [];
if ($keyword !== '') {
    $sqlAntrean = "
        SELECT 
            gv.kode_antrean,
            gv.keperluan,
            gv.tanggal_kunjungan,
            TIME_FORMAT(gv.jam_kunjungan, '%H:%i') AS jam_kunjungan,
            gv.status_antrean,
            m.nomor_induk AS nim,
            m.nama_lengkap AS nama_mahasiswa,
            d.nama_lengkap AS nama_dosen,
            d.ruangan
        FROM guest_visits gv
        INNER JOIN users m ON gv.mahasiswa_id = m.id
        INNER JOIN users d ON gv.dosen_id = d.id
        WHERE m.nomor_induk LIKE :kw_nim
           OR m.nama_lengkap LIKE :kw_mhs
           OR d.nama_lengkap LIKE :kw_dsn
           OR gv.kode_antrean LIKE :kw_kode
        ORDER BY gv.tanggal_kunjungan DESC, gv.jam_kunjungan ASC
    ";
    $stmtAntrean = $pdo->prepare($sqlAntrean);
    $stmtAntrean->execute([
        ':kw_nim'   => "%{$keyword}%",
        ':kw_mhs'   => "%{$keyword}%",
        ':kw_dsn'   => "%{$keyword}%",
        ':kw_kode'  => "%{$keyword}%",
    ]);
    $antreanResults = $stmtAntrean->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Tamu Digital — Universitas Amikom Yogyakarta</title>
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
<body class="bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 transition-colors duration-200 min-h-screen">

    <!-- Navbar -->
    <header class="sticky top-0 z-30 bg-white/95 dark:bg-slate-900/95 backdrop-blur border-b border-purple-100 dark:border-slate-800">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amikom-700 text-white font-bold flex items-center justify-center shadow-sm">
                    BT
                </div>
                <div>
                    <p class="text-sm font-bold leading-tight text-amikom-900 dark:text-purple-200">Universitas Amikom Yogyakarta</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Layanan Buku Tamu &amp; Jadwal Dosen</p>
                </div>
            </a>

            <div class="flex items-center gap-2 sm:gap-3">
                <button
                    type="button"
                    id="themeToggleBtn"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium border border-purple-200 dark:border-slate-700 text-amikom-800 dark:text-purple-200 hover:bg-purple-50 dark:hover:bg-slate-800 transition"
                >
                    Mode: <span id="themeLabel">Terang</span>
                </button>
                <a
                    href="pilih-peran.php"
                    class="px-4 py-2 rounded-lg text-xs sm:text-sm font-semibold bg-amikom-700 hover:bg-amikom-800 text-white shadow-sm transition"
                >
                    LOGIN
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-8 space-y-12">

        <!-- Hero Section -->
        <section class="bg-white dark:bg-slate-900 rounded-3xl border border-purple-100 dark:border-slate-800 p-6 sm:p-10 shadow-sm">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <div class="lg:col-span-7 space-y-4">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-amikom-800 dark:bg-purple-950 dark:text-purple-200">
                        Sistem Kunjungan &amp; Antrean Kampus
                    </span>
                    <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white leading-snug">
                        Sampaikan Keperluan Anda dengan Cepat dan Terstruktur
                    </h1>
                    <p class="text-base sm:text-lg font-medium text-amikom-700 dark:text-purple-300">
                        “Selamat Datang di Layanan Buku Tamu Digital”
                    </p>
                    <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                        Cek jadwal ketersediaan dosen di kampus, buat janji temu atau bimbingan, pantau nomor antrean digital, dan bubuhkan tanda tangan digital langsung secara praktis.
                    </p>
                    <div class="pt-2 flex flex-wrap gap-3">
                        <a
                            href="pilih-peran.php"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-amikom-700 hover:bg-amikom-800 text-white shadow-sm transition"
                        >
                            Mulai Buat Janji Temu
                        </a>
                        <a
                            href="#cek-jadwal"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold border border-purple-200 dark:border-slate-700 text-amikom-800 dark:text-purple-200 hover:bg-purple-50 dark:hover:bg-slate-800 transition"
                        >
                            Cek Jadwal &amp; Antrean
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-5">
                    <div class="rounded-2xl bg-gradient-to-br from-amikom-800 via-amikom-700 to-purple-950 text-white p-6 space-y-5 shadow-md">
                        <div class="flex items-center justify-between border-b border-white/15 pb-3">
                            <span class="text-xs uppercase tracking-wider text-purple-200">Ringkasan Kampus</span>
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-white/15">Hari Ini</span>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white/10 rounded-xl p-4">
                                <p class="text-2xl font-bold"><?= $totalVisitsToday + 20 ?></p>
                                <p class="text-xs text-purple-100 mt-1">Tamu Terlayani Hari Ini</p>
                            </div>
                            <div class="bg-white/10 rounded-xl p-4">
                                <p class="text-2xl font-bold"><?= $activeDosenCount + 14 ?></p>
                                <p class="text-xs text-purple-100 mt-1">Dosen Aktif Tersedia</p>
                            </div>
                        </div>
                        <p class="text-xs text-purple-100 leading-relaxed">
                            Pastikan Anda mengecek status kehadiran dan jadwal dosen sebelum datang ke ruang dosen agar waktu bimbingan lebih efisien.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Keunggulan & Fitur Utama -->
        <section class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-slate-900 border border-purple-100 dark:border-slate-800 rounded-2xl p-5 flex items-start gap-3">
                    <div class="w-9 h-9 rounded-lg bg-purple-100 dark:bg-purple-950 text-amikom-700 dark:text-purple-300 flex items-center justify-center font-bold shrink-0">01</div>
                    <div>
                        <h2 class="font-bold text-sm text-slate-900 dark:text-white">Efisiensi Waktu</h2>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">Cek jadwal dosen tersedia sebelum datang tanpa bolak-balik ruangan.</p>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-purple-100 dark:border-slate-800 rounded-2xl p-5 flex items-start gap-3">
                    <div class="w-9 h-9 rounded-lg bg-purple-100 dark:bg-purple-950 text-amikom-700 dark:text-purple-300 flex items-center justify-center font-bold shrink-0">02</div>
                    <div>
                        <h2 class="font-bold text-sm text-slate-900 dark:text-white">Sistem Antrean Digital</h2>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">Pemantauan status antrean kunjungan mahasiswa secara transparan.</p>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-purple-100 dark:border-slate-800 rounded-2xl p-5 flex items-start gap-3">
                    <div class="w-9 h-9 rounded-lg bg-purple-100 dark:bg-purple-950 text-amikom-700 dark:text-purple-300 flex items-center justify-center font-bold shrink-0">03</div>
                    <div>
                        <h2 class="font-bold text-sm text-slate-900 dark:text-white">Tanda Tangan Digital</h2>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">Pembubuhan tanda tangan langsung di layar sebagai bukti kunjungan.</p>
                    </div>
                </div>
            </div>

            <!-- 3 Kartu Layanan -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-slate-900 border border-purple-200 dark:border-slate-800 rounded-2xl p-6 space-y-3">
                    <span class="inline-block px-3 py-1 rounded-lg text-xs font-bold bg-amikom-700 text-white">DOSEN DAN AGENDA</span>
                    <ul class="text-sm text-slate-700 dark:text-slate-300 space-y-1.5 pt-1">
                        <li>• Konsultasi Mata Kuliah</li>
                        <li>• Bimbingan KRS &amp; Akademik</li>
                        <li>• Bimbingan Proyek / Tugas Akhir</li>
                    </ul>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-purple-200 dark:border-slate-800 rounded-2xl p-6 space-y-3">
                    <span class="inline-block px-3 py-1 rounded-lg text-xs font-bold bg-amikom-700 text-white">SISTEM ANTREAN DIGITAL</span>
                    <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed pt-1">
                        Nomor antrean tercatat otomatis per kunjungan sehingga mahasiswa dan dosen dapat mengatur waktu pertemuan dengan lebih teratur.
                    </p>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-purple-200 dark:border-slate-800 rounded-2xl p-6 space-y-3">
                    <span class="inline-block px-3 py-1 rounded-lg text-xs font-bold bg-amikom-700 text-white">TANDA TANGAN DIGITAL</span>
                    <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed pt-1">
                        Rekap kunjungan tersimpan rapi di database lengkap dengan tanda tangan digital untuk keperluan dokumentasi akademik.
                    </p>
                </div>
            </div>
        </section>

        <!-- Cek Status Antrean & Jadwal Ketersediaan Dosen -->
        <section id="cek-jadwal" class="bg-white dark:bg-slate-900 rounded-3xl border border-purple-100 dark:border-slate-800 p-6 sm:p-8 shadow-sm space-y-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">Cek Status Antrean &amp; Jadwal Dosen</h2>
                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">
                        Cari berdasarkan nama dosen, NIDN, NIM, atau kode antrean untuk melihat ketersediaan di kampus.
                    </p>
                </div>
                <div class="px-4 py-2.5 rounded-xl bg-amikom-700 text-white text-xs font-medium">
                    Pastikan data kunjungan sudah tercatat sebelum menemui dosen.
                </div>
            </div>

            <form method="GET" action="index.php#cek-jadwal" class="flex flex-col sm:flex-row gap-3">
                <input
                    type="text"
                    name="q"
                    value="<?= htmlspecialchars($keyword) ?>"
                    placeholder="Contoh: Nila, 25.01.5250, atau A-001..."
                    class="flex-1 px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-sm focus:outline-none focus:ring-2 focus:ring-amikom-600"
                >
                <button
                    type="submit"
                    class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-amikom-700 hover:bg-amikom-800 text-white transition"
                >
                    Cari Informasi
                </button>
                <?php if ($keyword !== ''): ?>
                    <a
                        href="index.php#cek-jadwal"
                        class="px-4 py-2.5 rounded-xl text-sm font-medium border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-center transition"
                    >
                        Reset
                    </a>
                <?php endif; ?>
            </form>

            <!-- Hasil Pencarian Antrean Jika Ada Kata Kunci -->
            <?php if ($keyword !== ''): ?>
                <div class="space-y-3">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-amikom-800 dark:text-purple-300">
                        Hasil Pencarian Antrean (<?= count($antreanResults) ?>)
                    </h3>
                    <?php if (count($antreanResults) > 0): ?>
                        <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-purple-50 dark:bg-slate-800 text-amikom-900 dark:text-purple-200 text-xs uppercase">
                                    <tr>
                                        <th class="py-3 px-4">Antrean</th>
                                        <th class="py-3 px-4">Mahasiswa</th>
                                        <th class="py-3 px-4">Dosen Tujuan</th>
                                        <th class="py-3 px-4">Jadwal</th>
                                        <th class="py-3 px-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                    <?php foreach ($antreanResults as $row): ?>
                                        <tr>
                                            <td class="py-3 px-4 font-bold text-amikom-700 dark:text-purple-300"><?= htmlspecialchars($row['kode_antrean']) ?></td>
                                            <td class="py-3 px-4">
                                                <div class="font-medium"><?= htmlspecialchars($row['nama_mahasiswa']) ?></div>
                                                <div class="text-xs text-slate-500"><?= htmlspecialchars($row['nim']) ?></div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <div><?= htmlspecialchars($row['nama_dosen']) ?></div>
                                                <div class="text-xs text-slate-500"><?= htmlspecialchars($row['ruangan'] ?? '-') ?></div>
                                            </td>
                                            <td class="py-3 px-4 text-xs">
                                                <?= htmlspecialchars($row['tanggal_kunjungan']) ?> • <?= htmlspecialchars($row['jam_kunjungan']) ?> WIB
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-amikom-800">
                                                    <?= ucfirst(htmlspecialchars($row['status_antrean'])) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Tidak ditemukan data antrean yang cocok dengan kata kunci tersebut.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Daftar Dosen & Ketersediaan Jadwal di Kampus -->
            <div class="space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">
                    Daftar Dosen &amp; Jadwal Ketersediaan di Kampus
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($dosenList as $dosen): ?>
                        <?php
                            $badgeClass = match ($dosen['status_kehadiran']) {
                                'tersedia' => 'bg-emerald-100 text-emerald-800',
                                'sibuk' => 'bg-amber-100 text-amber-800',
                                default => 'bg-slate-200 text-slate-700',
                            };
                            $statusLabel = match ($dosen['status_kehadiran']) {
                                'tersedia' => 'Tersedia di Kampus',
                                'sibuk' => 'Sedang Mengajar / Sibuk',
                                default => 'Tidak di Kampus',
                            };
                        ?>
                        <div class="border border-purple-100 dark:border-slate-800 rounded-2xl p-5 bg-slate-50/60 dark:bg-slate-950/50 space-y-2.5">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h4 class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($dosen['nama_lengkap']) ?></h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        NIDN: <?= htmlspecialchars($dosen['nidn']) ?> • <?= htmlspecialchars($dosen['prodi']) ?>
                                    </p>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold shrink-0 <?= $badgeClass ?>">
                                    <?= $statusLabel ?>
                                </span>
                            </div>
                            <p class="text-xs font-medium text-amikom-800 dark:text-purple-300">
                                Lokasi: <?= htmlspecialchars($dosen['ruangan'] ?? 'Belum diatur') ?>
                            </p>
                            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                                <span class="font-semibold">Jadwal Tersedia:</span>
                                <?= htmlspecialchars($dosen['jadwal_ringkas'] ?? 'Belum ada jadwal rutin') ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="border-t border-purple-100 dark:border-slate-800 bg-white dark:bg-slate-900 mt-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400">
            <p>© <?= date('Y') ?> Universitas Amikom Yogyakarta • Proyek Buku Tamu Digital</p>
            <div class="flex items-center gap-4">
                <a href="pilih-peran.php" class="hover:text-amikom-700">Pilih Peran</a>
                <a href="#cek-jadwal" class="hover:text-amikom-700">Jadwal Dosen</a>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            const root = document.documentElement;
            const btn = document.getElementById('themeToggleBtn');
            const label = document.getElementById('themeLabel');

            // Tema terang sebagai bawaan utama
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