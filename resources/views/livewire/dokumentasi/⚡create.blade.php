<?php

use App\Models\DokumentasiKegiatan;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Tambah Dokumentasi')] class extends Component
{
    use WithFileUploads;

    public string $judul = '';
    public string $deskripsi = '';
    public string $tanggal = '';
    public array $fotos = [];

    public function save(): void
    {
        Gate::authorize('create', DokumentasiKegiatan::class);

        $validated = $this->validate([
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string'],
            'tanggal' => ['required', 'date'],
            'fotos' => ['required', 'array', 'min:1'],
            'fotos.*' => ['image', 'max:2048'],
        ]);

        $storedFotos = collect($this->fotos)
            ->map(fn ($foto) => $foto->store('dokumentasi', 'public'))
            ->values()
            ->all();

        DokumentasiKegiatan::create([
            'user_id' => auth()->id(),
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'],
            'tanggal' => $validated['tanggal'],
            'status' => DokumentasiKegiatan::STATUS_PUBLISHED,
            'foto' => $storedFotos[0] ?? null,
            'fotos' => $storedFotos,
        ]);

        $this->redirectRoute('dokumentasi.index', navigate: true);
    }
};
?>

<section class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-2 text-sm text-zinc-500">
            <a href="{{ route('dokumentasi.index') }}" wire:navigate>{{ __('Dokumentasi') }}</a>
            <span>/</span>
            <span>{{ __('Tambah') }}</span>
        </div>
        <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Tambah Dokumentasi') }}</h1>
    </div>

    <form wire:submit="save" class="space-y-5 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:input wire:model="judul" :label="__('Judul')" required />
        <flux:textarea wire:model="deskripsi" :label="__('Deskripsi')" rows="6" required />
        <flux:input wire:model="tanggal" :label="__('Tanggal')" type="date" required />
        <flux:input wire:model="fotos" :label="__('Foto Dokumentasi')" type="file" accept="image/*" multiple />

        @if ($fotos)
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach ($fotos as $foto)
                    <img src="{{ $foto->temporaryUrl() }}" alt="Preview foto dokumentasi" class="aspect-video rounded-lg border border-zinc-200 object-cover dark:border-zinc-700">
                @endforeach
            </div>
        @endif

        <div class="flex justify-end gap-2">
            <flux:button variant="filled" :href="route('dokumentasi.index')" wire:navigate>{{ __('Batal') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button>
        </div>
    </form>
</section>
