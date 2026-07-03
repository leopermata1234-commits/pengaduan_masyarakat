<?php

use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

new #[Title('Tambah Permission')] class extends Component
{
    public string $name = '';

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions', 'name')],
        ]);

        Permission::create(['name' => $validated['name'], 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->redirectRoute('permissions.index', navigate: true);
    }
};
?>

<section class="mx-auto flex w-full max-w-2xl flex-col gap-6">
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-2 text-sm text-zinc-500"><a href="{{ route('permissions.index') }}" wire:navigate>{{ __('Permissions') }}</a><span>/</span><span>{{ __('Tambah') }}</span></div>
        <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Tambah Permission') }}</h1>
    </div>
    <form wire:submit="save" class="space-y-5 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:input wire:model="name" :label="__('Nama Permission')" placeholder="contoh: pengaduan.view" required />
        <div class="flex justify-end gap-2">
            <flux:button variant="filled" :href="route('permissions.index')" wire:navigate>{{ __('Batal') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button>
        </div>
    </form>
</section>
