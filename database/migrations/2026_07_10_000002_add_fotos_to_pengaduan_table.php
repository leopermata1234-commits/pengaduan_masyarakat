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
        Schema::table('pengaduan', function (Blueprint $table) {
            if (! Schema::hasColumn('pengaduan', 'fotos')) {
                $table->json('fotos')->nullable()->after('foto');
            }
        });

        DB::table('pengaduan')
            ->whereNotNull('foto')
            ->orderBy('id')
            ->chunkById(100, function ($pengaduan) {
                foreach ($pengaduan as $item) {
                    DB::table('pengaduan')
                        ->where('id', $item->id)
                        ->update(['fotos' => json_encode([$item->foto])]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengaduan', function (Blueprint $table) {
            if (Schema::hasColumn('pengaduan', 'fotos')) {
                $table->dropColumn('fotos');
            }
        });
    }
};
