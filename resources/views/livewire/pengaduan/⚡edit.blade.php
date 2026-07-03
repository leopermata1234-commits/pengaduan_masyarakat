<?php

use App\Models\Pengaduan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('Edit Pengaduan')] class extends Component
{
    use WithFileUploads;

    public Pengaduan $pengaduan;

    public string $judul = '';

    public string $isi_pengaduan = '';

    public string $status = '';

    public ?TemporaryUploadedFile $foto = null;

    public function mount(Pengaduan $pengaduan): void
    {
        Gate::authorize('update', $pengaduan);

        $this->pengaduan = $pengaduan;
        $this->judul = $pengaduan->judul;
        $this->isi_pengaduan = $pengaduan->isi_pengaduan;
        $this->status = $pengaduan->status;
    }

    public function save(): void
    {
        Gate::authorize('update', $this->pengaduan);

        $validated = $this->validate([
            'judul' => ['required', 'string', 'max:255'],
            'isi_pengaduan' => ['required', 'string'],
            'status' => ['required', Rule::in(Pengaduan::STATUSES)],
            'foto' => ['nullable', 'image', 'max:2048'],
        ]);

        $data = [
            'judul' => $validated['judul'],
            'isi_pengaduan' => $validated['isi_pengaduan'],
            'status' => $validated['status'],
        ];

        if ($this->foto) {
            if ($this->pengaduan->foto) {
                Storage::disk('public')->delete($this->pengaduan->foto);
            }
            $data['foto'] = $this->foto->store('pengaduan', 'public');
        }

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
        <flux:input wire:model="foto" :label="__('Ganti Foto')" type="file" accept="image/*" />
        <div class="flex justify-end gap-2">
            <flux:button variant="filled" :href="route('pengaduan.index')" wire:navigate>{{ __('Batal') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button>
        </div>
    </form>
</section>
