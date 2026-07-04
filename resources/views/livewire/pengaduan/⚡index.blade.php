<?php

use App\Models\Pengaduan;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Pengaduan')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function pengaduan()
    {
        return Pengaduan::query()
            ->with('user')
            ->visibleTo(auth()->user())
            ->when($this->search !== '', fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query
                    ->where('judul', 'like', "%{$this->search}%")
                    ->orWhere('isi_pengaduan', 'like', "%{$this->search}%")
                    ->orWhereHas('user', fn (Builder $query) => $query->where('name', 'like', "%{$this->search}%"))))
            ->when($this->status !== '', fn (Builder $query) => $query->where('status', $this->status))
            ->latest()
            ->paginate(10);
    }

    public function delete(int $pengaduanId): void
    {
        $pengaduan = Pengaduan::findOrFail($pengaduanId);
        Gate::authorize('delete', $pengaduan);

        if ($pengaduan->foto) {
            Storage::disk('public')->delete($pengaduan->foto);
        }

        $pengaduan->delete();
        $this->resetPage();
    }

    public function remind(int $pengaduanId): void
    {
        $pengaduan = Pengaduan::findOrFail($pengaduanId);

        abort_unless(auth()->user()->can('pengaduan.ingatkan'), 403);
        abort_unless($pengaduan->user_id === auth()->id(), 403);
        abort_unless($pengaduan->status === Pengaduan::STATUS_MENUNGGU, 403);

        Flux::toast(
            variant: 'success',
            text: __('Pengingat pengaduan berhasil dikirim.')
        );
    }
};
?>

<section class="mx-auto flex w-full max-w-7xl flex-col gap-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                <span>{{ __('Layanan') }}</span><span>/</span><span class="font-medium text-zinc-800 dark:text-zinc-100">{{ __('Pengaduan') }}</span>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Pengaduan') }}</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Pantau dan kelola laporan masyarakat.') }}</p>
            </div>
        </div>

        @can('pengaduan.create')
            <flux:button icon="plus" variant="primary" :href="route('pengaduan.create')" wire:navigate>{{ __('Buat Pengaduan') }}</flux:button>
        @endcan
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-3 border-b border-zinc-200 p-4 dark:border-zinc-700 md:grid-cols-[1fr_220px]">
            <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass" :placeholder="__('Cari pengaduan')" />
            <flux:select wire:model.live="status">
                <flux:select.option value="">{{ __('Semua Status') }}</flux:select.option>
                @foreach (Pengaduan::STATUSES as $statusOption)
                    <flux:select.option value="{{ $statusOption }}">{{ $statusOption }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Judul') }}</th>
                        <th class="px-4 py-3">{{ __('Tanggal') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3">{{ __('Sifat') }}</th>
                        <th class="w-72 px-4 py-3 text-center">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->pengaduan as $item)
                        <tr>
                            <td class="px-4 py-3 font-medium text-zinc-950 dark:text-white">{{ $item->judul }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $item->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $item->status }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $item->visibilitas }}</td>
                            <td class="px-4 py-3">
                                <div class="grid grid-cols-[4rem_1fr_4rem] items-center gap-2 whitespace-nowrap">
                                    <div></div>

                                    <div class="flex items-center justify-center gap-1.5">
                                        <flux:button size="sm" variant="ghost" class="min-w-20 justify-center" :href="route('pengaduan.show', $item)" wire:navigate>{{ __('Detail') }}</flux:button>
                                        @can('pengaduan.ingatkan')
                                            @if ($item->user_id === auth()->id() && $item->status === Pengaduan::STATUS_MENUNGGU)
                                                <flux:button size="sm" variant="ghost" class="min-w-20 justify-center" wire:click="remind({{ $item->id }})">{{ __('Ingatkan') }}</flux:button>
                                            @endif
                                        @endcan
                                    </div>

                                    <div class="flex justify-end">
                                        @can('update', $item)
                                            <flux:button size="sm" variant="ghost" icon="pencil" :href="route('pengaduan.edit', $item)" wire:navigate />
                                        @endcan
                                        @can('pengaduan.delete')
                                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $item->id }})" wire:confirm="{{ __('Hapus pengaduan ini?') }}" />
                                        @endcan
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-zinc-500">{{ __('Data pengaduan tidak ditemukan.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">{{ $this->pengaduan->links() }}</div>
    </div>
</section>
