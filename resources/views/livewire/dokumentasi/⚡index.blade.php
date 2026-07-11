<?php

use App\Models\DokumentasiKegiatan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Galeri')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $selectedDokumentasiId = null;

    public bool $showDetailModal = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function dokumentasi()
    {
        return DokumentasiKegiatan::query()
            ->with('user')
            ->when(! auth()->check() || auth()->user()->hasRole('Masyarakat'), fn (Builder $query) => $query->where('status', DokumentasiKegiatan::STATUS_PUBLISHED))
            ->when($this->search !== '', fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query
                    ->where('judul', 'like', "%{$this->search}%")
                    ->orWhere('deskripsi', 'like', "%{$this->search}%")))
            ->latest('tanggal')
            ->paginate(10);
    }

    #[Computed]
    public function selectedDokumentasi(): ?DokumentasiKegiatan
    {
        if (! $this->selectedDokumentasiId) {
            return null;
        }

        return DokumentasiKegiatan::query()->find($this->selectedDokumentasiId);
    }

    public function openDetail(int $dokumentasiId): void
    {
        $dokumentasi = DokumentasiKegiatan::findOrFail($dokumentasiId);
        Gate::authorize('view', $dokumentasi);

        $this->selectedDokumentasiId = $dokumentasi->id;
        $this->showDetailModal = true;
    }

    public function delete(int $dokumentasiId): void
    {
        $dokumentasi = DokumentasiKegiatan::findOrFail($dokumentasiId);
        Gate::authorize('delete', $dokumentasi);

        foreach ($dokumentasi->fotos ?? [] as $foto) {
            Storage::disk('public')->delete($foto);
        }

        if ($dokumentasi->foto) {
            Storage::disk('public')->delete($dokumentasi->foto);
        }
        $dokumentasi->delete();
        $this->resetPage();
    }

    /**
     * @return array<int, string>
     */
    public function fotoPaths(DokumentasiKegiatan $dokumentasi): array
    {
        return $dokumentasi->fotos ?? ($dokumentasi->foto ? [$dokumentasi->foto] : []);
    }

    public function fotoUrl(string $foto): string
    {
        return '/storage/'.Str::of($foto)->ltrim('/');
    }

};
?>

