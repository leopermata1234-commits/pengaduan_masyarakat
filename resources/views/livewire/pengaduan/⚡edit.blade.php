<?php

use App\Models\Pengaduan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Edit Pengaduan')] class extends Component
{
    use WithFileUploads;

    public Pengaduan $pengaduan;

    public string $judul = '';

    public string $isi_pengaduan = '';

    public string $status = '';

    public string $visibilitas = Pengaduan::VISIBILITAS_PUBLIK;

    public array $fotos = [];

    public array $existingFotos = [];

    public function mount(Pengaduan $pengaduan): void
    {
        Gate::authorize('update', $pengaduan);

        $this->pengaduan = $pengaduan;
        $this->judul = $pengaduan->judul;
        $this->isi_pengaduan = $pengaduan->isi_pengaduan;
        $this->status = $pengaduan->status;
        $this->visibilitas = $pengaduan->visibilitas;
        $this->existingFotos = $pengaduan->fotoPaths();
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
        Gate::authorize('update', $this->pengaduan);

        $validated = $this->validate([
            'judul' => ['required', 'string', 'max:255'],
            'isi_pengaduan' => ['required', 'string'],
            'status' => ['required', Rule::in(Pengaduan::STATUSES)],
            'visibilitas' => ['required', Rule::in(Pengaduan::VISIBILITAS)],
            'fotos' => ['nullable', 'array', 'max:'.max(0, 5 - count($this->existingFotos))],
            'fotos.*' => ['image', 'max:2048'],
        ]);

        $newFotos = collect($this->fotos)
            ->map(fn ($foto) => $foto->store('pengaduan', 'public'))
            ->values()
            ->all();

        $storedFotos = array_values([...$this->existingFotos, ...$newFotos]);

        $data = [
            'judul' => $validated['judul'],
            'isi_pengaduan' => $validated['isi_pengaduan'],
            'status' => $validated['status'],
            'visibilitas' => $validated['visibilitas'],
            'foto' => $storedFotos[0] ?? null,
            'fotos' => $storedFotos,
        ];

        $this->pengaduan->update($data);

        $this->redirectRoute('pengaduan.index', navigate: true);
    }
};
?>

<section class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-2 text-sm text-zinc-500"><a href="{{ route('pengaduan.index') }}" wire:navigate>{{ __('Pengaduan') }}</a><span>/</span><span>{{ __('Edit') }}</span></div>
        <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Edit Pengaduan') }}</h1>
    </div>
    <form wire:submit="save" class="space-y-5 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:input wire:model="judul" :label="__('Judul')" required />
        <flux:textarea wire:model="isi_pengaduan" :label="__('Isi Pengaduan')" rows="6" required />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (Pengaduan::STATUSES as $statusOption)
                <flux:select.option value="{{ $statusOption }}">{{ $statusOption }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model="visibilitas" :label="__('Sifat')">
            @foreach (Pengaduan::VISIBILITAS as $visibilitasOption)
                <flux:select.option value="{{ $visibilitasOption }}">{{ $visibilitasOption }}</flux:select.option>
            @endforeach
        </flux:select>
        @if ($existingFotos)
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach ($existingFotos as $index => $foto)
                    <div class="relative">
                        <img src="{{ Storage::disk('public')->url($foto) }}" alt="Foto pengaduan" class="aspect-video rounded-lg border border-zinc-200 object-cover dark:border-zinc-700">
                        <button type="button" wire:click="removeExistingFoto({{ $index }})" class="absolute right-2 top-2 rounded bg-white px-2 py-1 text-xs font-medium text-red-600 shadow dark:bg-zinc-900">{{ __('Hapus') }}</button>
                    </div>
                @endforeach
            </div>
        @endif

        @if (count($existingFotos) < 5)
            <div class="space-y-2">
                <label for="fotos" class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Tambah Foto') }}</label>
                <input
                    id="fotos"
                    wire:model="fotos"
                    type="file"
                    accept="image/*"
                    multiple
                    class="block w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 file:mr-4 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-zinc-700 hover:file:bg-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-200"
                />
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Maksimal total 5 foto, masing-masing 2 MB.') }}</p>
                @error('fotos') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                @error('fotos.*') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
        @endif

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
