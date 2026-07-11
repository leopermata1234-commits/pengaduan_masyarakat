<?php

use App\Models\Pengaduan;
use App\Models\User;
use App\Notifications\PengaduanBaruNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Buat Pengaduan')] class extends Component
{
    use WithFileUploads;

    public string $judul = '';

    public string $isi_pengaduan = '';

    public string $visibilitas = Pengaduan::VISIBILITAS_PUBLIK;

    public array $fotos = [];

    public function save(): void
    {
        Gate::authorize('create', Pengaduan::class);

        $validated = $this->validate([
            'judul' => ['required', 'string', 'max:255'],
            'isi_pengaduan' => ['required', 'string'],
            'visibilitas' => ['required', Rule::in(Pengaduan::VISIBILITAS)],
            'fotos' => ['nullable', 'array', 'max:5'],
            'fotos.*' => ['image', 'max:2048'],
        ]);

        $storedFotos = collect($this->fotos)
            ->map(fn ($foto) => $foto->store('pengaduan', 'public'))
            ->values()
            ->all();

        $pengaduan = Pengaduan::create([
            'user_id' => auth()->id(),
            'judul' => $validated['judul'],
            'isi_pengaduan' => $validated['isi_pengaduan'],
            'foto' => $storedFotos[0] ?? null,
            'fotos' => $storedFotos,
            'status' => Pengaduan::STATUS_PENDING,
            'visibilitas' => $validated['visibilitas'],
        ]);

        try {
            $penerima = User::permission('pengaduan.notifikasi-email')
                ->whereKeyNot(auth()->id())
                ->get();

            Notification::send($penerima, new PengaduanBaruNotification($pengaduan->load('user')));
        } catch (Throwable $e) {
            report($e);
        }

        $this->redirectRoute('pengaduan.index', navigate: true);
    }
};
?>

<section class="mx-auto flex w-full max-w-5xl flex-col gap-6">
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-2 text-sm text-zinc-500"><a href="{{ route('pengaduan.index') }}" wire:navigate>{{ __('Pengaduan') }}</a><span>/</span><span>{{ __('Buat') }}</span></div>
        <h1 class="font-serif text-3xl font-bold text-[#2f241b] sm:text-4xl">{{ __('Buat Pengaduan') }}</h1>
    </div>
    <form wire:submit="save" class="space-y-5 rounded-2xl border border-[#dfd4c6] bg-white p-6 shadow-[0_10px_28px_rgba(62,44,29,.10)]">
        <flux:input wire:model="judul" :label="__('Judul')" required />
        <flux:textarea wire:model="isi_pengaduan" :label="__('Isi Pengaduan')" rows="6" required />
        <flux:select wire:model="visibilitas" :label="__('Sifat')">
            @foreach (Pengaduan::VISIBILITAS as $visibilitasOption)
                <flux:select.option value="{{ $visibilitasOption }}">{{ $visibilitasOption }}</flux:select.option>
            @endforeach
        </flux:select>
        <div class="space-y-2">
            <label for="fotos" class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Foto') }}</label>
            <input
                id="fotos"
                wire:model="fotos"
                type="file"
                accept="image/*"
                multiple
                class="block w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 file:mr-4 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-zinc-700 hover:file:bg-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-200"
            />
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Maksimal 5 foto, masing-masing 2 MB.') }}</p>
            @error('fotos') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            @error('fotos.*') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        @if ($fotos)
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach ($fotos as $foto)
                    <img src="{{ $foto->temporaryUrl() }}" alt="Preview foto pengaduan" class="aspect-video rounded-lg border border-zinc-200 object-cover dark:border-zinc-700">
                @endforeach
            </div>
        @endif
        <div class="flex justify-end gap-2">
            <flux:button variant="filled" :href="route('pengaduan.index')" wire:navigate>{{ __('Batal') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button>
        </div>
    </form>
</section>
