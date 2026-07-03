<?php

use App\Models\Pengaduan;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('Buat Pengaduan')] class extends Component
{
    use WithFileUploads;

    public string $judul = '';

    public string $isi_pengaduan = '';

    public ?TemporaryUploadedFile $foto = null;

    public function save(): void
    {
        Gate::authorize('create', Pengaduan::class);

        $validated = $this->validate([
            'judul' => ['required', 'string', 'max:255'],
            'isi_pengaduan' => ['required', 'string'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ]);

        Pengaduan::create([
            'user_id' => auth()->id(),
            'judul' => $validated['judul'],
            'isi_pengaduan' => $validated['isi_pengaduan'],
            'foto' => $this->foto?->store('pengaduan', 'public'),
            'status' => Pengaduan::STATUS_PENDING,
        ]);

        $this->redirectRoute('pengaduan.index', navigate: true);
    }
};
?>

<section class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-2 text-sm text-zinc-500"><a href="{{ route('pengaduan.index') }}" wire:navigate>{{ __('Pengaduan') }}</a><span>/</span><span>{{ __('Buat') }}</span></div>
        <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Buat Pengaduan') }}</h1>
    </div>
    <form wire:submit="save" class="space-y-5 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:input wire:model="judul" :label="__('Judul')" required />
        <flux:textarea wire:model="isi_pengaduan" :label="__('Isi Pengaduan')" rows="6" required />
        <flux:input wire:model="foto" :label="__('Foto')" type="file" accept="image/*" />
        <div class="flex justify-end gap-2">
            <flux:button variant="filled" :href="route('pengaduan.index')" wire:navigate>{{ __('Batal') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button>
        </div>
    </form>
</section>
