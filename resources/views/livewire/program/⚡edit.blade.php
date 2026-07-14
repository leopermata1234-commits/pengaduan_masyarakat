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

<section class="mx-auto w-full max-w-6xl overflow-hidden rounded-2xl border border-[#d8c8b5] bg-[#f8f1e7] text-[#352b22] shadow-[0_10px_28px_rgba(62,44,29,.14)]">
    <div class="flex items-center justify-between gap-4 bg-[linear-gradient(135deg,#776352,#9b7b5a_55%,#6d5543)] px-5 py-4 text-white">
        <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#f3e3ca] text-[#765437] shadow-inner">
                <flux:icon name="calendar-days" class="size-5" />
            </div>
            <div class="min-w-0">
                <p class="truncate text-base font-bold">{{ __('Edit Program') }}</p>
                <span class="inline-flex rounded-md bg-white/15 px-2 py-0.5 text-xs font-semibold text-[#fff4e5] ring-1 ring-white/20">
                    {{ $programBanjar->status }}
                </span>
            </div>
        </div>

        <a href="{{ route('program.index') }}" wire:navigate class="rounded-lg bg-[#f3e3ca] px-4 py-2 text-xs font-bold text-[#59412e] transition hover:bg-white">
            {{ __('Back') }}
        </a>
    </div>

    <form wire:submit="save">
        <div class="space-y-5 px-5 py-7 sm:px-7">
            <div class="max-w-3xl">
                <label for="judul" class="text-sm font-bold">{{ __('Judul Program') }}</label>
                <input id="judul" wire:model="judul" type="text" required class="mt-2 w-full rounded-xl border border-[#ddcfbd] bg-[#fffdf9] px-4 py-3 text-sm font-semibold text-[#352b22] outline-none transition focus:border-[#17827a] focus:ring-2 focus:ring-[#17827a]/20">
                @error('judul') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="max-w-3xl">
                <label for="deskripsi" class="text-sm font-bold">{{ __('Deskripsi') }}</label>
                <textarea id="deskripsi" wire:model="deskripsi" rows="7" required class="mt-2 w-full resize-none rounded-xl border border-[#ddcfbd] bg-[#fffdf9] px-4 py-3 text-sm text-[#352b22] outline-none transition focus:border-[#17827a] focus:ring-2 focus:ring-[#17827a]/20"></textarea>
                @error('deskripsi') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid max-w-3xl gap-4 md:grid-cols-2">
                <div>
                    <label for="tanggal_mulai" class="text-sm font-bold">{{ __('Tanggal Mulai') }}</label>
                    <input id="tanggal_mulai" wire:model="tanggal_mulai" type="date" required class="mt-2 w-full rounded-xl border border-[#ddcfbd] bg-[#fffdf9] px-4 py-3 text-sm font-semibold text-[#352b22] outline-none transition focus:border-[#17827a] focus:ring-2 focus:ring-[#17827a]/20">
                    @error('tanggal_mulai') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="tanggal_selesai" class="text-sm font-bold">{{ __('Tanggal Selesai') }}</label>
                    <input id="tanggal_selesai" wire:model="tanggal_selesai" type="date" required class="mt-2 w-full rounded-xl border border-[#ddcfbd] bg-[#fffdf9] px-4 py-3 text-sm font-semibold text-[#352b22] outline-none transition focus:border-[#17827a] focus:ring-2 focus:ring-[#17827a]/20">
                    @error('tanggal_selesai') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="max-w-3xl">
                <p class="mb-2 text-sm font-bold">{{ __('Status Program') }}</p>
                <div class="grid gap-3 md:grid-cols-3">
                    @foreach (ProgramBanjar::STATUSES as $statusOption)
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl bg-[#efe2d0] px-4 py-3 text-sm font-bold text-[#5b4938] ring-1 ring-[#ddcfbd] transition has-[:checked]:bg-[#dceee9] has-[:checked]:text-[#0f625d] has-[:checked]:ring-[#17827a]">
                            <input type="radio" wire:model="status" value="{{ $statusOption }}" class="size-3 border-[#a88e72] text-[#13746e] focus:ring-[#17827a]">
                            <span>{{ $statusOption }}</span>
                        </label>
                    @endforeach
                </div>
                @error('status') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-3">
                <p class="text-sm font-bold">{{ __('Foto Program') }}</p>
                @if ($gambar)
                    <div class="grid max-w-3xl gap-4 sm:grid-cols-3">
                        <img
                            src="{{ $gambar->temporaryUrl() }}"
                            alt="{{ __('Preview foto baru') }}"
                            class="aspect-square w-full rounded-xl bg-[#eadcc9] object-cover ring-1 ring-[#d8c8b5]"
                        >
                    </div>
                @elseif ($programBanjar->gambar)
                    <div class="grid max-w-3xl gap-4 sm:grid-cols-3">
                        <a href="{{ $this->gambarUrl($programBanjar->gambar) }}" target="_blank" class="block overflow-hidden rounded-xl bg-[#eadcc9] ring-1 ring-[#d8c8b5]">
                            <img
                                src="{{ $this->gambarUrl($programBanjar->gambar) }}"
                                alt="{{ $programBanjar->judul }}"
                                class="aspect-square w-full object-cover"
                            >
                        </a>
                    </div>
                @else
                    <div class="grid max-w-3xl gap-4 sm:grid-cols-3">
                        <div class="flex aspect-square items-center justify-center rounded-xl bg-[#eadcc9] text-[#9a8066] ring-1 ring-[#d8c8b5]">
                            <flux:icon name="photo" class="size-16" />
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="border-t border-[#d8c8b5] bg-[#f1e5d5]">
            <div class="space-y-5 px-5 py-5 sm:px-7">
                <div>
                    <label for="gambar" class="text-sm font-bold">{{ __('Unggah Foto Pengganti') }} <span class="font-medium text-[#756858]">{{ __('(Opsional, 1 foto)') }}</span></label>
                    <input id="gambar" wire:model="gambar" type="file" accept="image/*" class="mt-2 block w-full rounded-xl border border-[#ddcfbd] bg-[#fffdf9] text-sm text-[#5b4938] file:mr-4 file:border-0 file:bg-[#d9c19f] file:px-4 file:py-3 file:text-sm file:font-bold file:text-[#4b3827] hover:file:bg-[#cdae84]">
                    <p class="mt-1 text-xs font-semibold text-[#756858]">{{ __('Format PNG/JPG Maks. 2MB') }}</p>
                    @error('gambar') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('program.index') }}" wire:navigate class="rounded-xl border border-[#cdbca8] bg-[#fffaf2] px-5 py-3 text-sm font-bold text-[#65503d] transition hover:bg-white">
                        {{ __('Batal') }}
                    </a>
                    <button type="submit" class="rounded-xl bg-[#13746e] px-5 py-3 text-sm font-bold text-white shadow-md transition hover:bg-[#0f625d]">
                        {{ __('Simpan Program') }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</section>
