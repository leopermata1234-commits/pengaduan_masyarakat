<?php

use App\Models\DokumentasiKegiatan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Edit Dokumentasi')] class extends Component
{
    use WithFileUploads;

    public DokumentasiKegiatan $dokumentasiKegiatan;

    public string $judul = '';
    public string $deskripsi = '';
    public string $tanggal = '';
    public string $status = '';
    public array $fotos = [];

    public array $existingFotos = [];

    public function mount(DokumentasiKegiatan $dokumentasiKegiatan): void
    {
        Gate::authorize('update', $dokumentasiKegiatan);

        $this->dokumentasiKegiatan = $dokumentasiKegiatan;
        $this->judul = $dokumentasiKegiatan->judul;
        $this->deskripsi = $dokumentasiKegiatan->deskripsi;
        $this->tanggal = $dokumentasiKegiatan->tanggal->format('Y-m-d');
        $this->status = $dokumentasiKegiatan->status;
        $this->existingFotos = $dokumentasiKegiatan->fotos ?? ($dokumentasiKegiatan->foto ? [$dokumentasiKegiatan->foto] : []);
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return [
            DokumentasiKegiatan::STATUS_DRAFT,
            DokumentasiKegiatan::STATUS_PUBLISHED,
        ];
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
            'status' => ['required', Rule::in($this->statusOptions)],
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
            'status' => $validated['status'],
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
            <span>{{ __('Edit') }}</span>
        </div>
        <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Edit Dokumentasi') }}</h1>
    </div>

    <form wire:submit="save" class="space-y-5 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:input wire:model="judul" :label="__('Judul')" required />
        <flux:textarea wire:model="deskripsi" :label="__('Deskripsi')" rows="6" required />
        <flux:input wire:model="tanggal" :label="__('Tanggal')" type="date" required />
        <flux:select wire:model="status" :label="__('Status')">@foreach ($this->statusOptions as $statusOption)<flux:select.option value="{{ $statusOption }}">{{ $statusOption }}</flux:select.option>@endforeach</flux:select>

        @if ($existingFotos)
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach ($existingFotos as $index => $foto)
                    <div class="relative">
                        <img src="{{ Storage::disk('public')->url($foto) }}" alt="Foto dokumentasi" class="aspect-video rounded-lg border border-zinc-200 object-cover dark:border-zinc-700">
                        <button type="button" wire:click="removeExistingFoto({{ $index }})" class="absolute right-2 top-2 rounded bg-white px-2 py-1 text-xs font-medium text-red-600 shadow dark:bg-zinc-900">{{ __('Hapus') }}</button>
                    </div>
                @endforeach
            </div>
        @endif

        <flux:input wire:model="fotos" :label="__('Tambah Foto Dokumentasi')" type="file" accept="image/*" multiple />

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
