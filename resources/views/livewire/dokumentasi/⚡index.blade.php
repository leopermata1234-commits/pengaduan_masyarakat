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

new #[Title('Dokumentasi')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function dokumentasi()
    {
        return DokumentasiKegiatan::query()
            ->with(['user', 'programBanjar'])
            ->when(auth()->user()->hasRole('Masyarakat'), fn (Builder $query) => $query->where('status', DokumentasiKegiatan::STATUS_PUBLISHED))
            ->when($this->search !== '', fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query
                    ->where('judul', 'like', "%{$this->search}%")
                    ->orWhere('deskripsi', 'like', "%{$this->search}%")
                    ->orWhereHas('programBanjar', fn (Builder $query) => $query->where('judul', 'like', "%{$this->search}%"))))
            ->latest('tanggal')
            ->paginate(10);
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

<section class="mx-auto flex w-full max-w-7xl flex-col gap-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400"><span>{{ __('Layanan') }}</span><span>/</span><span class="font-medium text-zinc-800 dark:text-zinc-100">{{ __('Dokumentasi') }}</span></div>
            <div>
                <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Dokumentasi') }}</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Tambahkan foto bukti berdasarkan informasi kegiatan yang sudah selesai.') }}</p>
            </div>
        </div>
        @can('dokumentasi.create')
            <flux:button icon="plus" variant="primary" :href="route('dokumentasi.create')" wire:navigate>{{ __('Tambah Dokumentasi') }}</flux:button>
        @endcan
    </div>
    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass" :placeholder="__('Cari dokumentasi')" />
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    <tr><th class="px-4 py-3">{{ __('Informasi Kegiatan') }}</th><th class="px-4 py-3">{{ __('Tanggal') }}</th><th class="px-4 py-3">{{ __('Deskripsi') }}</th><th class="px-4 py-3">{{ __('Foto') }}</th><th class="w-32 px-4 py-3 text-right">{{ __('Aksi') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->dokumentasi as $item)
                        <tr>
                            <td class="px-4 py-3 font-medium text-zinc-950 dark:text-white">{{ $item->programBanjar?->judul ?? $item->judul }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $item->tanggal->format('d M Y') }}</td>
                            <td class="max-w-md truncate px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $item->programBanjar?->deskripsi ?? $item->deskripsi }}</td>
                            <td class="px-4 py-3">
                                @php($fotoPaths = $this->fotoPaths($item))

                                @if ($fotoPaths)
                                    <div class="flex items-center gap-2">
                                        @foreach (array_slice($fotoPaths, 0, 3) as $foto)
                                            <a href="{{ $this->fotoUrl($foto) }}" target="_blank" class="block">
                                                <img
                                                    src="{{ $this->fotoUrl($foto) }}"
                                                    alt="{{ $item->judul }}"
                                                    class="h-14 w-20 rounded-md border border-zinc-200 object-cover dark:border-zinc-700"
                                                >
                                            </a>
                                        @endforeach

                                        @if (count($fotoPaths) > 3)
                                            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">+{{ count($fotoPaths) - 3 }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('Tidak ada foto') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3"><div class="flex justify-end gap-1">
                                @can('dokumentasi.edit')<flux:button size="sm" variant="ghost" icon="pencil" :href="route('dokumentasi.edit', $item)" wire:navigate />@endcan
                                @can('dokumentasi.delete')<flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $item->id }})" wire:confirm="{{ __('Hapus dokumentasi ini?') }}" />@endcan
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-zinc-500">{{ __('Data dokumentasi tidak ditemukan.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">{{ $this->dokumentasi->links() }}</div>
    </div>
</section>
