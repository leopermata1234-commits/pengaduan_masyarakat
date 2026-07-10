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
                <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Galeri') }}</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Kumpulan foto kegiatan Banjar Puluk-Puluk.') }}</p>
            </div>
        </div>
        @can('dokumentasi.create')
            <flux:button icon="plus" variant="primary" :href="route('dokumentasi.create')" wire:navigate>{{ __('Tambah Galeri') }}</flux:button>
        @endcan
    </div>
    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass" :placeholder="__('Cari galeri')" />
        </div>
        <div class="p-4">
            @if ($this->dokumentasi->isNotEmpty())
                <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($this->dokumentasi as $item)
                        @php($fotoPaths = $this->fotoPaths($item))

                        <article class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="relative">
                                @if ($fotoPaths)
                                    <div class="flex snap-x snap-mandatory overflow-x-auto scroll-smooth">
                                        @foreach ($fotoPaths as $index => $foto)
                                            <button type="button" wire:click="openDetail({{ $item->id }})" class="block w-full shrink-0 snap-start text-left">
                                                <img
                                                    src="{{ $this->fotoUrl($foto) }}"
                                                    alt="{{ $item->judul }} {{ $index + 1 }}"
                                                    class="aspect-[16/9] w-full object-cover"
                                                >
                                            </button>
                                        @endforeach
                                    </div>
                                @else
                                    <button type="button" wire:click="openDetail({{ $item->id }})" class="flex aspect-[16/9] w-full items-center justify-center bg-zinc-100 text-sm text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                        {{ __('Tidak ada foto') }}
                                    </button>
                                @endif

                                <div class="absolute left-3 top-3 rounded-md bg-zinc-950/65 px-2.5 py-1 text-xs font-medium text-white backdrop-blur">
                                    {{ $item->tanggal->format('d M Y') }}
                                </div>

                                @if (count($fotoPaths) > 0)
                                    <div class="absolute bottom-3 right-3 rounded-md bg-zinc-950/70 px-2.5 py-1 text-xs font-medium text-white backdrop-blur">
                                        {{ '+'.count($fotoPaths).' Foto' }}
                                    </div>
                                @endif

                                @if (count($fotoPaths) > 1)
                                    <div class="absolute bottom-3 left-3 rounded-md bg-white/90 px-2.5 py-1 text-xs font-medium text-zinc-700 shadow-sm backdrop-blur dark:bg-zinc-900/90 dark:text-zinc-200">
                                        {{ __('Geser foto') }}
                                    </div>
                                @endif
                            </div>

                            <div class="space-y-3 p-4">
                                <div>
                                    <button type="button" wire:click="openDetail({{ $item->id }})" class="block w-full text-left">
                                        <h2 class="line-clamp-2 min-h-12 text-base font-semibold leading-6 text-zinc-950 dark:text-white">{{ $item->judul }}</h2>
                                    </button>
                                </div>

                                <button type="button" wire:click="openDetail({{ $item->id }})" class="block w-full text-left">
                                    <p class="line-clamp-3 min-h-16 text-sm leading-5 text-zinc-600 dark:text-zinc-300">{{ $item->deskripsi }}</p>
                                </button>

                                <div class="flex items-center justify-end gap-3 border-t border-zinc-200 pt-3 text-xs text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                    @canany(['dokumentasi.edit', 'dokumentasi.delete'])
                                        <div class="flex shrink-0 gap-1">
                                            @can('dokumentasi.edit')<flux:button size="sm" variant="ghost" icon="pencil" :href="route('dokumentasi.edit', $item)" wire:navigate />@endcan
                                            @can('dokumentasi.delete')<flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $item->id }})" wire:confirm="{{ __('Hapus galeri ini?') }}" />@endcan
                                        </div>
                                    @endcanany
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('Data galeri tidak ditemukan.') }}</div>
            @endif
        </div>
        <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">{{ $this->dokumentasi->links() }}</div>
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
                    <div class="flex max-h-[70vh] snap-x snap-mandatory overflow-x-auto rounded-lg border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800">
                        @foreach ($detailFotoPaths as $index => $foto)
                            <img
                                src="{{ $this->fotoUrl($foto) }}"
                                alt="{{ $this->selectedDokumentasi->judul }} {{ $index + 1 }}"
                                class="max-h-[70vh] w-full shrink-0 snap-start object-contain"
                            >
                        @endforeach
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
