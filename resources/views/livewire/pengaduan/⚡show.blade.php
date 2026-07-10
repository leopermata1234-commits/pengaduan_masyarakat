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

        if (filled($validated['isi_tanggapan']) || $fotoTanggapan) {
            TanggapanPengaduan::create([
                'pengaduan_id' => $this->pengaduan->id,
                'admin_id' => auth()->id(),
                'isi_tanggapan' => filled($validated['isi_tanggapan'])
                    ? $validated['isi_tanggapan']
                    : __('Pengaduan selesai ditangani. Foto bukti penyelesaian telah dilampirkan.'),
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
            ]);
        }

        if ($validated['status'] === Pengaduan::STATUS_SELESAI && $this->pengaduan->wasChanged('status')) {
            TanggapanPengaduan::create([
                'pengaduan_id' => $this->pengaduan->id,
                'admin_id' => auth()->id(),
                'isi_tanggapan' => __('Pengaduan selesai ditangani. Foto bukti penyelesaian telah dilampirkan.'),
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
};
?>

<section class="mx-auto w-full max-w-6xl overflow-hidden rounded-lg bg-zinc-200 text-zinc-950 shadow-sm dark:bg-zinc-900 dark:text-white">
    <div class="flex items-center justify-between gap-4 bg-zinc-300 px-4 py-3 dark:bg-zinc-800">
        <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-zinc-400 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                <flux:icon name="user" class="size-5" />
            </div>
            <div class="min-w-0">
                <p class="truncate text-base font-bold">{{ $pengaduan->user->name }}</p>
                <span class="inline-flex rounded bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-zinc-700 dark:bg-zinc-700 dark:text-zinc-100">
                    {{ $pengaduan->created_at->format('d M Y') }}
                </span>
            </div>
        </div>

        <a href="{{ route('pengaduan.index') }}" wire:navigate class="h-6 w-16 rounded-md bg-zinc-100 text-center text-xs font-semibold leading-6 text-zinc-600 transition hover:bg-white dark:bg-zinc-700 dark:text-zinc-200">
            {{ __('Back') }}
        </a>
    </div>

    <div class="space-y-5 px-4 py-6">
        <div class="max-w-3xl">
            <h1 class="text-2xl font-bold leading-8">{{ $pengaduan->judul }}</h1>
            <p class="mt-3 whitespace-pre-line text-sm leading-6 text-zinc-800 dark:text-zinc-200">{{ $pengaduan->isi_pengaduan }}</p>
        </div>

        <div class="space-y-3">
            <p class="text-sm font-bold">{{ __('Foto Pengaduan') }}</p>
            @if ($this->fotoPaths())
                <div class="grid max-w-3xl gap-4 sm:grid-cols-3">
                    @foreach ($this->fotoPaths() as $index => $foto)
                        <button type="button" wire:click="openFotoPreview('{{ addslashes($foto) }}', '{{ addslashes($pengaduan->judul.' '.($index + 1)) }}')" class="block w-full overflow-hidden rounded-lg bg-zinc-300 text-left dark:bg-zinc-800">
                            <img
                                src="{{ $this->fotoUrl($foto) }}"
                                alt="{{ $pengaduan->judul }} {{ $index + 1 }}"
                                class="aspect-square w-full object-cover"
                            >
                        </button>
                    @endforeach
                </div>
            @else
                <div class="grid max-w-3xl gap-4 sm:grid-cols-3">
                    @for ($index = 0; $index < 3; $index++)
                        <div class="flex aspect-square items-center justify-center rounded-lg bg-zinc-300 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-500">
                            <flux:icon name="photo" class="size-16" />
                        </div>
                    @endfor
                </div>
            @endif
        </div>
    </div>

    @can('pengaduan.verify')
        <form wire:submit="saveTindakan" class="border-t-8 border-zinc-100 bg-zinc-200 dark:border-zinc-950 dark:bg-zinc-900">
            <div class="flex items-center gap-3 bg-zinc-300 px-4 py-3 dark:bg-zinc-800">
                <h2 class="font-bold">{{ __('Tindakan') }}</h2>
            </div>

            <div class="space-y-5 px-4 py-5">
                <div>
                    <p class="mb-2 text-sm font-bold">{{ __('Status Saat Ini') }}</p>
                    <div class="grid gap-3 md:grid-cols-3">
                        @foreach ($this->actionStatuses() as $statusValue => $statusLabel)
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg bg-zinc-100 px-4 py-3 text-sm font-bold text-zinc-800 ring-1 ring-transparent transition has-[:checked]:bg-white has-[:checked]:ring-zinc-500 dark:bg-zinc-800 dark:text-zinc-100 dark:has-[:checked]:bg-zinc-700">
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
                    <button type="submit" class="rounded-lg bg-zinc-300 px-5 py-3 text-sm font-bold text-zinc-950 transition hover:bg-zinc-400 dark:bg-zinc-700 dark:text-white dark:hover:bg-zinc-600">
                        {{ __('Simpan Tanggapan') }}
                    </button>
                </div>
            </div>
        </form>
    @endcan

    @if ($pengaduan->tanggapan->isNotEmpty())
        <div class="border-t border-zinc-300 bg-zinc-100 px-4 py-5 dark:border-zinc-800 dark:bg-zinc-950">
            <h2 class="font-bold">{{ __('Riwayat Tanggapan') }}</h2>
            <div class="mt-3 space-y-3">
                @foreach ($pengaduan->tanggapan as $tanggapan)
                    <div class="rounded-lg bg-white p-4 text-sm shadow-sm dark:bg-zinc-900">
                        <p class="font-semibold text-zinc-600 dark:text-zinc-400">{{ $tanggapan->admin?->name ?? __('Admin') }} &middot; {{ $tanggapan->created_at->format('d M Y H:i') }}</p>
                        <p class="mt-2 whitespace-pre-line text-zinc-800 dark:text-zinc-100">{{ $tanggapan->isi_tanggapan }}</p>
                        @if ($tanggapan->foto)
                            <button type="button" wire:click="openFotoPreview('{{ addslashes($tanggapan->foto) }}', '{{ __('Foto tanggapan') }}')" class="mt-3 block max-w-sm overflow-hidden rounded-lg text-left">
                                <img src="{{ $this->fotoUrl($tanggapan->foto) }}" alt="{{ __('Foto tanggapan') }}" class="aspect-video w-full rounded-lg object-cover">
                            </button>
                        @endif
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

