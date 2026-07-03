<?php

use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

new #[Title('Tambah Role')] class extends Component
{
    public string $name = '';

    public array $selectedPermissions = [];

    #[Computed]
    public function permissions()
    {
        return Permission::query()->orderBy('name')->get()->groupBy(fn ($permission) => str($permission->name)->before('.')->toString());
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')],
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => [Rule::exists('permissions', 'name')],
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
        $role->syncPermissions($validated['selectedPermissions']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->redirectRoute('roles.index', navigate: true);
    }
};
?>

<section class="mx-auto flex w-full max-w-4xl flex-col gap-6">
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-2 text-sm text-zinc-500"><a href="{{ route('roles.index') }}" wire:navigate>{{ __('Roles') }}</a><span>/</span><span>{{ __('Tambah') }}</span></div>
        <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Tambah Role') }}</h1>
    </div>
    <form wire:submit="save" class="space-y-6 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:input wire:model="name" :label="__('Nama Role')" required />

        <div class="space-y-4">
            <p class="text-sm font-medium text-zinc-950 dark:text-white">{{ __('Permission') }}</p>
            @foreach ($this->permissions as $group => $permissions)
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <p class="mb-3 text-sm font-semibold capitalize text-zinc-700 dark:text-zinc-200">{{ $group }}</p>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($permissions as $permission)
                            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                                <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->name }}" class="rounded border-zinc-300">
                                <span>{{ $permission->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex justify-end gap-2">
            <flux:button variant="filled" :href="route('roles.index')" wire:navigate>{{ __('Batal') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button>
        </div>
    </form>
</section>
