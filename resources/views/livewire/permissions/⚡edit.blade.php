<?php

use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

new #[Title('Edit Permission')] class extends Component
{
    public Permission $permission;

    public string $name = '';

    public function mount(Permission $permission): void
    {
        $this->permission = $permission;
        $this->name = $permission->name;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions', 'name')->ignore($this->permission->id)],
        ]);

        $this->permission->update(['name' => $validated['name']]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->redirectRoute('permissions.index', navigate: true);
    }
};
?>

<section class="mx-auto flex w-full max-w-2xl flex-col gap-6">
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-2 text-sm text-zinc-500"><a href="{{ route('permissions.index') }}" wire:navigate>{{ __('Permissions') }}</a><span>/</span><span>{{ __('Edit') }}</span></div>
        <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Edit Permission') }}</h1>
    </div>
    <form wire:submit="save" class="space-y-5 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:input wire:model="name" :label="__('Nama Permission')" required />
        <div class="flex justify-end gap-2">
            <flux:button variant="filled" :href="route('permissions.index')" wire:navigate>{{ __('Batal') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button>
        </div>
    </form>
</section>
