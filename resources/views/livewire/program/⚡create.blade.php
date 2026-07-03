<?php

use App\Models\ProgramBanjar;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('Tambah Informasi Kegiatan')] class extends Component
{
    use WithFileUploads;

    public string $judul = '';
    public string $deskripsi = '';
    public string $tanggal = '';
    public string $status = ProgramBanjar::STATUS_DRAFT;
    public ?TemporaryUploadedFile $gambar = null;

    public function save(): void
    {
        Gate::authorize('create', ProgramBanjar::class);

        $validated = $this->validate([
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string'],
            'tanggal' => ['required', 'date'],
            'status' => ['required', Rule::in(ProgramBanjar::STATUSES)],
            'gambar' => ['nullable', 'image', 'max:2048'],
        ]);

        ProgramBanjar::create([
            'user_id' => auth()->id(),
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'],
            'tanggal' => $validated['tanggal'],
            'status' => $validated['status'],
            'gambar' => $this->gambar?->store('program', 'public'),
        ]);

        $this->redirectRoute('program.index', navigate: true);
    }
};
?>

<section class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <div class="flex flex-col gap-2"><div class="flex items-center gap-2 text-sm text-zinc-500"><a href="{{ route('program.index') }}" wire:navigate>{{ __('Informasi Kegiatan') }}</a><span>/</span><span>{{ __('Tambah') }}</span></div><h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Tambah Informasi Kegiatan') }}</h1></div>
    <form wire:submit="save" class="space-y-5 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:input wire:model="judul" :label="__('Judul')" required />
        <flux:textarea wire:model="deskripsi" :label="__('Deskripsi')" rows="6" required />
        <flux:input wire:model="tanggal" :label="__('Tanggal')" type="date" required />
        <flux:select wire:model="status" :label="__('Status')">@foreach (ProgramBanjar::STATUSES as $statusOption)<flux:select.option value="{{ $statusOption }}">{{ $statusOption }}</flux:select.option>@endforeach</flux:select>
        <flux:input wire:model="gambar" :label="__('Gambar')" type="file" accept="image/*" />
        <div class="flex justify-end gap-2"><flux:button variant="filled" :href="route('program.index')" wire:navigate>{{ __('Batal') }}</flux:button><flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button></div>
    </form>
</section>
