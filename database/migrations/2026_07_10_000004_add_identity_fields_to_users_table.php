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
        Schema::table('users', function (Blueprint $table) {
            $table->string('nik', 20)->nullable()->after('phone');
            $table->string('kk', 20)->nullable()->after('nik');
            $table->date('tanggal_lahir')->nullable()->after('kk');
            $table->string('jenis_kelamin', 20)->nullable()->after('tanggal_lahir');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nik', 'kk', 'tanggal_lahir', 'jenis_kelamin']);
        });
    }
};
