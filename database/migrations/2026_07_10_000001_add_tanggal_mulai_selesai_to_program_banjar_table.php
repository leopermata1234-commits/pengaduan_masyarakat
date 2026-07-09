<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('program_banjar', function (Blueprint $table) {
            if (! Schema::hasColumn('program_banjar', 'tanggal_mulai')) {
                $table->date('tanggal_mulai')->nullable()->after('tanggal');
            }

            if (! Schema::hasColumn('program_banjar', 'tanggal_selesai')) {
                $table->date('tanggal_selesai')->nullable()->after('tanggal_mulai');
            }
        });

        DB::table('program_banjar')->update([
            'tanggal_mulai' => DB::raw('tanggal'),
            'tanggal_selesai' => DB::raw('tanggal'),
        ]);

        DB::table('program_banjar')
            ->where('status', 'Draft')
            ->update(['status' => 'Rencana']);

        DB::table('program_banjar')
            ->where('status', 'Published')
            ->update(['status' => 'Berjalan']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('program_banjar')
            ->where('status', 'Rencana')
            ->update(['status' => 'Draft']);

        DB::table('program_banjar')
            ->where('status', 'Berjalan')
            ->update(['status' => 'Published']);

        Schema::table('program_banjar', function (Blueprint $table) {
            if (Schema::hasColumn('program_banjar', 'tanggal_selesai')) {
                $table->dropColumn('tanggal_selesai');
            }

            if (Schema::hasColumn('program_banjar', 'tanggal_mulai')) {
                $table->dropColumn('tanggal_mulai');
            }
        });
    }
};
