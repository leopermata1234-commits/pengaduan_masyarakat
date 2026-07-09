<?php

use App\Models\ProgramBanjar;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('Edit Program')] class extends Component
{
    use WithFileUploads;

    public ProgramBanjar $programBanjar;
    public string $judul = '';
    public string $deskripsi = '';
    public string $tanggal_mulai = '';
    public string $tanggal_selesai = '';
    public string $status = '';
    public ?TemporaryUploadedFile $gambar = null;

    public function gambarUrl(string $gambar): string
    {
        return '/storage/'.Str::of($gambar)->ltrim('/');
    }

    public function mount(ProgramBanjar $programBanjar): void
    {
        Gate::authorize('update', $programBanjar);

        $this->programBanjar = $programBanjar;
        $this->judul = $programBanjar->judul;
        $this->deskripsi = $programBanjar->deskripsi;
        $this->tanggal_mulai = ($programBanjar->tanggal_mulai ?? $programBanjar->tanggal)->format('Y-m-d');
        $this->tanggal_selesai = ($programBanjar->tanggal_selesai ?? $programBanjar->tanggal)->format('Y-m-d');
        $this->status = $programBanjar->status;
    }

    public function save(): void
    {
        Gate::authorize('update', $this->programBanjar);

        $validated = $this->validate([
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'status' => ['required', Rule::in(ProgramBanjar::STATUSES)],
            'gambar' => ['nullable', 'image', 'max:2048'],
        ]);

        $data = collect($validated)->except('gambar')->all();
        $data['tanggal'] = $validated['tanggal_mulai'];

        if ($this->gambar) {
            if ($this->programBanjar->gambar) {
                Storage::disk('public')->delete($this->programBanjar->gambar);
            }
            $data['gambar'] = $this->gambar->store('program', 'public');
        }

        $this->programBanjar->update($data);
        $this->redirectRoute('program.index', navigate: true);
    }
};
?>

<section class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <div class="flex flex-col gap-2"><div class="flex items-center gap-2 text-sm text-zinc-500"><a href="{{ route('program.index') }}" wire:navigate>{{ __('Program') }}</a><span>/</span><span>{{ __('Edit') }}</span></div><h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Edit Program') }}</h1></div>
    <form wire:submit="save" class="space-y-5 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:input wire:model="judul" :label="__('Judul')" required />
        <flux:textarea wire:model="deskripsi" :label="__('Deskripsi')" rows="6" required />
        <div class="grid gap-5 md:grid-cols-2">
            <flux:input wire:model="tanggal_mulai" :label="__('Tanggal Mulai')" type="date" required />
            <flux:input wire:model="tanggal_selesai" :label="__('Tanggal Selesai')" type="date" required />
        </div>
        <flux:select wire:model="status" :label="__('Status')">@foreach (ProgramBanjar::STATUSES as $statusOption)<flux:select.option value="{{ $statusOption }}">{{ $statusOption }}</flux:select.option>@endforeach</flux:select>
        <flux:input wire:model="gambar" :label="__('Ganti Foto Program')" type="file" accept="image/*" />

        @if ($gambar)
            <div class="space-y-3">
                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Preview Foto Baru') }}</p>
                <img
                    src="{{ $gambar->temporaryUrl() }}"
                    alt="{{ __('Preview foto baru') }}"
                    class="max-h-80 w-full rounded-lg border border-zinc-200 object-contain dark:border-zinc-700"
                >
            </div>
        @elseif ($programBanjar->gambar)
            <div class="space-y-3">
                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Foto Saat Ini') }}</p>
                <a href="{{ $this->gambarUrl($programBanjar->gambar) }}" target="_blank" class="block">
                    <img
                        src="{{ $this->gambarUrl($programBanjar->gambar) }}"
                        alt="{{ $programBanjar->judul }}"
                        class="max-h-80 w-full rounded-lg border border-zinc-200 object-contain dark:border-zinc-700"
                    >
                </a>
            </div>
        @endif

        <div class="flex justify-end gap-2"><flux:button variant="filled" :href="route('program.index')" wire:navigate>{{ __('Batal') }}</flux:button><flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button></div>
    </form>
</section>
