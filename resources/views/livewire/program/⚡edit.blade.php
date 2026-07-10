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

<section class="mx-auto w-full max-w-6xl overflow-hidden rounded-lg bg-zinc-200 text-zinc-950 shadow-sm dark:bg-zinc-900 dark:text-white">
    <div class="flex items-center justify-between gap-4 bg-zinc-300 px-4 py-3 dark:bg-zinc-800">
        <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-zinc-400 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                <flux:icon name="calendar-days" class="size-5" />
            </div>
            <div class="min-w-0">
                <p class="truncate text-base font-bold">{{ __('Edit Program') }}</p>
                <span class="inline-flex rounded bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-zinc-700 dark:bg-zinc-700 dark:text-zinc-100">
                    {{ $programBanjar->status }}
                </span>
            </div>
        </div>

        <a href="{{ route('program.index') }}" wire:navigate class="h-6 w-16 rounded-md bg-zinc-100 text-center text-xs font-semibold leading-6 text-zinc-600 transition hover:bg-white dark:bg-zinc-700 dark:text-zinc-200">
            {{ __('Back') }}
        </a>
    </div>

    <form wire:submit="save">
        <div class="space-y-5 px-4 py-6">
            <div class="max-w-3xl">
                <label for="judul" class="text-sm font-bold">{{ __('Judul Program') }}</label>
                <input id="judul" wire:model="judul" type="text" required class="mt-2 w-full rounded-lg border border-zinc-100 bg-zinc-100 px-4 py-3 text-sm font-semibold text-zinc-900 outline-none transition focus:border-zinc-400 focus:ring-2 focus:ring-zinc-400/30 dark:border-zinc-800 dark:bg-zinc-800 dark:text-white">
                @error('judul') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="max-w-3xl">
                <label for="deskripsi" class="text-sm font-bold">{{ __('Deskripsi') }}</label>
                <textarea id="deskripsi" wire:model="deskripsi" rows="7" required class="mt-2 w-full resize-none rounded-lg border border-zinc-100 bg-zinc-100 px-4 py-3 text-sm text-zinc-900 outline-none transition focus:border-zinc-400 focus:ring-2 focus:ring-zinc-400/30 dark:border-zinc-800 dark:bg-zinc-800 dark:text-white"></textarea>
                @error('deskripsi') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid max-w-3xl gap-4 md:grid-cols-2">
                <div>
                    <label for="tanggal_mulai" class="text-sm font-bold">{{ __('Tanggal Mulai') }}</label>
                    <input id="tanggal_mulai" wire:model="tanggal_mulai" type="date" required class="mt-2 w-full rounded-lg border border-zinc-100 bg-zinc-100 px-4 py-3 text-sm font-semibold text-zinc-900 outline-none transition focus:border-zinc-400 focus:ring-2 focus:ring-zinc-400/30 dark:border-zinc-800 dark:bg-zinc-800 dark:text-white">
                    @error('tanggal_mulai') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="tanggal_selesai" class="text-sm font-bold">{{ __('Tanggal Selesai') }}</label>
                    <input id="tanggal_selesai" wire:model="tanggal_selesai" type="date" required class="mt-2 w-full rounded-lg border border-zinc-100 bg-zinc-100 px-4 py-3 text-sm font-semibold text-zinc-900 outline-none transition focus:border-zinc-400 focus:ring-2 focus:ring-zinc-400/30 dark:border-zinc-800 dark:bg-zinc-800 dark:text-white">
                    @error('tanggal_selesai') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="max-w-3xl">
                <p class="mb-2 text-sm font-bold">{{ __('Status Program') }}</p>
                <div class="grid gap-3 md:grid-cols-3">
                    @foreach (ProgramBanjar::STATUSES as $statusOption)
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg bg-zinc-100 px-4 py-3 text-sm font-bold text-zinc-800 ring-1 ring-transparent transition has-[:checked]:bg-white has-[:checked]:ring-zinc-500 dark:bg-zinc-800 dark:text-zinc-100 dark:has-[:checked]:bg-zinc-700">
                            <input type="radio" wire:model="status" value="{{ $statusOption }}" class="size-3 border-zinc-400 text-zinc-700 focus:ring-zinc-500">
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
                            class="aspect-square w-full rounded-lg bg-zinc-300 object-cover dark:bg-zinc-800"
                        >
                    </div>
                @elseif ($programBanjar->gambar)
                    <div class="grid max-w-3xl gap-4 sm:grid-cols-3">
                        <a href="{{ $this->gambarUrl($programBanjar->gambar) }}" target="_blank" class="block overflow-hidden rounded-lg bg-zinc-300 dark:bg-zinc-800">
                            <img
                                src="{{ $this->gambarUrl($programBanjar->gambar) }}"
                                alt="{{ $programBanjar->judul }}"
                                class="aspect-square w-full object-cover"
                            >
                        </a>
                    </div>
                @else
                    <div class="grid max-w-3xl gap-4 sm:grid-cols-3">
                        <div class="flex aspect-square items-center justify-center rounded-lg bg-zinc-300 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-500">
                            <flux:icon name="photo" class="size-16" />
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="border-t-8 border-zinc-100 bg-zinc-200 dark:border-zinc-950 dark:bg-zinc-900">
            <div class="space-y-5 px-4 py-5">
                <div>
                    <label for="gambar" class="text-sm font-bold">{{ __('Unggah Foto Pengganti') }} <span class="font-medium text-zinc-600 dark:text-zinc-400">{{ __('(Opsional, 1 foto)') }}</span></label>
                    <input id="gambar" wire:model="gambar" type="file" accept="image/*" class="mt-2 block w-full rounded-lg bg-zinc-100 text-sm text-zinc-700 file:mr-4 file:rounded-md file:border-0 file:bg-zinc-300 file:px-4 file:py-2 file:text-sm file:font-bold file:text-zinc-700 hover:file:bg-zinc-400 dark:bg-zinc-800 dark:text-zinc-200 dark:file:bg-zinc-700 dark:file:text-zinc-100">
                    <p class="mt-1 text-xs font-semibold text-zinc-600 dark:text-zinc-400">{{ __('Format PNG/JPG Maks. 2MB') }}</p>
                    @error('gambar') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('program.index') }}" wire:navigate class="rounded-lg bg-zinc-100 px-5 py-3 text-sm font-bold text-zinc-700 transition hover:bg-white dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700">
                        {{ __('Batal') }}
                    </a>
                    <button type="submit" class="rounded-lg bg-zinc-300 px-5 py-3 text-sm font-bold text-zinc-950 transition hover:bg-zinc-400 dark:bg-zinc-700 dark:text-white dark:hover:bg-zinc-600">
                        {{ __('Simpan Program') }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</section>
