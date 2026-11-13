---
name: roomreserve
description: >-
  Skill utama pengembangan proyek Laravel RoomReserve (D:\laragon\www\roomreserve) dengan Tailwind CSS,
  8 tabel ERD, Data Seeder Login, RBAC 5 Role (Mahasiswa, Dosen, Kalab, BAAK, Admin) dengan tampilan Blade
  terpisah per role, serta konfigurasi Docker & Kubernetes.
---

# Skill Proyek Laravel: RoomReserve

- **Lokasi Proyek**: `D:\laragon\www\roomreserve` (Jangan gunakan Drive C).
- **Framework**: Laravel + Blade + Tailwind CSS (Warna Utama: Dark Navy Blue `#0A192F` & Crisp White `#FFFFFF`, responsif semua layar).
- **Alur Peminjaman 6 Langkah**:
  1. Pengguna memilih gedung (`GKT` / `TI`), ruangan, tanggal, dan waktu.
  2. Sistem mengecek jadwal bentrok terhadap `class_schedules` dan `reservations` berstatus `DISETUJUI`.
  3. Ruang GKT (Lantai 1–4): Ada jadwal kuliah -> dikirim ke **Dosen yang mengajar** (`MENUNGGU_DOSEN`) | Tidak ada jadwal kuliah -> dikirim ke **BAAK** (`MENUNGGU_BAAK`).
  4. Laboratorium TI (2 Lantai, 9 Lab): Dikirim ke **Kepala Laboratorium (Kalab)** masing-masing (`MENUNGGU_KALAB`).
  5. Setelah disetujui (`DISETUJUI`), jadwal masuk ke **Kalender Ruangan**.
  6. Jika ditolak (`DITOLAK`), pemberi persetujuan **wajib menulis alasannya (`notes`)**.
- **Pemisahan Tampilan Role (RBAC)**:
  - `resources/views/roles/mahasiswa.blade.php` (`/mahasiswa`)
  - `resources/views/roles/dosen.blade.php` (`/dosen`)
  - `resources/views/roles/kalab.blade.php` (`/kalab`)
  - `resources/views/roles/baak.blade.php` (`/baak`)
  - `resources/views/roles/admin.blade.php` (`/admin`)
