<?php

use App\Models\DokumentasiKegiatan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Edit Galeri')] class extends Component
{
    use WithFileUploads;

    public DokumentasiKegiatan $dokumentasiKegiatan;

    public string $judul = '';
    public string $deskripsi = '';
    public string $tanggal = '';
    public array $fotos = [];

    public array $existingFotos = [];

    public function fotoUrl(?string $foto): ?string
    {
        if (! $foto) {
            return null;
        }

        if (Str::startsWith($foto, ['http://', 'https://', '/storage/'])) {
            return $foto;
        }

        return '/storage/'.Str::of($foto)->ltrim('/');
    }

    public function mount(DokumentasiKegiatan $dokumentasiKegiatan): void
    {
        Gate::authorize('update', $dokumentasiKegiatan);

        $this->dokumentasiKegiatan = $dokumentasiKegiatan;
        $this->judul = $dokumentasiKegiatan->judul;
        $this->deskripsi = $dokumentasiKegiatan->deskripsi;
        $this->tanggal = $dokumentasiKegiatan->tanggal->format('Y-m-d');
        $this->existingFotos = $dokumentasiKegiatan->fotos ?? ($dokumentasiKegiatan->foto ? [$dokumentasiKegiatan->foto] : []);
    }

    public function removeExistingFoto(int $index): void
    {
        if (! isset($this->existingFotos[$index])) {
            return;
        }

        Storage::disk('public')->delete($this->existingFotos[$index]);
        unset($this->existingFotos[$index]);
        $this->existingFotos = array_values($this->existingFotos);
    }

    public function save(): void
    {
        Gate::authorize('update', $this->dokumentasiKegiatan);

        $validated = $this->validate([
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string'],
            'tanggal' => ['required', 'date'],
            'fotos' => ['nullable', 'array'],
            'fotos.*' => ['image', 'max:2048'],
        ]);

        $newFotos = collect($this->fotos)
            ->map(fn ($foto) => $foto->store('dokumentasi', 'public'))
            ->values()
            ->all();

        $storedFotos = array_values([...$this->existingFotos, ...$newFotos]);

        $this->dokumentasiKegiatan->update([
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'],
            'tanggal' => $validated['tanggal'],
            'foto' => $storedFotos[0] ?? null,
            'fotos' => $storedFotos,
        ]);

        $this->redirectRoute('dokumentasi.index', navigate: true);
    }
};
?>

