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

    public ?int $selectedProgramId = null;

    public bool $showDetailModal = false;

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
            ->when(! auth()->check() || auth()->user()->hasRole('Masyarakat'), fn (Builder $query) => $query->whereIn('status', [ProgramBanjar::STATUS_BERJALAN, ProgramBanjar::STATUS_SELESAI]))
            ->when($this->search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query->where('judul', 'like', "%{$this->search}%")->orWhere('deskripsi', 'like', "%{$this->search}%")))
            ->when($this->status !== '', fn (Builder $query) => $query->where('status', $this->status))
            ->latest('tanggal_mulai')
            ->paginate(10);
    }

    #[Computed]
    public function selectedProgram(): ?ProgramBanjar
    {
        if (! $this->selectedProgramId) {
            return null;
        }

        return ProgramBanjar::query()->find($this->selectedProgramId);
    }

    public function openDetail(int $programId): void
    {
        $program = ProgramBanjar::findOrFail($programId);
        Gate::authorize('view', $program);

        $this->selectedProgramId = $program->id;
        $this->showDetailModal = true;
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

<section class="flex w-full flex-col gap-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400"><span>{{ __('Layanan') }}</span><span>/</span><span class="font-medium text-zinc-800 dark:text-zinc-100">{{ __('Program') }}</span></div>
            <div>
                <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Program') }}</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Informasi dan kabar terbaru Banjar Puluk-Puluk.') }}</p>
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
                                    <button type="button" wire:click="openDetail({{ $item->id }})" class="block w-full text-left">
                                        <img
                                            src="{{ $this->gambarUrl($item->gambar) }}"
                                            alt="{{ $item->judul }}"
                                            class="aspect-[16/9] w-full object-cover"
                                        >
                                    </button>
                                @else
                                    <button type="button" wire:click="openDetail({{ $item->id }})" class="flex aspect-[16/9] w-full items-center justify-center bg-zinc-100 text-sm text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                        {{ __('Belum ada foto program') }}
                                    </button>
                                @endif

                                <div class="absolute left-3 top-3 rounded-md bg-zinc-950/65 px-2.5 py-1 text-xs font-medium text-white backdrop-blur">
                                    {{ ($item->tanggal_mulai ?? $item->tanggal)->format('d M Y') }}
                                    @if (($item->tanggal_selesai ?? null) && ! $item->tanggal_selesai->isSameDay($item->tanggal_mulai ?? $item->tanggal))
                                        - {{ $item->tanggal_selesai->format('d M Y') }}
                                    @endif
                                </div>

                                <div class="absolute bottom-3 right-3 rounded-md bg-zinc-950/70 px-2.5 py-1 text-xs font-medium text-white backdrop-blur">
                                    {{ $item->status }}
                                </div>
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
                                    @canany(['program.edit', 'program.delete'])
                                        <div class="flex shrink-0 gap-1">
                                            @can('program.edit')
                                                @if ($item->status === ProgramBanjar::STATUS_RENCANA)
                                                    <flux:button size="sm" variant="ghost" icon="play" wire:click="setStatus({{ $item->id }}, '{{ ProgramBanjar::STATUS_BERJALAN }}')" />
                                                @elseif ($item->status === ProgramBanjar::STATUS_BERJALAN)
                                                    <flux:button size="sm" variant="ghost" icon="check" wire:click="setStatus({{ $item->id }}, '{{ ProgramBanjar::STATUS_SELESAI }}')" />
                                                @else
                                                    <flux:button size="sm" variant="ghost" icon="arrow-path" wire:click="setStatus({{ $item->id }}, '{{ ProgramBanjar::STATUS_RENCANA }}')" />
                                                @endif

                                                <flux:button size="sm" variant="ghost" icon="pencil" :href="route('program.edit', $item)" wire:navigate />
                                            @endcan

                                            @can('program.delete')<flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $item->id }})" wire:confirm="{{ __('Hapus program ini?') }}" />@endcan
                                        </div>
                                    @endcanany
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

    <flux:modal name="detail-program" wire:model="showDetailModal" focusable class="max-w-4xl">
        @if ($this->selectedProgram)
            <div class="space-y-5">
                <div>
                    <flux:heading size="lg">{{ $this->selectedProgram->judul }}</flux:heading>
                    <flux:subheading>
                        {{ ($this->selectedProgram->tanggal_mulai ?? $this->selectedProgram->tanggal)->format('d M Y') }}
                        @if (($this->selectedProgram->tanggal_selesai ?? null) && ! $this->selectedProgram->tanggal_selesai->isSameDay($this->selectedProgram->tanggal_mulai ?? $this->selectedProgram->tanggal))
                            - {{ $this->selectedProgram->tanggal_selesai->format('d M Y') }}
                        @endif
                        · {{ $this->selectedProgram->status }}
                    </flux:subheading>
                </div>

                @if ($this->selectedProgram->gambar)
                    <div class="max-h-[70vh] overflow-hidden rounded-lg border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800">
                        <img
                            src="{{ $this->gambarUrl($this->selectedProgram->gambar) }}"
                            alt="{{ $this->selectedProgram->judul }}"
                            class="max-h-[70vh] w-full object-contain"
                        >
                    </div>
                @else
                    <div class="flex aspect-video w-full items-center justify-center rounded-lg bg-zinc-100 text-sm text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                        {{ __('Belum ada foto program') }}
                    </div>
                @endif

                <p class="text-sm leading-6 text-zinc-700 dark:text-zinc-300">{{ $this->selectedProgram->deskripsi }}</p>

                <div class="flex justify-end">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Tutup') }}</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>
</section>
