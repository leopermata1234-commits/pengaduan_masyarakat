<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('pengaduan', 'reminded_at')) {
            return;
        }

        Schema::table('pengaduan', function (Blueprint $table) {
            $table->timestamp('reminded_at')->nullable()->after('visibilitas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('pengaduan', 'reminded_at')) {
            return;
        }

        Schema::table('pengaduan', function (Blueprint $table) {
            $table->dropColumn('reminded_at');
        });
    }
};
