<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

new #[Title('Roles')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function roles()
    {
        return Role::query()
            ->withCount('permissions')
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(10);
    }

    public function delete(int $roleId): void
    {
        abort_unless(auth()->user()->can('role.delete'), 403);

        Role::findOrFail($roleId)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->resetPage();
    }
};
?>

<section class="mx-auto flex w-full max-w-7xl flex-col gap-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                <span>{{ __('Administrasi') }}</span><span>/</span><span class="font-medium text-zinc-800 dark:text-zinc-100">{{ __('Roles') }}</span>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Roles') }}</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Kelola role sistem dan cakupan aksesnya.') }}</p>
            </div>
        </div>

        @can('role.create')
            <flux:button icon="plus" variant="primary" :href="route('roles.create')" wire:navigate>{{ __('Tambah Role') }}</flux:button>
        @endcan
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
            <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass" :placeholder="__('Cari role')" />
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Role') }}</th>
                        <th class="px-4 py-3">{{ __('Permission') }}</th>
                        <th class="w-32 px-4 py-3 text-right">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($this->roles as $role)
                        <tr>
                            <td class="px-4 py-3 font-medium text-zinc-950 dark:text-white">{{ $role->name }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $role->permissions_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    @can('role.edit')
                                        <flux:button size="sm" variant="ghost" icon="pencil" :href="route('roles.edit', $role)" wire:navigate />
                                    @endcan
                                    @can('role.delete')
                                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $role->id }})" wire:confirm="{{ __('Hapus role ini?') }}" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-zinc-500">{{ __('Data role tidak ditemukan.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">{{ $this->roles->links() }}</div>
    </div>
</section>