<section class="flex w-full flex-col gap-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400"><span>{{ __('Layanan') }}</span><span>/</span><span class="font-medium text-zinc-800 dark:text-zinc-100">{{ __('Galeri') }}</span></div>
            <div>
                <h1 class="font-serif text-3xl font-bold text-[#2f241b] sm:text-4xl">{{ __('Galeri Kegiatan') }}</h1>
                <p class="mt-2 text-sm leading-6 text-[#625b53] sm:text-base">{{ __('Kumpulan momen budaya, kebersamaan, dan kegiatan Banjar Puluk-Puluk.') }}</p>
            </div>
        </div>
        @can('dokumentasi.create')
            <flux:button icon="plus" variant="primary" :href="route('dokumentasi.create')" wire:navigate>{{ __('Tambah Galeri') }}</flux:button>
        @endcan
    </div>
    <div class="space-y-6">
        <div class="rounded-2xl border border-[#7c5a3c]/30 bg-[linear-gradient(135deg,#776352,#9b7b5a_48%,#6d5543)] p-3 shadow-[0_8px_18px_rgba(69,48,29,.22)]">
            <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass" :placeholder="__('Cari galeri')" />
        </div>
        <div>
            @if ($this->dokumentasi->isNotEmpty())
                <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($this->dokumentasi as $item)
                        @php($fotoPaths = $this->fotoPaths($item))

                        <article class="group overflow-hidden rounded-2xl bg-white shadow-[0_8px_22px_rgba(62,44,29,.12)] ring-1 ring-[#dfd4c6] transition hover:-translate-y-1 hover:shadow-[0_16px_30px_rgba(62,44,29,.18)]">
                            <div class="relative">
                                @if ($fotoPaths)
                                    <button type="button" wire:click="openDetail({{ $item->id }})" class="block w-full overflow-hidden text-left">
                                        <img
                                            src="{{ $this->fotoUrl($fotoPaths[0]) }}"
                                            alt="{{ $item->judul }}"
                                            class="aspect-[16/9] w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                                        >
                                    </button>
                                @else
                                    <button type="button" wire:click="openDetail({{ $item->id }})" class="flex aspect-[16/9] w-full items-center justify-center bg-zinc-100 text-sm text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                        {{ __('Tidak ada foto') }}
                                    </button>
                                @endif

                                @if (count($fotoPaths) > 0)
                                    <div class="absolute bottom-3 right-3 rounded-md bg-zinc-950/70 px-2.5 py-1 text-xs font-medium text-white backdrop-blur">
                                        {{ '+'.count($fotoPaths).' Foto' }}
                                    </div>
                                @endif

                            </div>

                            <div class="relative flex min-h-48 flex-col p-6 pb-16">
                                <div>
                                    <button type="button" wire:click="openDetail({{ $item->id }})" class="block w-full text-left">
                                        <h2 class="line-clamp-2 text-lg font-semibold leading-7 text-zinc-700 dark:text-zinc-100">{{ $item->judul }}</h2>
                                    </button>
                                </div>

                                <button type="button" wire:click="openDetail({{ $item->id }})" class="mt-3 block w-full text-left">
                                    <p class="line-clamp-3 text-sm leading-6 text-zinc-900 dark:text-zinc-300">{{ $item->deskripsi }}</p>
                                </button>

                                <div class="mt-auto flex items-end justify-between gap-3 pt-8 text-xs text-zinc-600 dark:text-zinc-400">
                                    <div class="space-y-2">
                                        <p>{{ $item->tanggal->format('d M Y') }}</p>
                                        <button type="button" wire:click="openDetail({{ $item->id }})" class="font-bold text-[#13746e] transition hover:text-[#0c554f]">{{ __('Lihat Detail') }} <span aria-hidden="true">&rarr;</span></button>
                                    </div>
                                    @canany(['dokumentasi.edit', 'dokumentasi.delete'])
                                        <div class="flex shrink-0 gap-1">
                                            @can('dokumentasi.edit')<flux:button size="sm" variant="ghost" icon="pencil" :href="route('dokumentasi.edit', $item)" wire:navigate />@endcan
                                            @can('dokumentasi.delete')<flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $item->id }})" wire:confirm="{{ __('Hapus galeri ini?') }}" />@endcan
                                        </div>
                                    @endcanany
                                </div>

                                <div class="absolute bottom-0 right-0 rounded-tl-lg bg-[#34A99D] px-4 py-2 text-center text-sm font-bold leading-4 text-white shadow-sm">
                                    <span class="block">{{ $item->tanggal->format('d M') }}</span>
                                    <span class="block">{{ $item->tanggal->format('Y') }}</span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-[#bcae9e] bg-white/70 py-16 text-center text-sm text-[#746c63]">{{ __('Data galeri tidak ditemukan.') }}</div>
            @endif
        </div>
        @if ($this->dokumentasi->hasPages())
            <div class="rounded-xl bg-white/80 p-3 shadow-sm">{{ $this->dokumentasi->links() }}</div>
        @endif
    </div>

    <flux:modal name="detail-dokumentasi" wire:model="showDetailModal" focusable class="max-w-4xl">
        @if ($this->selectedDokumentasi)
            @php($detailFotoPaths = $this->fotoPaths($this->selectedDokumentasi))

            <div class="space-y-5">
                <div>
                    <flux:heading size="lg">{{ $this->selectedDokumentasi->judul }}</flux:heading>
                    <flux:subheading>{{ $this->selectedDokumentasi->tanggal->format('d M Y') }}</flux:subheading>
                </div>

                @if ($detailFotoPaths)
                    <div x-data="{ active: 0, total: {{ count($detailFotoPaths) }} }" class="relative overflow-hidden rounded-xl border border-[#dfd4c6] bg-[#f5efe6]">
                        @foreach ($detailFotoPaths as $index => $foto)
                            <img
                                x-show="active === {{ $index }}"
                                x-transition.opacity.duration.250ms
                                src="{{ $this->fotoUrl($foto) }}"
                                alt="{{ $this->selectedDokumentasi->judul }} {{ $index + 1 }}"
                                class="max-h-[70vh] min-h-64 w-full object-contain"
                            >
                        @endforeach

                        @if (count($detailFotoPaths) > 1)
                            <button type="button" x-on:click="active = active === 0 ? total - 1 : active - 1" aria-label="{{ __('Foto sebelumnya') }}" class="absolute left-3 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-[#2d2119]/75 text-2xl font-bold text-white shadow-lg backdrop-blur transition hover:bg-[#13746e]">
                                &lsaquo;
                            </button>
                            <button type="button" x-on:click="active = active === total - 1 ? 0 : active + 1" aria-label="{{ __('Foto berikutnya') }}" class="absolute right-3 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-[#2d2119]/75 text-2xl font-bold text-white shadow-lg backdrop-blur transition hover:bg-[#13746e]">
                                &rsaquo;
                            </button>

                            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 rounded-full bg-[#2d2119]/75 px-3 py-1 text-xs font-semibold text-white backdrop-blur">
                                <span x-text="active + 1"></span> / {{ count($detailFotoPaths) }}
                            </div>
                        @endif
                    </div>
                @else
                    <div class="flex aspect-video w-full items-center justify-center rounded-lg bg-zinc-100 text-sm text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                        {{ __('Tidak ada foto') }}
                    </div>
                @endif

                <p class="text-sm leading-6 text-zinc-700 dark:text-zinc-300">{{ $this->selectedDokumentasi->deskripsi }}</p>

                <div class="flex justify-end">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Tutup') }}</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>
</section>
