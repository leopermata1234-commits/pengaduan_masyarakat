<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

new #[Title('Manajemen Akun')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $role = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function roles()
    {
        return Role::query()->orderBy('name')->get();
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->with('roles')
            ->whereNotNull('email')
            ->when($this->search !== '', fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%")))
            ->when($this->role !== '', fn (Builder $query) => $query->role($this->role))
            ->latest()
            ->paginate(10);
    }

    public function delete(int $userId): void
    {
        abort_unless(auth()->user()->can('users.delete'), 403);
        abort_if(auth()->id() === $userId, 403);

        User::findOrFail($userId)->delete();

        $this->resetPage();
    }
};
?>

<section class="mx-auto flex w-full max-w-7xl flex-col gap-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                <span>{{ __('Administrasi') }}</span>
                <span>/</span>
                <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ __('Manajemen Akun') }}</span>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Manajemen Akun') }}</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Kelola akun yang memiliki akses login, termasuk Admin, Prajuru, dan Masyarakat beserta role aksesnya.') }}</p>
            </div>
        </div>

        @can('users.create')
            <flux:button icon="plus" variant="primary" :href="route('users.create')" wire:navigate>{{ __('Tambah Akun') }}</flux:button>
        @endcan
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-3 border-b border-zinc-200 p-4 dark:border-zinc-700 md:grid-cols-[1fr_220px]">
            <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass" :placeholder="__('Cari nama, email, atau telepon')" />
            <flux:select wire:model.live="role">
                <flux:select.option value="">{{ __('Semua Role') }}</flux:select.option>
                @foreach ($this->roles as $roleOption)
                    <flux:select.option value="{{ $roleOption->name }}">{{ $roleOption->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Nama') }}</th>
                        <th class="px-4 py-3">{{ __('Email') }}</th>
                        <th class="px-4 py-3">{{ __('Telepon') }}</th>
                        <th class="px-4 py-3">{{ __('Role') }}</th>
                        <th class="w-32 px-4 py-3 text-right">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->users as $user)
                        <tr>
                            <td class="px-4 py-3 font-medium text-zinc-950 dark:text-white">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $user->phone ?: '-' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $user->roles->pluck('name')->join(', ') ?: '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    @can('users.edit')
                                        <flux:button size="sm" variant="ghost" icon="pencil" :href="route('users.edit', $user)" wire:navigate />
                                    @endcan
                                    @can('users.delete')
                                        @if (auth()->id() !== $user->id)
                                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $user->id }})" wire:confirm="{{ __('Hapus akun ini?') }}" />
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">{{ __('Data akun tidak ditemukan.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
            {{ $this->users->links() }}
        </div>
    </div>
</section>
