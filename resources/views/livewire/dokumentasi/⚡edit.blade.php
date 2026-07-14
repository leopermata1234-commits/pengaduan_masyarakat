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

<section class="mx-auto w-full max-w-6xl overflow-hidden rounded-2xl border border-[#d8c8b5] bg-[#f8f1e7] text-[#352b22] shadow-[0_10px_28px_rgba(62,44,29,.14)]">
    <div class="flex items-center justify-between gap-4 bg-[linear-gradient(135deg,#776352,#9b7b5a_55%,#6d5543)] px-5 py-4 text-white">
        <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#f3e3ca] text-[#765437] shadow-inner">
                <flux:icon name="photo" class="size-5" />
            </div>
            <div class="min-w-0">
                <p class="truncate text-base font-bold">{{ __('Edit Galeri') }}</p>
                <span class="inline-flex rounded-md bg-white/15 px-2 py-0.5 text-xs font-semibold text-[#fff4e5] ring-1 ring-white/20">
                    {{ $dokumentasiKegiatan->tanggal->format('d M Y') }}
                </span>
            </div>
        </div>

        <a href="{{ route('dokumentasi.index') }}" wire:navigate class="rounded-lg bg-[#f3e3ca] px-4 py-2 text-xs font-bold text-[#59412e] transition hover:bg-white">
            {{ __('Back') }}
        </a>
    </div>

    <form wire:submit="save">
        <div class="space-y-5 px-5 py-7 sm:px-7">
            <div class="max-w-3xl">
                <label for="judul" class="text-sm font-bold">{{ __('Judul Galeri') }}</label>
                <input id="judul" wire:model="judul" type="text" required class="mt-2 w-full rounded-xl border border-[#ddcfbd] bg-[#fffdf9] px-4 py-3 text-sm font-semibold text-[#352b22] outline-none transition focus:border-[#17827a] focus:ring-2 focus:ring-[#17827a]/20">
                @error('judul') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="max-w-3xl">
                <label for="deskripsi" class="text-sm font-bold">{{ __('Deskripsi') }}</label>
                <textarea id="deskripsi" wire:model="deskripsi" rows="7" required class="mt-2 w-full resize-none rounded-xl border border-[#ddcfbd] bg-[#fffdf9] px-4 py-3 text-sm text-[#352b22] outline-none transition focus:border-[#17827a] focus:ring-2 focus:ring-[#17827a]/20"></textarea>
                @error('deskripsi') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="max-w-3xl">
                <label for="tanggal" class="text-sm font-bold">{{ __('Tanggal') }}</label>
                <input id="tanggal" wire:model="tanggal" type="date" required class="mt-2 w-full rounded-xl border border-[#ddcfbd] bg-[#fffdf9] px-4 py-3 text-sm font-semibold text-[#352b22] outline-none transition focus:border-[#17827a] focus:ring-2 focus:ring-[#17827a]/20">
                @error('tanggal') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-3">
                <p class="text-sm font-bold">{{ __('Foto Galeri') }}</p>
                @if ($existingFotos)
                    <div class="grid max-w-3xl gap-4 sm:grid-cols-3">
                        @foreach ($existingFotos as $index => $foto)
                            <div class="relative overflow-hidden rounded-xl bg-[#eadcc9] ring-1 ring-[#d8c8b5]">
                                <a href="{{ $this->fotoUrl($foto) }}" target="_blank" class="block">
                                    <img src="{{ $this->fotoUrl($foto) }}" alt="Foto galeri" class="aspect-square w-full object-cover">
                                </a>
                                <button type="button" wire:click="removeExistingFoto({{ $index }})" class="absolute right-2 top-2 rounded-lg bg-[#fffaf2] px-2.5 py-1.5 text-xs font-bold text-red-700 shadow">{{ __('Hapus') }}</button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="grid max-w-3xl gap-4 sm:grid-cols-3">
                        <div class="flex aspect-square items-center justify-center rounded-xl bg-[#eadcc9] text-[#9a8066] ring-1 ring-[#d8c8b5]">
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
                            <img src="{{ $foto->temporaryUrl() }}" alt="Preview foto galeri" class="aspect-square w-full rounded-xl bg-[#eadcc9] object-cover ring-1 ring-[#d8c8b5]">
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="border-t border-[#d8c8b5] bg-[#f1e5d5]">
            <div class="space-y-5 px-5 py-5 sm:px-7">
                <div>
                    <label for="fotos" class="text-sm font-bold">{{ __('Tambah Foto Galeri') }} <span class="font-medium text-[#756858]">{{ __('(Opsional, bisa lebih dari 1 foto)') }}</span></label>
                    <input id="fotos" wire:model="fotos" type="file" accept="image/*" multiple class="mt-2 block w-full rounded-xl border border-[#ddcfbd] bg-[#fffdf9] text-sm text-[#5b4938] file:mr-4 file:border-0 file:bg-[#d9c19f] file:px-4 file:py-3 file:text-sm file:font-bold file:text-[#4b3827] hover:file:bg-[#cdae84]">
                    <p class="mt-1 text-xs font-semibold text-[#756858]">{{ __('Format PNG/JPG Maks. 2MB per foto') }}</p>
                    @error('fotos') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    @error('fotos.*') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('dokumentasi.index') }}" wire:navigate class="rounded-xl border border-[#cdbca8] bg-[#fffaf2] px-5 py-3 text-sm font-bold text-[#65503d] transition hover:bg-white">
                        {{ __('Batal') }}
                    </a>
                    <button type="submit" class="rounded-xl bg-[#13746e] px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-[#0f625d]">
                        {{ __('Simpan Galeri') }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</section>
