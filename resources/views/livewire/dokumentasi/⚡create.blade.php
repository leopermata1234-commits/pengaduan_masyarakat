<?php

use App\Models\DokumentasiKegiatan;
use App\Models\ProgramBanjar;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Tambah Dokumentasi')] class extends Component
{
    use WithFileUploads;

    public string $program_banjar_id = '';

    public array $fotos = [];

    #[Computed]
    public function informasiKegiatan()
    {
        return ProgramBanjar::query()
            ->where('status', ProgramBanjar::STATUS_SELESAI)
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
            ->where('status', ProgramBanjar::STATUS_PUBLISHED)
            ->find($this->program_banjar_id);
    }

    public function save(): void
    {
        Gate::authorize('create', DokumentasiKegiatan::class);

        $validated = $this->validate([
            'program_banjar_id' => [
                'required',
                Rule::exists('program_banjar', 'id')->where(fn ($query) => $query->where('status', ProgramBanjar::STATUS_SELESAI)),
            ],
            'fotos' => ['required', 'array', 'min:1'],
            'fotos.*' => ['image', 'max:2048'],
        ]);

        $informasi = ProgramBanjar::findOrFail($validated['program_banjar_id']);

        $storedFotos = collect($this->fotos)
            ->map(fn ($foto) => $foto->store('dokumentasi', 'public'))
            ->values()
            ->all();

        DokumentasiKegiatan::create([
            'user_id' => auth()->id(),
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
            <span>{{ __('Tambah') }}</span>
        </div>
        <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Tambah Dokumentasi') }}</h1>
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

        <flux:input wire:model="fotos" :label="__('Foto Bukti Kegiatan')" type="file" accept="image/*" multiple />

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
