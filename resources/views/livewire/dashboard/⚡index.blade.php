<?php

use App\Models\DokumentasiKegiatan;
use App\Models\Pengaduan;
use App\Models\ProgramBanjar;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component
{
    /**
     * @return array<string, int>
     */
    #[Computed]
    public function stats(): array
    {
        $pengaduan = $this->pengaduanQuery();

        return [
            'users' => User::count(),
            'pengaduan' => (clone $pengaduan)->count(),
            'pending' => (clone $pengaduan)->where('status', Pengaduan::STATUS_PENDING)->count(),
            'diproses' => (clone $pengaduan)->where('status', Pengaduan::STATUS_DIPROSES)->count(),
            'selesai' => (clone $pengaduan)->where('status', Pengaduan::STATUS_SELESAI)->count(),
            'program' => ProgramBanjar::count(),
            'dokumentasi' => DokumentasiKegiatan::count(),
        ];
    }

    #[Computed]
    public function recentPengaduan()
    {
        return $this->pengaduanQuery()
            ->with('user')
            ->latest()
            ->limit(5)
            ->get();
    }

    private function pengaduanQuery(): Builder
    {
        return Pengaduan::query()->visibleTo(Auth::user());
    }
};
?>

<section class="mx-auto flex w-full max-w-7xl flex-col gap-6">
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
            <span>{{ __('Layanan') }}</span>
            <span>/</span>
            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ __('Dashboard') }}</span>
        </div>

        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Dashboard') }}</h1>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Ringkasan layanan masyarakat Banjar Puluk-Puluk.') }}</p>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total User') }}</p>
            <p class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($this->stats['users']) }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Pengaduan') }}</p>
            <p class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($this->stats['pengaduan']) }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pengaduan Pending') }}</p>
            <p class="mt-2 text-3xl font-semibold text-amber-600 dark:text-amber-400">{{ number_format($this->stats['pending']) }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pengaduan Diproses') }}</p>
            <p class="mt-2 text-3xl font-semibold text-sky-600 dark:text-sky-400">{{ number_format($this->stats['diproses']) }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pengaduan Selesai') }}</p>
            <p class="mt-2 text-3xl font-semibold text-emerald-600 dark:text-emerald-400">{{ number_format($this->stats['selesai']) }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Informasi Kegiatan') }}</p>
            <p class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($this->stats['program']) }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Dokumentasi') }}</p>
            <p class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ number_format($this->stats['dokumentasi']) }}</p>
        </div>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
            <h2 class="text-base font-semibold text-zinc-950 dark:text-white">{{ __('Pengaduan Terbaru') }}</h2>
        </div>

        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @forelse ($this->recentPengaduan as $pengaduan)
                <div class="flex flex-col gap-2 px-5 py-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="font-medium text-zinc-950 dark:text-white">{{ $pengaduan->judul }}</p>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $pengaduan->user->name }} · {{ $pengaduan->created_at->format('d M Y') }} · {{ $pengaduan->visibilitas }}</p>
                    </div>
                    <span class="inline-flex w-fit rounded-md border border-zinc-200 px-2 py-1 text-xs font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                        {{ $pengaduan->status }}
                    </span>
                </div>
            @empty
                <p class="px-5 py-8 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Belum ada pengaduan.') }}</p>
            @endforelse
        </div>
    </div>
</section>
