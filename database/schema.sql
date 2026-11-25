CREATE DATABASE IF NOT EXISTS buku_tamu_amikom CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE buku_tamu_amikom;

# Tabel Pengguna (Mahasiswa & Dosen)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('mahasiswa', 'dosen') NOT NULL,
    nomor_induk VARCHAR(30) NOT NULL UNIQUE, # NIM untuk mahasiswa, NIDN untuk dosen
    nama_lengkap VARCHAR(120) NOT NULL, 
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    prodi VARCHAR(100) DEFAULT 'D3 Teknik Informatika',
    ruangan VARCHAR(100) NULL, # khusus dosen ( misalnya ruang dosen di gedung 5 lantai 2)
    status_kehadiran ENUM('tersedia', 'sibuk', 'tidak_di_kampus') DEFAULT 'tersedia',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

# Tabel Jadwal Ketersdiaan Dosen di Kampus
CREATE TABLE lecturer_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT NOT NULL,
    hari ENUM('Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu') NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    keterangan VARCHAR(150) DEFAULT 'Tersedia untuk bimbingan & konsultasi',
    FOREIGN KEY (dosen_id) REFERENCES users(id) ON DELETE CASCADE
);

# Tabel Kunjungan Buku Tamu & Antrean Digital
CREATE TABLE guest_visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_antrean VARCHAR(15) NOT NULL,
    mahasiswa_id INT NOT NULL,
    dosen_id INT NOT NULL,
    keperluan ENUM('Konsultasi', 'Bimbingan KRS', 'Bimbingan Tugas Akhir', 'Rapat/Kerjasama', 'Lainnya') NOT NULL,
    catatan_keperluan TEXT NULL,
    tanggal_kunjungan DATE NOT NULL,
    jam_kunjungan TIME NOT NULL,
    status_antrean ENUM('menunggu', 'dipanggil', 'selesai', 'batal') DEFAULT 'menunggu',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mahasiswa_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dosen_id) REFERENCES users(id) ON DELETE CASCADE
);


-- Data Dummy untuk pengujian
-- Password semua akun dummy di bawah adalah : pasword123
INSERT INTO users (role, nomor_induk, nama_lengkap, email, password, prodi, ruangan, status_kehadiran) VALUES
('dosen', '0512048801', 'Nila Feby Puspitasari, M.Kom.', 'nila.feby@amikom.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'D3 Teknik Informatika', 'Ruang Dosen Gedung 5 Lt. 2', 'tersedia'),
('dosen', '0520089002', 'Ria Andriani, M.Kom.', 'ria.andriani@amikom.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'D3 Teknik Informatika', 'Ruang Dosen Gedung 4 Lt. 1', 'sibuk'),
('mahasiswa', '25.01.5250', 'Natalia Chrisna Hapstari', 'natalia.5250@students.amikom.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'D3 Teknik Informatika', NULL, 'tersedia');

INSERT INTO lecturer_schedules (dosen_id, hari, jam_mulai, jam_selesai, keterangan) VALUES
(1, 'Senin', '09:00:00', '12:00:00', 'Bimbingan Proyek & Konsultasi Akademik'),
(1, 'Rabu', '13:00:00', '15:30:00', 'Konsultasi Mata Kuliah & Tugas Akhir'),
(2, 'Selasa', '10:00:00', '14:00:00', 'Bimbingan KRS & Konsultasi'),
(2, 'Kamis', '09:30:00', '11:30:00', 'Review Proyek Mahasiswa');

INSERT INTO guest_visits (kode_antrean, mahasiswa_id, dosen_id, keperluan, catatan_keperluan, tanggal_kunjungan, jam_kunjungan, status_antrean) VALUES
('A-001', 3, 1, 'Konsultasi', 'Diskusi progres pengembangan proyek semester', CURDATE(), '10:00:00', 'menunggu');