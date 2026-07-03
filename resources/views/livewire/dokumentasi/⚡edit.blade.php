<?php

use App\Models\DokumentasiKegiatan;
use App\Models\ProgramBanjar;
use Illuminate\Database\Eloquent\Builder;
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

    public string $program_banjar_id = '';

    public array $fotos = [];

    public array $existingFotos = [];

    public function mount(DokumentasiKegiatan $dokumentasiKegiatan): void
    {
        Gate::authorize('update', $dokumentasiKegiatan);

        $this->dokumentasiKegiatan = $dokumentasiKegiatan;
        $this->program_banjar_id = (string) $dokumentasiKegiatan->program_banjar_id;
        $this->existingFotos = $dokumentasiKegiatan->fotos ?? ($dokumentasiKegiatan->foto ? [$dokumentasiKegiatan->foto] : []);
    }

    #[Computed]
    public function informasiKegiatan()
    {
        return ProgramBanjar::query()
            ->where(fn (Builder $query) => $query
                ->where('status', ProgramBanjar::STATUS_SELESAI)
                ->orWhere('id', $this->dokumentasiKegiatan->program_banjar_id))
            ->orderByDesc('tanggal')
            ->get();
    }

    #[Computed]
    public function selectedInformasi(): ?ProgramBanjar
    {
        if ($this->program_banjar_id === '') {
            return null;
        }

        return ProgramBanjar::query()
            ->where(fn (Builder $query) => $query
                ->where('status', ProgramBanjar::STATUS_SELESAI)
                ->orWhere('id', $this->dokumentasiKegiatan->program_banjar_id))
            ->find($this->program_banjar_id);
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
            'program_banjar_id' => [
                'required',
                Rule::exists('program_banjar', 'id')->where(fn ($query) => $query->where('status', ProgramBanjar::STATUS_SELESAI)),
            ],
            'fotos' => ['nullable', 'array'],
            'fotos.*' => ['image', 'max:2048'],
        ]);

        $informasi = ProgramBanjar::findOrFail($validated['program_banjar_id']);

        $newFotos = collect($this->fotos)
            ->map(fn ($foto) => $foto->store('dokumentasi', 'public'))
            ->values()
            ->all();

        $storedFotos = array_values([...$this->existingFotos, ...$newFotos]);

        $this->dokumentasiKegiatan->update([
            'program_banjar_id' => $informasi->id,
            'judul' => $informasi->judul,
            'deskripsi' => $informasi->deskripsi,
            'tanggal' => $informasi->tanggal,
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
            <span>{{ __('Edit') }}</span>
        </div>
        <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Edit Dokumentasi') }}</h1>
    </div>

    <form wire:submit="save" class="space-y-5 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:select wire:model.live="program_banjar_id" :label="__('Informasi Kegiatan Selesai')">
            <flux:select.option value="">{{ __('Pilih informasi kegiatan') }}</flux:select.option>
            @foreach ($this->informasiKegiatan as $informasi)
                <flux:select.option value="{{ $informasi->id }}">{{ $informasi->judul }} - {{ $informasi->tanggal->format('d M Y') }}</flux:select.option>
            @endforeach
        </flux:select>

        @if ($this->selectedInformasi)
            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <p class="font-semibold text-zinc-950 dark:text-white">{{ $this->selectedInformasi->judul }}</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $this->selectedInformasi->tanggal->format('d M Y') }}</p>
                <p class="mt-3 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-200">{{ $this->selectedInformasi->deskripsi }}</p>
            </div>
        @endif

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

        <flux:input wire:model="fotos" :label="__('Tambah Foto Bukti')" type="file" accept="image/*" multiple />

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
