<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('class_schedules', 'class_name')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->string('class_name', 60)->nullable()->after('course_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('class_schedules', 'class_name')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->dropColumn('class_name');
            });
        }
    }
};
