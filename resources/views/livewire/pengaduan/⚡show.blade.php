<?php

use App\Models\Pengaduan;
use App\Models\TanggapanPengaduan;
use App\Notifications\PengaduanStatusDiverifikasiNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detail Pengaduan')] class extends Component
{
    public Pengaduan $pengaduan;

    public string $status = '';

    public string $isi_tanggapan = '';

    public string $alasan_penolakan = '';

    public function fotoUrl(string $foto): string
    {
        return '/storage/'.Str::of($foto)->ltrim('/');
    }

    /**
     * @return array<int, string>
     */
    public function fotoPaths(): array
    {
        return $this->pengaduan->fotoPaths();
    }

    public function mount(Pengaduan $pengaduan): void
    {
        Gate::authorize('view', $pengaduan);

        $this->pengaduan = $pengaduan->load(['user', 'tanggapan.admin']);
        $this->status = $pengaduan->status;
    }

    public function saveStatus(): void
    {
        Gate::authorize('verify', $this->pengaduan);

        $validated = $this->validate([
            'status' => ['required', Rule::in(Pengaduan::STATUSES)],
            'alasan_penolakan' => [
                Rule::requiredIf($this->status === Pengaduan::STATUS_DITOLAK && $this->pengaduan->status !== Pengaduan::STATUS_DITOLAK),
                'nullable',
                'string',
            ],
        ]);

        $statusSebelumnya = $this->pengaduan->status;

        $this->pengaduan->update(['status' => $validated['status']]);

        if ($validated['status'] === Pengaduan::STATUS_DITOLAK && $this->pengaduan->wasChanged('status')) {
            TanggapanPengaduan::create([
                'pengaduan_id' => $this->pengaduan->id,
                'admin_id' => auth()->id(),
                'isi_tanggapan' => __('Pengaduan ditolak. Alasan: :alasan', [
                    'alasan' => $validated['alasan_penolakan'],
                ]),
            ]);
        }

        if ($this->pengaduan->wasChanged('status')) {
            $this->notifyStatusChanged($statusSebelumnya, $validated['alasan_penolakan'] ?? null);
        }

        $this->alasan_penolakan = '';
        $this->pengaduan->refresh()->load(['user', 'tanggapan.admin']);
    }

    public function respond(): void
    {
        Gate::authorize('respond', $this->pengaduan);

        $validated = $this->validate([
            'isi_tanggapan' => ['required', 'string'],
        ]);

        TanggapanPengaduan::create([
            'pengaduan_id' => $this->pengaduan->id,
            'admin_id' => auth()->id(),
            'isi_tanggapan' => $validated['isi_tanggapan'],
        ]);

        if ($this->pengaduan->status === Pengaduan::STATUS_PENDING) {
            $statusSebelumnya = $this->pengaduan->status;

            $this->pengaduan->update(['status' => Pengaduan::STATUS_DIPROSES]);
            $this->status = Pengaduan::STATUS_DIPROSES;

            if ($this->pengaduan->wasChanged('status')) {
                $this->notifyStatusChanged($statusSebelumnya);
            }
        }

        $this->isi_tanggapan = '';
        $this->pengaduan->refresh()->load(['user', 'tanggapan.admin']);
    }

    private function notifyStatusChanged(string $statusSebelumnya, ?string $alasanPenolakan = null): void
    {
        try {
            $this->pengaduan->load('user');
            $this->pengaduan->user?->notify(new PengaduanStatusDiverifikasiNotification(
                pengaduan: $this->pengaduan,
                statusSebelumnya: $statusSebelumnya,
                verifikator: auth()->user(),
                alasanPenolakan: $alasanPenolakan,
            ));
        } catch (Throwable $e) {
            report($e);
        }
    }
};
?>

<section class="mx-auto flex w-full max-w-5xl flex-col gap-6">
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-2 text-sm text-zinc-500"><a href="{{ route('pengaduan.index') }}" wire:navigate>{{ __('Pengaduan') }}</a><span>/</span><span>{{ __('Detail') }}</span></div>
        <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ $pengaduan->judul }}</h1>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="space-y-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div>
                <p class="text-sm text-zinc-500">{{ __('Pelapor') }}: {{ $pengaduan->user->name }} &middot; {{ $pengaduan->created_at->format('d M Y') }}</p>
                <p class="mt-4 whitespace-pre-line text-zinc-800 dark:text-zinc-100">{{ $pengaduan->isi_pengaduan }}</p>
            </div>
            @if ($this->fotoPaths())
                <div class="space-y-3">
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Foto Pengaduan') }}</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($this->fotoPaths() as $index => $foto)
                            <a href="{{ $this->fotoUrl($foto) }}" target="_blank" class="block">
                                <img
                                    src="{{ $this->fotoUrl($foto) }}"
                                    alt="{{ $pengaduan->judul }} {{ $index + 1 }}"
                                    class="aspect-video w-full rounded-lg border border-zinc-200 object-cover dark:border-zinc-700"
                                >
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-4">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500">{{ __('Status Saat Ini') }}</p>
                <p class="mt-1 text-lg font-semibold text-zinc-950 dark:text-white">{{ $pengaduan->status }}</p>
                <p class="mt-4 text-sm text-zinc-500">{{ __('Sifat') }}</p>
                <p class="mt-1 text-lg font-semibold text-zinc-950 dark:text-white">{{ $pengaduan->visibilitas }}</p>
                @can('pengaduan.verify')
                    <form wire:submit="saveStatus" class="mt-4 space-y-3">
                        <flux:select wire:model.live="status" :label="__('Ubah Status')">
                            @foreach (Pengaduan::STATUSES as $statusOption)
                                <flux:select.option value="{{ $statusOption }}">{{ $statusOption }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        @if ($status === Pengaduan::STATUS_DITOLAK && $pengaduan->status !== Pengaduan::STATUS_DITOLAK)
                            <flux:textarea wire:model="alasan_penolakan" :label="__('Alasan Penolakan')" rows="4" required />
                        @endif

                        <flux:button type="submit" variant="primary" class="w-full">{{ __('Simpan Status') }}</flux:button>
                    </form>
                @endcan
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
            <h2 class="font-semibold text-zinc-950 dark:text-white">{{ __('Tanggapan') }}</h2>
        </div>
        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @forelse ($pengaduan->tanggapan as $tanggapan)
                <div class="px-5 py-4">
                    <p class="text-sm text-zinc-500">{{ $tanggapan->admin?->name ?? __('Admin') }} &middot; {{ $tanggapan->created_at->format('d M Y H:i') }}</p>
                    <p class="mt-2 whitespace-pre-line text-zinc-800 dark:text-zinc-100">{{ $tanggapan->isi_tanggapan }}</p>
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-zinc-500">{{ __('Belum ada tanggapan.') }}</p>
            @endforelse
        </div>
        @can('pengaduan.respond')
            <form wire:submit="respond" class="space-y-3 border-t border-zinc-200 p-5 dark:border-zinc-700">
                <flux:textarea wire:model="isi_tanggapan" :label="__('Tambah Tanggapan')" rows="4" required />
                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">{{ __('Kirim Tanggapan') }}</flux:button>
                </div>
            </form>
        @endcan
    </div>
</section>

