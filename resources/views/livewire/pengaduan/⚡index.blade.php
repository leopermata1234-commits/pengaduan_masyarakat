<?php

use App\Models\Pengaduan;
use App\Models\User;
use App\Notifications\PengaduanReminderNotification;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
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

    #[Url]
    public string $cakupan = 'saya';

    public function mount(): void
    {
        if (! request()->query->has('cakupan') && ! auth()->user()->hasRole('Masyarakat')) {
            $this->cakupan = 'semua';
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedCakupan(): void
    {
        $this->resetPage();
    }

    public function isMasyarakat(): bool
    {
        return auth()->user()->hasRole('Masyarakat');
    }

    #[Computed]
    public function pengaduan()
    {
        return Pengaduan::query()
            ->with('user')
            ->visibleTo(auth()->user())
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('pengaduan as pengaduan_lanjutan')
                ->whereColumn('pengaduan_lanjutan.user_id', 'pengaduan.user_id')
                ->whereColumn('pengaduan_lanjutan.judul', 'pengaduan.judul')
                ->where('pengaduan_lanjutan.status', '!=', Pengaduan::STATUS_MENUNGGU)
                ->where('pengaduan.status', Pengaduan::STATUS_MENUNGGU))
            ->when($this->cakupan === 'saya', fn (Builder $query) => $query->where('user_id', auth()->id()))
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

        foreach ($pengaduan->fotos ?? [] as $foto) {
            if ($foto !== $pengaduan->foto) {
                Storage::disk('public')->delete($foto);
            }
        }

        $pengaduan->delete();
        $this->resetPage();
    }

    public function remind(int $pengaduanId): void
    {
        $pengaduan = Pengaduan::with('user')->findOrFail($pengaduanId);

        abort_unless(auth()->user()->can('pengaduan.ingatkan'), 403);
        abort_unless($pengaduan->user_id === auth()->id(), 403);

        if (! $pengaduan->isMenunggu()) {
            Flux::toast(
                variant: 'warning',
                text: __('Pengingat hanya bisa dikirim untuk pengaduan yang masih menunggu.')
            );

            return;
        }

        if ($pengaduan->isReminderOnCooldown()) {
            Flux::toast(
                variant: 'warning',
                text: __('Pengingat bisa dikirim lagi pada :tanggal.', [
                    'tanggal' => $pengaduan->reminderAvailableAt()?->format('d M Y H:i'),
                ])
            );

            return;
        }

        try {
            $penerima = User::permission('pengaduan.notifikasi-email')
                ->whereKeyNot(auth()->id())
                ->get();

            Notification::send($penerima, new PengaduanReminderNotification($pengaduan));
        } catch (Throwable $e) {
            report($e);
        }

        $pengaduan->update(['reminded_at' => now()]);

        Flux::toast(
            variant: 'success',
            text: __('Pengingat pengaduan berhasil dikirim.')
        );
    }
};
?>

