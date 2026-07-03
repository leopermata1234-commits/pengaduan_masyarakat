<?php

use App\Models\ProgramBanjar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Informasi Kegiatan')] class extends Component
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
    public function program()
    {
        return ProgramBanjar::query()
            ->with('user')
            ->when(auth()->user()->hasRole('Masyarakat'), fn (Builder $query) => $query->whereIn('status', [ProgramBanjar::STATUS_PUBLISHED, ProgramBanjar::STATUS_SELESAI]))
            ->when($this->search !== '', fn (Builder $query) => $query->where('judul', 'like', "%{$this->search}%")->orWhere('deskripsi', 'like', "%{$this->search}%"))
            ->when($this->status !== '', fn (Builder $query) => $query->where('status', $this->status))
            ->latest('tanggal')
            ->paginate(10);
    }

    public function delete(int $programId): void
    {
        $program = ProgramBanjar::findOrFail($programId);
        Gate::authorize('delete', $program);

        if ($program->gambar) {
            Storage::disk('public')->delete($program->gambar);
        }
        $program->delete();
        $this->resetPage();
    }

    public function setStatus(int $programId, string $status): void
    {
        $program = ProgramBanjar::findOrFail($programId);
        Gate::authorize('update', $program);
        abort_unless(in_array($status, ProgramBanjar::STATUSES, true), 422);

        $program->update(['status' => $status]);
    }
};
?>

<section class="mx-auto flex w-full max-w-7xl flex-col gap-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400"><span>{{ __('Layanan') }}</span><span>/</span><span class="font-medium text-zinc-800 dark:text-zinc-100">{{ __('Informasi Kegiatan') }}</span></div>
            <div>
                <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Informasi Kegiatan') }}</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Kelola informasi kegiatan Banjar Puluk-Puluk.') }}</p>
            </div>
        </div>
        @can('program.create')
            <flux:button icon="plus" variant="primary" :href="route('program.create')" wire:navigate>{{ __('Tambah Informasi') }}</flux:button>
        @endcan
    </div>
    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-3 border-b border-zinc-200 p-4 dark:border-zinc-700 md:grid-cols-[1fr_220px]">
            <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass" :placeholder="__('Cari informasi kegiatan')" />
            <flux:select wire:model.live="status">
                <flux:select.option value="">{{ __('Semua Status') }}</flux:select.option>
                @foreach (ProgramBanjar::STATUSES as $statusOption)
                    <flux:select.option value="{{ $statusOption }}">{{ $statusOption }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    <tr><th class="px-4 py-3">{{ __('Judul') }}</th><th class="px-4 py-3">{{ __('Tanggal') }}</th><th class="px-4 py-3">{{ __('Status') }}</th><th class="px-4 py-3">{{ __('Pembuat') }}</th><th class="w-32 px-4 py-3 text-right">{{ __('Aksi') }}</th></tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->program as $item)
                        <tr>
                            <td class="px-4 py-3 font-medium text-zinc-950 dark:text-white">{{ $item->judul }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $item->tanggal->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $item->status }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $item->user->name }}</td>
                            <td class="px-4 py-3"><div class="flex justify-end gap-1">
                                @can('program.edit')<flux:button size="sm" variant="ghost" icon="pencil" :href="route('program.edit', $item)" wire:navigate />@endcan
                                @can('program.edit')
                                    @if ($item->status !== ProgramBanjar::STATUS_PUBLISHED)
                                        <flux:button size="sm" variant="ghost" icon="check" wire:click="setStatus({{ $item->id }}, '{{ ProgramBanjar::STATUS_PUBLISHED }}')" />
                                    @else
                                        <flux:button size="sm" variant="ghost" icon="arrow-path" wire:click="setStatus({{ $item->id }}, '{{ ProgramBanjar::STATUS_DRAFT }}')" />
                                    @endif
                                @endcan
                                @can('program.delete')<flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $item->id }})" wire:confirm="{{ __('Hapus informasi kegiatan ini?') }}" />@endcan
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-zinc-500">{{ __('Data informasi kegiatan tidak ditemukan.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">{{ $this->program->links() }}</div>
    </div>
</section>
