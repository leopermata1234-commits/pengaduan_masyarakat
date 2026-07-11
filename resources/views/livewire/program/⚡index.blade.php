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

    private function statusClasses(ProgramBanjar $program): string
    {
        return match ($program->status) {
            ProgramBanjar::STATUS_BERJALAN => 'bg-[#9bd329] text-[#20320b]',
            ProgramBanjar::STATUS_SELESAI => 'bg-[#d9a2a0] text-[#4b2322]',
            default => 'bg-[#e6c879] text-[#49370d]',
        };
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

<section class="flex w-full flex-col gap-7">
    <div class="flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2 text-sm text-[#7b746b]"><span>{{ __('Layanan') }}</span><span>/</span><span class="font-semibold text-[#32281f]">{{ __('Program & Kegiatan') }}</span></div>
            <div>
                <h1 class="font-serif text-3xl font-bold tracking-tight text-[#2f241b] sm:text-4xl">{{ __('Daftar Program & Kegiatan') }}</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-[#625b53] sm:text-base">{{ __('Informasi terkini mengenai program pembangunan, budaya, dan pelayanan masyarakat di lingkungan Banjar Puluk-Puluk.') }}</p>
            </div>
        </div>
        @can('program.create')
            <a href="{{ route('program.create') }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#13746e] px-5 py-3 text-sm font-bold text-white shadow-md transition hover:-translate-y-0.5 hover:bg-[#0f625d]">
                <span class="text-lg leading-none">+</span>{{ __('Tambah Program') }}
            </a>
        @endcan
    </div>

    <div class="rounded-2xl border border-[#7c5a3c]/30 bg-[linear-gradient(135deg,#776352,#9b7b5a_48%,#6d5543)] p-2.5 shadow-[0_8px_18px_rgba(69,48,29,0.22)]">
        <div class="grid gap-2.5 md:grid-cols-[1fr_240px]">
            <label class="flex min-h-14 items-center gap-3 rounded-xl bg-white px-4 shadow-inner ring-1 ring-black/5 focus-within:ring-2 focus-within:ring-[#17827a]">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-6 w-6 shrink-0 text-[#77716b]"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                <input wire:model.live.debounce.400ms="search" type="search" placeholder="{{ __('Cari program atau kegiatan...') }}" class="min-w-0 flex-1 border-0 bg-transparent text-base text-[#332b25] outline-none placeholder:text-[#8a8580] focus:ring-0">
            </label>
            <div class="relative">
                <select wire:model.live="status" aria-label="{{ __('Filter status program') }}" class="min-h-14 w-full appearance-none rounded-xl border-0 bg-[#f4e9d5] bg-none px-4 pr-12 text-base font-semibold text-[#352b22] shadow-inner outline-none ring-1 ring-black/10 focus:ring-2 focus:ring-[#17827a]" style="background-image: none;">
                    <option value="">{{ __('Semua Status') }}</option>
                    @foreach (ProgramBanjar::STATUSES as $statusOption)
                        <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                    @endforeach
                </select>
                <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[#554536]">
                    <path d="m6 8 4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>
    </div>

    <div>
            @if ($this->program->isNotEmpty())
                <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($this->program as $item)
                        <article class="group flex min-h-full flex-col overflow-hidden rounded-2xl bg-white shadow-[0_8px_22px_rgba(62,44,29,.12)] ring-1 ring-[#dfd4c6] transition hover:-translate-y-1 hover:shadow-[0_16px_30px_rgba(62,44,29,.18)]">
                            <div class="overflow-hidden">
                                @if ($item->gambar)
                                    <button type="button" wire:click="openDetail({{ $item->id }})" class="block w-full text-left">
                                        <img
                                            src="{{ $this->gambarUrl($item->gambar) }}"
                                            alt="{{ $item->judul }}"
                                            class="aspect-[16/9] w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                                        >
                                    </button>
                                @else
                                    <button type="button" wire:click="openDetail({{ $item->id }})" class="flex aspect-[16/9] w-full flex-col items-center justify-center bg-[linear-gradient(135deg,#e9d8b8,#b89165)] text-sm font-medium text-white">
                                        <x-app-logo-icon class="mb-2 h-10 w-10 opacity-80" />
                                        {{ __('Belum ada foto program') }}
                                    </button>
                                @endif

                            </div>

                            <div class="flex min-h-56 flex-1 flex-col p-6">
                                <div class="flex items-start justify-between gap-3">
                                    <button type="button" wire:click="openDetail({{ $item->id }})" class="block min-w-0 flex-1 text-left">
                                        <h2 class="line-clamp-2 text-lg font-semibold leading-7 text-zinc-700 dark:text-zinc-100">{{ $item->judul }}</h2>
                                    </button>
                                    <span class="shrink-0 rounded-lg px-2.5 py-1.5 text-[11px] font-extrabold uppercase {{ $this->statusClasses($item) }}">{{ $item->status }}</span>
                                </div>

                                <button type="button" wire:click="openDetail({{ $item->id }})" class="mt-3 block w-full text-left">
                                    <p class="line-clamp-3 text-sm leading-6 text-zinc-900 dark:text-zinc-300">{{ $item->deskripsi }}</p>
                                </button>

                                <div class="mt-auto flex items-end justify-between gap-3 border-t border-[#eee5d9] pt-4 text-xs text-zinc-600 dark:text-zinc-400">
                                    <div class="space-y-1.5">
                                        <p class="font-semibold text-[#655a50]">
                                            <span class="text-[#13746e]">{{ __('Tanggal') }}:</span>
                                            {{ ($item->tanggal_mulai ?? $item->tanggal)->format('d M Y') }}
                                            @if (($item->tanggal_selesai ?? null) && ! $item->tanggal_selesai->isSameDay($item->tanggal_mulai ?? $item->tanggal))
                                                <span aria-hidden="true">&ndash;</span> {{ $item->tanggal_selesai->format('d M Y') }}
                                            @endif
                                        </p>
                                        <button type="button" wire:click="openDetail({{ $item->id }})" class="font-bold text-[#13746e] transition hover:text-[#0c554f]">{{ __('Lihat Detail') }} <span aria-hidden="true">&rarr;</span></button>
                                    </div>
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
                <div class="rounded-2xl border border-dashed border-[#bcae9e] bg-white/70 py-16 text-center text-sm text-[#746c63]">{{ __('Data program tidak ditemukan.') }}</div>
            @endif
    </div>

    @if ($this->program->hasPages())
        <div class="rounded-xl bg-white/80 p-3 shadow-sm">{{ $this->program->links() }}</div>
    @endif

    <div class="relative overflow-hidden rounded-2xl border-4 border-[#8b5a32] bg-[linear-gradient(135deg,#8a5731,#bd8652_48%,#754625)] px-5 py-7 text-center text-white shadow-[0_10px_24px_rgba(84,49,24,0.25)]">
        <div aria-hidden="true" class="absolute inset-x-5 top-3 h-px bg-white/25"></div>
        <p class="relative text-base font-extrabold uppercase tracking-wide sm:text-lg">{{ __('Punya keluhan atau usulan program?') }}</p>
        <a href="{{ auth()->check() ? route('pengaduan.create') : route('login') }}" class="relative mt-4 inline-flex items-center justify-center rounded-xl border border-white/25 bg-[#d1a06c] px-6 py-3 text-sm font-extrabold uppercase tracking-wide text-white shadow-[0_5px_0_#6a4024] transition hover:-translate-y-0.5 hover:bg-[#daa978]">
            {{ __('Ajukan Pengaduan & Usulan') }}
        </a>
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