<section class="flex w-full flex-col gap-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                <span>{{ __('Layanan') }}</span><span>/</span><span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $this->isMasyarakat() ? __('Pengaduan') : __('Tanggapan Pengaduan') }}</span>
            </div>
            <div>
                <h1 class="font-serif text-3xl font-bold text-[#2f241b] sm:text-4xl">{{ $this->isMasyarakat() ? __('Pengaduan Masyarakat') : __('Tanggapan Pengaduan') }}</h1>
                <p class="mt-2 text-sm leading-6 text-[#625b53] sm:text-base">
                    {{ $this->isMasyarakat() ? __('Sampaikan aspirasi dan pantau perkembangan laporan masyarakat secara terbuka.') : __('Kelola, verifikasi, dan berikan tanggapan terhadap pengaduan masyarakat.') }}
                </p>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-[#dfd4c6] bg-white/95 shadow-[0_10px_28px_rgba(62,44,29,.10)]">
        <div class="flex flex-col gap-3 border-b border-zinc-200 p-4 dark:border-zinc-700 sm:flex-row sm:items-center sm:justify-between">
            <div class="inline-flex w-fit rounded-xl bg-[#f1eee9] p-1 shadow-inner ring-1 ring-[#dfd4c6]">
                <label class="cursor-pointer rounded-lg px-4 py-2 text-sm font-semibold text-[#655b52] transition has-[:checked]:bg-[#13746e] has-[:checked]:text-white has-[:checked]:shadow-md">
                    <input type="radio" wire:model.live="cakupan" value="saya" class="sr-only">
                    {{ __('Pengaduan Saya') }}
                </label>
                <label class="cursor-pointer rounded-lg px-4 py-2 text-sm font-semibold text-[#655b52] transition has-[:checked]:bg-[#13746e] has-[:checked]:text-white has-[:checked]:shadow-md">
                    <input type="radio" wire:model.live="cakupan" value="semua" class="sr-only">
                    {{ __('Semua Pengaduan') }}
                </label>
            </div>

            @can('pengaduan.create')
                <flux:button icon="plus" variant="primary" :href="route('pengaduan.create')" wire:navigate>{{ __('Buat Pengaduan') }}</flux:button>
            @endcan
        </div>

        <div class="border-b border-[#dfd4c6] p-4">
            <div class="rounded-2xl border border-[#7c5a3c]/30 bg-[linear-gradient(135deg,#776352,#9b7b5a_48%,#6d5543)] p-2.5 shadow-[0_8px_18px_rgba(69,48,29,0.22)]">
                <div class="grid gap-2.5 md:grid-cols-[1fr_240px]">
                    <label class="flex min-h-14 items-center gap-3 rounded-xl bg-white px-4 shadow-inner ring-1 ring-black/5 focus-within:ring-2 focus-within:ring-[#17827a]">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-6 w-6 shrink-0 text-[#77716b]">
                            <circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>
                        </svg>
                        <input wire:model.live.debounce.400ms="search" type="search" placeholder="{{ __('Cari pengaduan masyarakat...') }}" class="min-w-0 flex-1 border-0 bg-transparent text-base text-[#332b25] outline-none placeholder:text-[#8a8580] focus:ring-0">
                    </label>

                    <div class="relative">
                        <select wire:model.live="status" aria-label="{{ __('Filter status pengaduan') }}" class="min-h-14 w-full appearance-none rounded-xl border-0 bg-[#f4e9d5] bg-none px-4 pr-12 text-base font-semibold text-[#352b22] shadow-inner outline-none ring-1 ring-black/10 focus:ring-2 focus:ring-[#17827a]" style="background-image: none;">
                            <option value="">{{ __('Semua Status') }}</option>
                            @foreach (Pengaduan::STATUSES as $statusOption)
                                <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                            @endforeach
                        </select>
                        <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[#554536]">
                            <path d="m6 8 4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Judul') }}</th>
                        <th class="px-4 py-3">{{ __('Tanggal') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('Sifat') }}</th>
                        <th class="w-96 px-4 py-3 text-center">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->pengaduan as $item)
                        @php
                            [$statusClasses, $statusDot] = match ($item->status) {
                                Pengaduan::STATUS_SELESAI => ['bg-emerald-100 text-emerald-800 ring-emerald-600/20', 'bg-emerald-500'],
                                Pengaduan::STATUS_DIPROSES => ['bg-sky-100 text-sky-800 ring-sky-600/20', 'bg-sky-500'],
                                Pengaduan::STATUS_DITOLAK => ['bg-red-100 text-red-800 ring-red-600/20', 'bg-red-500'],
                                default => ['bg-amber-100 text-amber-800 ring-amber-600/20', 'bg-amber-400'],
                            };
                        @endphp
                        <tr>
                            <td class="px-4 py-3 font-medium text-zinc-950 dark:text-white">{{ $item->judul }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $item->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset {{ $statusClasses }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>
                                    {{ $item->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-zinc-600 dark:text-zinc-300">
                                <span class="inline-flex items-center justify-center gap-1.5 font-medium">
                                    @if ($item->visibilitas === Pengaduan::VISIBILITAS_PRIVAT)
                                        <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 text-[#715744]">
                                            <rect x="4" y="8" width="12" height="9" rx="2"/>
                                            <path d="M7 8V6a3 3 0 0 1 6 0v2" stroke-linecap="round"/>
                                        </svg>
                                    @endif
                                    {{ $item->visibilitas }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="relative flex min-h-9 items-center justify-center whitespace-nowrap">
                                    <div class="flex justify-center">
                                        <a href="{{ route('pengaduan.show', $item) }}" wire:navigate class="inline-flex min-w-20 items-center justify-center rounded-lg bg-[#13746e] px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-[#0f625d]">
                                            {{ __('Detail') }}
                                        </a>
                                    </div>

                                    <div class="absolute left-[calc(50%+3rem)] flex justify-center">
                                        @can('pengaduan.ingatkan')
                                            @if ($item->user_id === auth()->id() && $item->isMenunggu())
                                                @if ($item->isReminderOnCooldown())
                                                    <button type="button" class="inline-flex min-w-20 cursor-not-allowed items-center justify-center rounded-lg bg-zinc-200 px-3 py-2 text-xs font-bold text-zinc-500" disabled>
                                                        {{ __('Cooldown') }}
                                                    </button>
                                                @else
                                                    <button type="button" wire:click="remind({{ $item->id }})" class="inline-flex min-w-20 items-center justify-center rounded-lg bg-red-600 px-3 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-red-700">
                                                        {{ __('Ingatkan') }}
                                                    </button>
                                                @endif
                                            @endif
                                        @endcan
                                    </div>

                                    <div class="absolute right-0 flex justify-end">
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
