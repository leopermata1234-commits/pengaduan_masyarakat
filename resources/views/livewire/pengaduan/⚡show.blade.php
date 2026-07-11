<?php

use App\Models\Pengaduan;
use App\Models\TanggapanPengaduan;
use App\Notifications\PengaduanStatusDiverifikasiNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('Detail Pengaduan')] class extends Component
{
    use WithFileUploads;

    public Pengaduan $pengaduan;

    public string $status = '';

    public string $isi_tanggapan = '';

    public string $alasan_penolakan = '';

    public ?TemporaryUploadedFile $foto_tanggapan = null;

    public ?TemporaryUploadedFile $foto_status = null;

    public bool $showFotoModal = false;

    public ?string $previewFotoUrl = null;

    public string $previewFotoAlt = '';

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

    public function openFotoPreview(string $foto, string $alt = ''): void
    {
        $this->previewFotoUrl = $this->fotoUrl($foto);
        $this->previewFotoAlt = $alt;
        $this->showFotoModal = true;
    }

    public function mount(Pengaduan $pengaduan): void
    {
        Gate::authorize('view', $pengaduan);

        $this->pengaduan = $pengaduan->load(['user', 'tanggapan.admin']);
        $this->status = $pengaduan->status;
    }

    /**
     * @return array<string, string>
     */
    public function actionStatuses(): array
    {
        return [
            Pengaduan::STATUS_MENUNGGU => __('Menunggu'),
            Pengaduan::STATUS_DIPROSES => __('Sedang Diproses'),
            Pengaduan::STATUS_SELESAI => __('Selesai'),
        ];
    }

    public function saveTindakan(): void
    {
        Gate::authorize('verify', $this->pengaduan);

        $validated = $this->validate([
            'status' => ['required', Rule::in(array_keys($this->actionStatuses()))],
            'isi_tanggapan' => ['nullable', 'string'],
            'foto_tanggapan' => [
                Rule::requiredIf($this->status === Pengaduan::STATUS_SELESAI && $this->pengaduan->status !== Pengaduan::STATUS_SELESAI),
                'nullable',
                'image',
                'max:2048',
            ],
        ]);

        $statusSebelumnya = $this->pengaduan->status;
        $fotoTanggapan = $this->foto_tanggapan?->store('tanggapan-pengaduan', 'public');

        $this->pengaduan->update(['status' => $validated['status']]);

        if (filled($validated['isi_tanggapan']) || $fotoTanggapan || $this->pengaduan->wasChanged('status')) {
            TanggapanPengaduan::create([
                'pengaduan_id' => $this->pengaduan->id,
                'admin_id' => auth()->id(),
                'isi_tanggapan' => filled($validated['isi_tanggapan'])
                    ? $validated['isi_tanggapan']
                    : __('Status pengaduan diperbarui menjadi :status.', ['status' => $validated['status']]),
                'status' => $validated['status'],
                'foto' => $fotoTanggapan,
            ]);
        }

        if ($this->pengaduan->wasChanged('status')) {
            $this->notifyStatusChanged($statusSebelumnya);
        }

        $this->isi_tanggapan = '';
        $this->foto_tanggapan = null;
        $this->pengaduan->refresh()->load(['user', 'tanggapan.admin']);
        $this->status = $this->pengaduan->status;
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
            'foto_status' => [
                Rule::requiredIf($this->status === Pengaduan::STATUS_SELESAI && $this->pengaduan->status !== Pengaduan::STATUS_SELESAI),
                'nullable',
                'image',
                'max:2048',
            ],
        ]);

        $statusSebelumnya = $this->pengaduan->status;
        $fotoStatus = $this->foto_status?->store('tanggapan-pengaduan', 'public');

        $this->pengaduan->update(['status' => $validated['status']]);

        if ($validated['status'] === Pengaduan::STATUS_DITOLAK && $this->pengaduan->wasChanged('status')) {
            TanggapanPengaduan::create([
                'pengaduan_id' => $this->pengaduan->id,
                'admin_id' => auth()->id(),
                'isi_tanggapan' => __('Pengaduan ditolak. Alasan: :alasan', [
                    'alasan' => $validated['alasan_penolakan'],
                ]),
                'status' => $validated['status'],
            ]);
        }

        if ($validated['status'] === Pengaduan::STATUS_SELESAI && $this->pengaduan->wasChanged('status')) {
            TanggapanPengaduan::create([
                'pengaduan_id' => $this->pengaduan->id,
                'admin_id' => auth()->id(),
                'isi_tanggapan' => __('Pengaduan selesai ditangani. Foto bukti penyelesaian telah dilampirkan.'),
                'status' => $validated['status'],
                'foto' => $fotoStatus,
            ]);
        }

        if ($this->pengaduan->wasChanged('status')) {
            $this->notifyStatusChanged($statusSebelumnya, $validated['alasan_penolakan'] ?? null);
        }

        $this->alasan_penolakan = '';
        $this->foto_status = null;
        $this->pengaduan->refresh()->load(['user', 'tanggapan.admin']);
    }

    public function respond(): void
    {
        Gate::authorize('respond', $this->pengaduan);

        $validated = $this->validate([
            'isi_tanggapan' => ['required', 'string'],
            'foto_tanggapan' => ['nullable', 'image', 'max:2048'],
        ]);

        TanggapanPengaduan::create([
            'pengaduan_id' => $this->pengaduan->id,
            'admin_id' => auth()->id(),
            'isi_tanggapan' => $validated['isi_tanggapan'],
            'status' => $this->pengaduan->status === Pengaduan::STATUS_PENDING
                ? Pengaduan::STATUS_DIPROSES
                : $this->pengaduan->status,
            'foto' => $this->foto_tanggapan?->store('tanggapan-pengaduan', 'public'),
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
        $this->foto_tanggapan = null;
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

    private function statusBannerClasses(): string
    {
        return match ($this->pengaduan->status) {
            Pengaduan::STATUS_SELESAI => 'from-emerald-700 to-emerald-600',
            Pengaduan::STATUS_DIPROSES => 'from-sky-700 to-sky-600',
            Pengaduan::STATUS_DITOLAK => 'from-red-700 to-red-600',
            default => 'from-amber-600 to-amber-500',
        };
    }

    private function tanggapanStatusClasses(?string $status): string
    {
        return match ($status) {
            Pengaduan::STATUS_SELESAI => 'bg-emerald-100 text-emerald-800 ring-emerald-600/20',
            Pengaduan::STATUS_DIPROSES => 'bg-sky-100 text-sky-800 ring-sky-600/20',
            Pengaduan::STATUS_DITOLAK => 'bg-red-100 text-red-800 ring-red-600/20',
            default => 'bg-amber-100 text-amber-800 ring-amber-600/20',
        };
    }
};
?>

<section class="mx-auto w-full max-w-5xl overflow-hidden rounded-2xl border border-[#dfd4c6] bg-white text-[#332920] shadow-[0_14px_36px_rgba(62,44,29,.13)]">
    <div class="bg-gradient-to-r {{ $this->statusBannerClasses() }} px-5 py-4 text-white sm:px-7">
        <div class="flex items-start justify-between gap-5">
            <div>
                <p class="text-base font-extrabold uppercase tracking-wide sm:text-lg">{{ __('Status Pengaduan: :status', ['status' => $pengaduan->status]) }}</p>
                <p class="mt-1 text-xs text-white/85 sm:text-sm">
                    {{ __('Diajukan oleh :nama', ['nama' => $pengaduan->user->name]) }}
                    <span aria-hidden="true">&middot;</span>
                    {{ $pengaduan->created_at->format('d M Y H:i') }}
                </p>
            </div>
            <a href="{{ route('pengaduan.index') }}" wire:navigate class="shrink-0 rounded-lg bg-white/15 px-4 py-2 text-xs font-semibold text-white ring-1 ring-white/25 transition hover:bg-white/25">
                {{ __('Kembali') }}
            </a>
        </div>
    </div>

    <div class="space-y-7 px-5 py-7 sm:px-7">
        <div class="border-b border-[#e8ddd0] pb-6">
            <h1 class="font-serif text-3xl font-bold leading-tight text-[#2f241b] sm:text-4xl">{{ $pengaduan->judul }}</h1>
            <div class="mt-5">
                <h2 class="text-base font-extrabold text-[#3b3027]">{{ __('Deskripsi Masalah') }}</h2>
                <p class="mt-2 whitespace-pre-line text-sm leading-7 text-[#5f574f]">{{ $pengaduan->isi_pengaduan }}</p>
            </div>
        </div>

        <div class="space-y-3">
            <div>
                <h2 class="text-base font-extrabold text-[#3b3027]">{{ __('Foto Pengaduan') }}</h2>
                <p class="mt-1 text-xs text-[#81776e]">{{ __('Dokumen dan foto pendukung dari masyarakat.') }}</p>
            </div>
            @if ($this->fotoPaths())
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($this->fotoPaths() as $index => $foto)
                        <button type="button" wire:click="openFotoPreview('{{ addslashes($foto) }}', '{{ addslashes($pengaduan->judul.' '.($index + 1)) }}')" class="group block w-full overflow-hidden rounded-xl border border-[#dfd4c6] bg-[#f5efe6] text-left shadow-sm">
                            <img
                                src="{{ $this->fotoUrl($foto) }}"
                                alt="{{ $pengaduan->judul }} {{ $index + 1 }}"
                                class="aspect-video w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                            >
                            <span class="block px-3 py-2 text-center text-xs font-semibold text-[#655b52]">{{ __('Lampiran :nomor', ['nomor' => $index + 1]) }}</span>
                        </button>
                    @endforeach
                </div>
            @else
                <div class="flex min-h-40 items-center justify-center rounded-xl border border-dashed border-[#cbbba9] bg-[#fffaf2] text-sm text-[#81776e]">
                    {{ __('Tidak ada foto pendukung.') }}
                </div>
            @endif
        </div>
    </div>

    @can('pengaduan.verify')
        <form wire:submit="saveTindakan" class="border-t-8 border-[#f7f2ea] bg-[#fffaf4]">
            <div class="flex items-center gap-3 border-b border-[#dfd4c6] bg-[#efe4d5] px-6 py-4">
                <h2 class="font-extrabold text-[#3b3027]">{{ __('Tindakan Petugas') }}</h2>
            </div>

            <div class="space-y-5 px-4 py-5">
                <div>
                    <p class="mb-2 text-sm font-bold">{{ __('Status Saat Ini') }}</p>
                    <div class="grid gap-3 md:grid-cols-3">
                        @foreach ($this->actionStatuses() as $statusValue => $statusLabel)
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-bold text-[#554b42] ring-1 ring-[#dfd4c6] transition has-[:checked]:bg-[#eaf7f4] has-[:checked]:text-[#13746e] has-[:checked]:ring-[#13746e]">
                                <input type="radio" wire:model.live="status" value="{{ $statusValue }}" class="size-3 border-zinc-400 text-zinc-700 focus:ring-zinc-500">
                                <span>{{ $statusLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('status') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="isi_tanggapan" class="text-sm font-bold">{{ __('Tanggapan Resmi') }}</label>
                    <textarea id="isi_tanggapan" wire:model="isi_tanggapan" rows="5" class="mt-2 w-full resize-none rounded-lg border border-zinc-100 bg-zinc-100 px-4 py-3 text-sm text-zinc-900 outline-none transition focus:border-zinc-400 focus:ring-2 focus:ring-zinc-400/30 dark:border-zinc-800 dark:bg-zinc-800 dark:text-white"></textarea>
                    @error('isi_tanggapan') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="foto_tanggapan" class="text-sm font-bold">
                        {{ __('Unggah Foto Lampiran') }}
                        <span class="font-medium text-zinc-600 dark:text-zinc-400">
                            {{ $status === Pengaduan::STATUS_SELESAI && $pengaduan->status !== Pengaduan::STATUS_SELESAI ? __('(Wajib, 1 foto)') : __('(Opsional, 1 foto)') }}
                        </span>
                    </label>
                    <input id="foto_tanggapan" wire:model="foto_tanggapan" type="file" accept="image/*" class="mt-2 block w-full rounded-lg bg-zinc-100 text-sm text-zinc-700 file:mr-4 file:rounded-md file:border-0 file:bg-zinc-300 file:px-4 file:py-2 file:text-sm file:font-bold file:text-zinc-700 hover:file:bg-zinc-400 dark:bg-zinc-800 dark:text-zinc-200 dark:file:bg-zinc-700 dark:file:text-zinc-100">
                    <p class="mt-1 text-xs font-semibold text-zinc-600 dark:text-zinc-400">{{ __('Format PNG/JPG Maks. 2MB') }}</p>
                    @error('foto_tanggapan') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                    @if ($foto_tanggapan)
                        <div class="mt-3 max-w-56">
                            <img
                                src="{{ $foto_tanggapan->temporaryUrl() }}"
                                alt="{{ __('Preview foto tanggapan') }}"
                                class="aspect-video w-full rounded-lg border border-zinc-300 object-cover dark:border-zinc-700"
                            >
                        </div>
                    @endif
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="rounded-xl bg-[#13746e] px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#0f625d]">
                        {{ __('Simpan Tanggapan') }}
                    </button>
                </div>
            </div>
        </form>
    @endcan

    @if ($pengaduan->tanggapan->isNotEmpty())
        <div class="border-t-8 border-[#f7f2ea] bg-[#fbf8f3] px-5 py-7 sm:px-7">
            <h2 class="text-base font-extrabold uppercase tracking-wide text-[#3b3027]">{{ __('Riwayat Tanggapan & Penyelesaian') }}</h2>
            <div class="relative mt-6 space-y-5 before:absolute before:bottom-5 before:left-5 before:top-5 before:w-px before:bg-[#c9d7d4]">
                @foreach ($pengaduan->tanggapan as $tanggapan)
                    <div class="relative flex gap-4">
                        <div class="z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#13746e] text-white shadow-sm ring-4 ring-[#fbf8f3]">
                            <flux:icon name="user" class="size-5" />
                        </div>
                        <div class="min-w-0 flex-1 rounded-xl border border-[#dfd4c6] bg-white p-4 text-sm shadow-sm">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <p class="font-bold text-[#3b3027]">{{ $tanggapan->admin?->name ?? __('Admin Banjar') }} <span class="font-medium text-[#81776e]">&middot; {{ $tanggapan->created_at->format('d M Y H:i') }}</span></p>
                                @if ($tanggapan->status)
                                    <span class="inline-flex w-fit shrink-0 rounded-full px-2.5 py-1 text-[11px] font-extrabold uppercase ring-1 ring-inset {{ $this->tanggapanStatusClasses($tanggapan->status) }}">
                                        {{ $tanggapan->status }}
                                    </span>
                                @endif
                            </div>
                            <p class="mt-2 whitespace-pre-line leading-6 text-[#5f574f]">{{ $tanggapan->isi_tanggapan }}</p>
                            @if ($tanggapan->foto)
                                <div class="mt-4 rounded-xl border border-[#e8ddd0] bg-[#fffaf2] p-3">
                                    <p class="mb-2 text-xs font-extrabold uppercase tracking-wide text-[#655b52]">{{ __('Dokumen Hasil Penanganan') }}</p>
                                    <button type="button" wire:click="openFotoPreview('{{ addslashes($tanggapan->foto) }}', '{{ __('Foto tanggapan') }}')" class="block max-w-sm overflow-hidden rounded-lg text-left">
                                        <img src="{{ $this->fotoUrl($tanggapan->foto) }}" alt="{{ __('Foto tanggapan') }}" class="aspect-video w-full rounded-lg object-cover">
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <flux:modal name="preview-foto-pengaduan" wire:model="showFotoModal" focusable class="max-w-5xl">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Preview Foto') }}</flux:heading>
            </div>

            @if ($previewFotoUrl)
                <div class="overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-900">
                    <img
                        src="{{ $previewFotoUrl }}"
                        alt="{{ $previewFotoAlt }}"
                        class="max-h-[75vh] w-full object-contain"
                    >
                </div>
            @endif
        </div>
    </flux:modal>
</section>

