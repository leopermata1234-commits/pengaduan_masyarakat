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
        if (! Schema::hasColumn('dokumentasi_kegiatan', 'program_banjar_id')) {
            return;
        }

        Schema::table('dokumentasi_kegiatan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_banjar_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('dokumentasi_kegiatan', 'program_banjar_id')) {
            return;
        }

        Schema::table('dokumentasi_kegiatan', function (Blueprint $table) {
            $table->foreignId('program_banjar_id')
                ->nullable()
                ->after('user_id')
                ->constrained('program_banjar')
                ->nullOnDelete();
        });
    }
};
