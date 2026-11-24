<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('requested_email', 120);
            $table->string('requested_phone', 30);
            $table->string('requester_notes', 255)->nullable();
            $table->string('target_handler', 20); // BAAK or ADMIN
            $table->string('status', 20)->default('MENUNGGU'); // MENUNGGU, SELESAI, DITOLAK
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('handled_notes', 255)->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('handled_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_requests');
    }
};
