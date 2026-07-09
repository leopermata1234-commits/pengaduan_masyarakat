<?php

namespace Database\Seeders;

use App\Models\DokumentasiKegiatan;
use App\Models\Pengaduan;
use App\Models\ProgramBanjar;
use App\Models\TanggapanPengaduan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LayananMasyarakatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = $this->userWithRole(
            name: 'Admin Banjar',
            email: 'admin@example.com',
            role: 'Admin',
        );

        $bendesa = $this->userWithRole(
            name: 'Bendesa Adat',
            email: 'bendesa@example.com',
            role: 'Bendesa Adat',
        );

        $masyarakat = $this->userWithRole(
            name: 'Masyarakat Demo',
            email: 'masyarakat@example.com',
            role: 'Masyarakat',
        );

        if (Pengaduan::query()->doesntExist()) {
            $pengaduan = Pengaduan::factory()
                ->for($masyarakat)
                ->create([
                    'judul' => 'Lampu jalan mati di area bale banjar',
                    'isi_pengaduan' => 'Mohon bantuan pengecekan lampu jalan karena area menjadi gelap saat malam.',
                    'status' => Pengaduan::STATUS_DIPROSES,
                    'visibilitas' => Pengaduan::VISIBILITAS_PUBLIK,
                ]);

            TanggapanPengaduan::factory()
                ->for($pengaduan)
                ->for($admin, 'admin')
                ->create([
                    'isi_tanggapan' => 'Pengaduan sudah diterima dan sedang dikoordinasikan dengan petugas terkait.',
                ]);

            Pengaduan::factory()
                ->for($masyarakat)
                ->pending()
                ->create([
                    'judul' => 'Saluran air tersumbat setelah hujan',
                    'isi_pengaduan' => 'Saluran air di gang utama tersumbat dan perlu dibersihkan.',
                    'visibilitas' => Pengaduan::VISIBILITAS_PRIVAT,
                ]);
        }

        if (ProgramBanjar::query()->doesntExist()) {
            ProgramBanjar::factory()
                ->for($admin)
                ->selesai()
                ->create([
                    'judul' => 'Kerja Bakti Banjar Puluk-Puluk',
                    'deskripsi' => 'Kegiatan gotong royong membersihkan area lingkungan banjar.',
                    'tanggal' => now()->subWeek()->toDateString(),
                    'tanggal_mulai' => now()->subWeek()->toDateString(),
                    'tanggal_selesai' => now()->subWeek()->toDateString(),
                ]);

            ProgramBanjar::factory()
                ->for($bendesa)
                ->create([
                    'judul' => 'Rapat Koordinasi Krama Banjar',
                    'deskripsi' => 'Pembahasan program layanan masyarakat dan agenda kegiatan bulan depan.',
                    'tanggal' => now()->addWeeks(2)->toDateString(),
                    'tanggal_mulai' => now()->addWeeks(2)->toDateString(),
                    'tanggal_selesai' => now()->addWeeks(2)->toDateString(),
                    'status' => ProgramBanjar::STATUS_RENCANA,
                ]);
        }

        if (DokumentasiKegiatan::query()->doesntExist()) {
            DokumentasiKegiatan::factory()
                ->for($admin)
                ->published()
                ->create([
                    'judul' => 'Dokumentasi Kegiatan Bersih Lingkungan',
                    'deskripsi' => 'Dokumentasi kegiatan gotong royong warga di lingkungan Banjar Puluk-Puluk.',
                    'tanggal' => now()->subWeek()->toDateString(),
                    'fotos' => [],
                ]);
        }
    }

    private function userWithRole(string $name, string $email, string $role): User
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $user = User::factory()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
            ]);
        }

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        return $user;
    }
}