<section class="mx-auto w-full max-w-6xl overflow-hidden rounded-lg bg-zinc-200 text-zinc-950 shadow-sm dark:bg-zinc-900 dark:text-white">
    <div class="flex items-center justify-between gap-4 bg-zinc-300 px-4 py-3 dark:bg-zinc-800">
        <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-zinc-400 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                <flux:icon name="photo" class="size-5" />
            </div>
            <div class="min-w-0">
                <p class="truncate text-base font-bold">{{ __('Edit Galeri') }}</p>
                <span class="inline-flex rounded bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-zinc-700 dark:bg-zinc-700 dark:text-zinc-100">
                    {{ $dokumentasiKegiatan->tanggal->format('d M Y') }}
                </span>
            </div>
        </div>

        <a href="{{ route('dokumentasi.index') }}" wire:navigate class="h-6 w-16 rounded-md bg-zinc-100 text-center text-xs font-semibold leading-6 text-zinc-600 transition hover:bg-white dark:bg-zinc-700 dark:text-zinc-200">
            {{ __('Back') }}
        </a>
    </div>

    <form wire:submit="save">
        <div class="space-y-5 px-4 py-6">
            <div class="max-w-3xl">
                <label for="judul" class="text-sm font-bold">{{ __('Judul Galeri') }}</label>
                <input id="judul" wire:model="judul" type="text" required class="mt-2 w-full rounded-lg border border-zinc-100 bg-zinc-100 px-4 py-3 text-sm font-semibold text-zinc-900 outline-none transition focus:border-zinc-400 focus:ring-2 focus:ring-zinc-400/30 dark:border-zinc-800 dark:bg-zinc-800 dark:text-white">
                @error('judul') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="max-w-3xl">
                <label for="deskripsi" class="text-sm font-bold">{{ __('Deskripsi') }}</label>
                <textarea id="deskripsi" wire:model="deskripsi" rows="7" required class="mt-2 w-full resize-none rounded-lg border border-zinc-100 bg-zinc-100 px-4 py-3 text-sm text-zinc-900 outline-none transition focus:border-zinc-400 focus:ring-2 focus:ring-zinc-400/30 dark:border-zinc-800 dark:bg-zinc-800 dark:text-white"></textarea>
                @error('deskripsi') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="max-w-3xl">
                <label for="tanggal" class="text-sm font-bold">{{ __('Tanggal') }}</label>
                <input id="tanggal" wire:model="tanggal" type="date" required class="mt-2 w-full rounded-lg border border-zinc-100 bg-zinc-100 px-4 py-3 text-sm font-semibold text-zinc-900 outline-none transition focus:border-zinc-400 focus:ring-2 focus:ring-zinc-400/30 dark:border-zinc-800 dark:bg-zinc-800 dark:text-white">
                @error('tanggal') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-3">
                <p class="text-sm font-bold">{{ __('Foto Galeri') }}</p>
                @if ($existingFotos)
                    <div class="grid max-w-3xl gap-4 sm:grid-cols-3">
                        @foreach ($existingFotos as $index => $foto)
                            <div class="relative overflow-hidden rounded-lg bg-zinc-300 dark:bg-zinc-800">
                                <a href="{{ $this->fotoUrl($foto) }}" target="_blank" class="block">
                                    <img src="{{ $this->fotoUrl($foto) }}" alt="Foto galeri" class="aspect-square w-full object-cover">
                                </a>
                                <button type="button" wire:click="removeExistingFoto({{ $index }})" class="absolute right-2 top-2 rounded bg-white px-2 py-1 text-xs font-bold text-red-600 shadow dark:bg-zinc-900">{{ __('Hapus') }}</button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="grid max-w-3xl gap-4 sm:grid-cols-3">
                        <div class="flex aspect-square items-center justify-center rounded-lg bg-zinc-300 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-500">
                            <flux:icon name="photo" class="size-16" />
                        </div>
                    </div>
                @endif
            </div>

            @if ($fotos)
                <div class="space-y-3">
                    <p class="text-sm font-bold">{{ __('Preview Foto Baru') }}</p>
                    <div class="grid max-w-3xl gap-4 sm:grid-cols-3">
                        @foreach ($fotos as $foto)
                            <img src="{{ $foto->temporaryUrl() }}" alt="Preview foto galeri" class="aspect-square w-full rounded-lg bg-zinc-300 object-cover dark:bg-zinc-800">
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="border-t-8 border-zinc-100 bg-zinc-200 dark:border-zinc-950 dark:bg-zinc-900">
            <div class="space-y-5 px-4 py-5">
                <div>
                    <label for="fotos" class="text-sm font-bold">{{ __('Tambah Foto Galeri') }} <span class="font-medium text-zinc-600 dark:text-zinc-400">{{ __('(Opsional, bisa lebih dari 1 foto)') }}</span></label>
                    <input id="fotos" wire:model="fotos" type="file" accept="image/*" multiple class="mt-2 block w-full rounded-lg bg-zinc-100 text-sm text-zinc-700 file:mr-4 file:rounded-md file:border-0 file:bg-zinc-300 file:px-4 file:py-2 file:text-sm file:font-bold file:text-zinc-700 hover:file:bg-zinc-400 dark:bg-zinc-800 dark:text-zinc-200 dark:file:bg-zinc-700 dark:file:text-zinc-100">
                    <p class="mt-1 text-xs font-semibold text-zinc-600 dark:text-zinc-400">{{ __('Format PNG/JPG Maks. 2MB per foto') }}</p>
                    @error('fotos') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    @error('fotos.*') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('dokumentasi.index') }}" wire:navigate class="rounded-lg bg-zinc-100 px-5 py-3 text-sm font-bold text-zinc-700 transition hover:bg-white dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">
                        {{ __('Batal') }}
                    </a>
                    <button type="submit" class="rounded-lg bg-zinc-300 px-5 py-3 text-sm font-bold text-zinc-950 transition hover:bg-zinc-400 dark:bg-zinc-700 dark:text-white dark:hover:bg-zinc-600">
                        {{ __('Simpan Galeri') }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</section>
