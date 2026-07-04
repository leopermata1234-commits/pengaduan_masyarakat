<?php

use App\Models\Pengaduan;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $position = 'bottom';

    #[Computed]
    public function pendingCount(): int
    {
        return Pengaduan::query()
            ->where('status', Pengaduan::STATUS_PENDING)
            ->count();
    }

    /**
     * @return Collection<int, Pengaduan>
     */
    #[Computed]
    public function latestPengaduan(): Collection
    {
        return Pengaduan::query()
            ->with('user')
            ->where('status', Pengaduan::STATUS_PENDING)
            ->latest()
            ->limit(5)
            ->get();
    }
};
?>

<div>
    @can('pengaduan.lonceng')
        <div wire:poll.30s class="relative">
            <flux:dropdown position="{{ $position }}" align="end">
                <button
                    type="button"
                    class="relative inline-flex h-10 w-10 items-center justify-center rounded-lg text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white"
                    aria-label="{{ __('Notifikasi pengaduan') }}"
                >
                    <flux:icon name="bell" class="size-5" />

                    @if ($this->pendingCount > 0)
                        <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 text-[11px] font-semibold leading-none text-white">
                            {{ $this->pendingCount > 99 ? '99+' : $this->pendingCount }}
                        </span>
                    @endif
                </button>

                <flux:menu class="w-80 overflow-hidden p-0">
                    <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                        <p class="font-semibold text-zinc-950 dark:text-white">{{ __('Notifikasi Pengaduan') }}</p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ trans_choice(':count pengaduan menunggu tanggapan', $this->pendingCount, ['count' => $this->pendingCount]) }}
                        </p>
                    </div>

                    <div class="max-h-80 overflow-y-auto">
                        @forelse ($this->latestPengaduan as $pengaduan)
                            <a
                                href="{{ route('pengaduan.show', $pengaduan) }}"
                                wire:navigate
                                class="block border-b border-zinc-100 px-4 py-3 last:border-b-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/70"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <p class="line-clamp-1 font-medium text-zinc-950 dark:text-white">{{ $pengaduan->judul }}</p>
                                    <span class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                                        {{ $pengaduan->status }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $pengaduan->user?->name ?? __('Masyarakat') }} &middot; {{ $pengaduan->created_at->diffForHumans() }}
                                </p>
                                <p class="mt-1 line-clamp-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $pengaduan->isi_pengaduan }}</p>
                            </a>
                        @empty
                            <p class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('Belum ada pengaduan baru.') }}</p>
                        @endforelse
                    </div>

                    <a
                        href="{{ route('pengaduan.index', ['status' => Pengaduan::STATUS_PENDING]) }}"
                        wire:navigate
                        class="block border-t border-zinc-200 px-4 py-3 text-center text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    >
                        {{ __('Lihat semua pengaduan') }}
                    </a>
                </flux:menu>
            </flux:dropdown>
        </div>
    @endcan
</div>
