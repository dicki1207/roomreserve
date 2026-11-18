<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 2. TABEL BUILDINGS (GKT 4 Lantai & TI 2 Lantai)
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // GKT / TI
            $table->integer('total_floors');
            $table->timestamps();
        });

        // 3. TABEL ROOMS (Ruang GKT Lantai 1-4 & 9 Laboratorium Gedung TI)
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();
            $table->integer('floor');
            $table->string('room_code', 30)->unique();
            $table->string('room_name', 120);
            $table->string('room_type', 50);
            $table->integer('capacity');
            $table->string('status', 30)->default('available');
            $table->timestamps();
        });

        // 4. TABEL LAB_MANAGERS (Penugasan Kalab untuk 9 Laboratorium Gedung TI)
        Schema::create('lab_managers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Kalab
            $table->timestamp('assigned_at')->nullable();
        });

        // 5. TABEL CLASS_SCHEDULES (Jadwal Kuliah Ruang GKT beserta Dosen Pengajar)
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignId('lecturer_id')->constrained('users')->cascadeOnDelete();
            $table->string('course_name', 150);
            $table->string('class_name', 60)->nullable();
            $table->date('schedule_date');
            $table->string('start_time', 10);
            $table->string('end_time', 10);
        });

        // 6. TABEL RESERVATIONS (Data Pengajuan Peminjaman Ruangan)
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->text('purpose');
            $table->date('reservation_date');
            $table->string('start_time', 10);
            $table->string('end_time', 10);
            $table->string('status', 40); // MENUNGGU_DOSEN, MENUNGGU_KALAB, MENUNGGU_BAAK, DISETUJUI, DITOLAK
            $table->timestamp('submitted_at')->nullable();
        });

        // 7. TABEL APPROVALS (Data Persetujuan & Alasan Penolakan Wajib — Relasi 1:1 / 1:0..1 terhadap RESERVATIONS)
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->unique()->constrained('reservations')->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->string('approver_type', 30); // DOSEN, KALAB, BAAK
            $table->string('decision', 30)->default('MENUNGGU'); // MENUNGGU, DISETUJUI, DITOLAK
            $table->text('notes')->nullable();
            $table->timestamp('decided_at')->nullable();
        });

        // 8. TABEL AUDIT_LOGS (Pencatatan Aktivitas Sistem)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action', 120);
            $table->string('entity', 60);
            $table->unsignedBigInteger('entity_id');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('class_schedules');
        Schema::dropIfExists('lab_managers');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('buildings');
    }
};
