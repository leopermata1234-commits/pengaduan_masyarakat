<?php

use App\Models\ProgramBanjar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Program')] class extends Component
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

    public function gambarUrl(string $gambar): string
    {
        return '/storage/'.Str::of($gambar)->ltrim('/');
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
            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400"><span>{{ __('Layanan') }}</span><span>/</span><span class="font-medium text-zinc-800 dark:text-zinc-100">{{ __('Program') }}</span></div>
            <div>
                <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Program') }}</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Galeri foto program Banjar Puluk-Puluk.') }}</p>
            </div>
        </div>
        @can('program.create')
            <flux:button icon="plus" variant="primary" :href="route('program.create')" wire:navigate>{{ __('Tambah Program') }}</flux:button>
        @endcan
    </div>
    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-3 border-b border-zinc-200 p-4 dark:border-zinc-700 md:grid-cols-[1fr_220px]">
            <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass" :placeholder="__('Cari program')" />
            <flux:select wire:model.live="status">
                <flux:select.option value="">{{ __('Semua Status') }}</flux:select.option>
                @foreach (ProgramBanjar::STATUSES as $statusOption)
                    <flux:select.option value="{{ $statusOption }}">{{ $statusOption }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="p-4">
            @if ($this->program->isNotEmpty())
                <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($this->program as $item)
                        <article class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="relative">
                                @if ($item->gambar)
                                    <a href="{{ $this->gambarUrl($item->gambar) }}" target="_blank" class="block">
                                        <img
                                            src="{{ $this->gambarUrl($item->gambar) }}"
                                            alt="{{ $item->judul }}"
                                            class="aspect-[16/9] w-full object-cover"
                                        >
                                    </a>
                                @else
                                    <div class="flex aspect-[16/9] w-full items-center justify-center bg-zinc-100 text-sm text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                        {{ __('Belum ada foto program') }}
                                    </div>
                                @endif

                                <div class="absolute left-3 top-3 rounded-md bg-zinc-950/65 px-2.5 py-1 text-xs font-medium text-white backdrop-blur">
                                    {{ $item->tanggal->format('d M Y') }}
                                </div>

                                <div class="absolute bottom-3 right-3 rounded-md bg-zinc-950/70 px-2.5 py-1 text-xs font-medium text-white backdrop-blur">
                                    {{ $item->status }}
                                </div>
                            </div>

                            <div class="space-y-3 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <h2 class="line-clamp-2 min-h-12 text-base font-semibold leading-6 text-zinc-950 dark:text-white">{{ $item->judul }}</h2>

                                    @canany(['program.edit', 'program.delete'])
                                        <div class="flex shrink-0 gap-1">
                                            @can('program.edit')<flux:button size="sm" variant="ghost" icon="pencil" :href="route('program.edit', $item)" wire:navigate />@endcan
                                            @can('program.delete')<flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $item->id }})" wire:confirm="{{ __('Hapus program ini?') }}" />@endcan
                                        </div>
                                    @endcanany
                                </div>

                                <p class="line-clamp-3 min-h-16 text-sm leading-5 text-zinc-600 dark:text-zinc-300">{{ $item->deskripsi }}</p>

                                <div class="flex items-center justify-between gap-3 border-t border-zinc-200 pt-3 text-xs text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                    <span class="truncate">{{ $item->user->name }}</span>

                                    @can('program.edit')
                                        @if ($item->status !== ProgramBanjar::STATUS_PUBLISHED)
                                            <flux:button size="sm" variant="ghost" icon="check" wire:click="setStatus({{ $item->id }}, '{{ ProgramBanjar::STATUS_PUBLISHED }}')" />
                                        @else
                                            <flux:button size="sm" variant="ghost" icon="arrow-path" wire:click="setStatus({{ $item->id }}, '{{ ProgramBanjar::STATUS_DRAFT }}')" />
                                        @endif
                                    @endcan
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('Data program tidak ditemukan.') }}</div>
            @endif
        </div>
        <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">{{ $this->program->links() }}</div>
    </div>
</section>
