<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Data Masyarakat')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function masyarakat()
    {
        return User::query()
            ->role('Masyarakat')
            ->when($this->search !== '', fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%")
                    ->orWhere('nik', 'like', "%{$this->search}%")
                    ->orWhere('kk', 'like', "%{$this->search}%")))
            ->latest()
            ->paginate(10);
    }
};
?>

<section class="mx-auto flex w-full max-w-[1680px] flex-col gap-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                <span>{{ __('Layanan') }}</span>
                <span>/</span>
                <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ __('Data Masyarakat') }}</span>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Data Masyarakat') }}</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Daftar masyarakat banjar yang sudah registrasi di sistem.') }}</p>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass" :placeholder="__('Cari nama, email, telepon, NIK, atau KK')" />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Nama') }}</th>
                        <th class="px-4 py-3">{{ __('NIK') }}</th>
                        <th class="px-4 py-3">{{ __('KK') }}</th>
                        <th class="px-4 py-3">{{ __('Tanggal Lahir') }}</th>
                        <th class="px-4 py-3">{{ __('Jenis Kelamin') }}</th>
                        <th class="px-4 py-3">{{ __('No. HP') }}</th>
                        <th class="px-4 py-3">{{ __('Email') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->masyarakat as $user)
                        <tr>
                            <td class="px-4 py-3 font-medium text-zinc-950 dark:text-white">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $user->nik ?: '-' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $user->kk ?: '-' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $user->tanggal_lahir?->format('d M Y') ?: '-' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $user->jenis_kelamin ?: '-' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $user->phone ?: '-' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $user->email }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">{{ __('Data masyarakat tidak ditemukan.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
            {{ $this->masyarakat->links() }}
        </div>
    </div>
</section>
