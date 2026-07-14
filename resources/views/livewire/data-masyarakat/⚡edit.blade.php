<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Data Warga')] class extends Component
{
    public User $user;

    public string $name = '';

    public string $nik = '';

    public string $kk = '';

    public string $tanggal_lahir = '';

    public string $jenis_kelamin = '';

    public string $phone = '';

    public function mount(User $user): void
    {
        Gate::authorize('edit.data.warga');
        abort_unless($user->hasRole('Masyarakat'), 404);

        $this->user = $user;
        $this->name = $user->name;
        $this->nik = $user->nik ?? '';
        $this->kk = $user->kk ?? '';
        $this->tanggal_lahir = $user->tanggal_lahir?->format('Y-m-d') ?? '';
        $this->jenis_kelamin = $user->jenis_kelamin ?? '';
        $this->phone = $user->phone ?? '';
    }

    public function save(): void
    {
        Gate::authorize('edit.data.warga');
        abort_unless($this->user->hasRole('Masyarakat'), 404);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'digits:16', Rule::unique('users', 'nik')->ignore($this->user)],
            'kk' => ['required', 'digits:16'],
            'tanggal_lahir' => ['required', 'date', 'before_or_equal:today'],
            'jenis_kelamin' => ['required', Rule::in(['Laki-laki', 'Perempuan'])],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $this->user->update($validated);

        session()->flash('success', __('Data warga berhasil diperbarui.'));
        $this->redirectRoute('data-masyarakat.index', navigate: true);
    }
};
?>

<section class="mx-auto flex w-full max-w-5xl flex-col gap-6">
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-2 text-sm text-[#756858]">
            <a href="{{ route('data-masyarakat.index') }}" wire:navigate class="transition hover:text-[#13746e]">{{ __('Data Masyarakat') }}</a>
            <span>/</span>
            <span class="font-semibold text-[#352b22]">{{ __('Edit Warga') }}</span>
        </div>
        <h1 class="font-serif text-3xl font-bold text-[#2f241b]">{{ __('Edit Data Warga') }}</h1>
        <p class="text-sm text-[#625b53]">{{ __('Perbarui informasi kependudukan dan nomor HP warga.') }}</p>
    </div>

    <form wire:submit="save" class="overflow-hidden rounded-2xl border border-[#d8c8b5] bg-[#f8f1e7] shadow-[0_10px_28px_rgba(62,44,29,.12)]">
        <div class="bg-[linear-gradient(135deg,#776352,#9b7b5a_55%,#6d5543)] px-6 py-4 text-white">
            <h2 class="font-bold">{{ $user->name }}</h2>
            <p class="mt-1 text-xs text-white/80">{{ __('Pastikan perubahan sesuai dengan identitas warga.') }}</p>
        </div>

        <div class="grid gap-5 p-6 md:grid-cols-2">
            <div class="md:col-span-2">
                <flux:input wire:model="name" :label="__('Nama Lengkap')" required />
            </div>
            <flux:input wire:model="nik" :label="__('NIK (16 digit)')" inputmode="numeric" maxlength="16" required />
            <flux:input wire:model="kk" :label="__('Nomor KK (16 digit)')" inputmode="numeric" maxlength="16" required />
            <flux:input wire:model="tanggal_lahir" :label="__('Tanggal Lahir')" type="date" required />
            <flux:select wire:model="jenis_kelamin" :label="__('Jenis Kelamin')" required>
                <flux:select.option value="">{{ __('Pilih jenis kelamin') }}</flux:select.option>
                <flux:select.option value="Laki-laki">{{ __('Laki-laki') }}</flux:select.option>
                <flux:select.option value="Perempuan">{{ __('Perempuan') }}</flux:select.option>
            </flux:select>
            <div class="md:col-span-2">
                <flux:input wire:model="phone" :label="__('Nomor HP')" type="tel" required />
            </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-[#d8c8b5] bg-[#f1e5d5] px-6 py-5">
            <a href="{{ route('data-masyarakat.index') }}" wire:navigate class="rounded-xl border border-[#cdbca8] bg-[#fffaf2] px-5 py-3 text-sm font-bold text-[#65503d] transition hover:bg-white">
                {{ __('Batal') }}
            </a>
            <button type="submit" class="rounded-xl bg-[#13746e] px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-[#0f625d]">
                {{ __('Simpan Perubahan') }}
            </button>
        </div>
    </form>
</section>
