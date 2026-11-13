<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. TABEL USERS (Sesuai ERD: id, full_name, email, password_hash, role, status, created_at)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 120);
            $table->string('email', 120)->unique();
            $table->string('password_hash');
            $table->string('role', 30)->default('mahasiswa'); // mahasiswa, dosen, kalab, baak, admin
            $table->string('status', 20)->default('active');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
