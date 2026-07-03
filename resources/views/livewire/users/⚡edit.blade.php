<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

new #[Title('Edit User')] class extends Component
{
    public User $user;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $role = '';

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = (string) $user->phone;
        $this->role = $user->roles()->first()?->name ?? 'Masyarakat';
    }

    #[Computed]
    public function roles()
    {
        return Role::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::exists('roles', 'name')],
        ]);

        $this->user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
            ...($validated['password'] ? ['password' => Hash::make($validated['password'])] : []),
        ]);

        $this->user->syncRoles([$validated['role']]);

        $this->redirectRoute('users.index', navigate: true);
    }
};
?>

<section class="mx-auto flex w-full max-w-3xl flex-col gap-6">
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
            <a href="{{ route('users.index') }}" wire:navigate>{{ __('Users') }}</a>
            <span>/</span>
            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ __('Edit') }}</span>
        </div>
        <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Edit User') }}</h1>
    </div>

    <form wire:submit="save" class="space-y-5 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:input wire:model="name" :label="__('Nama')" required />
        <flux:input wire:model="email" :label="__('Email')" type="email" required />
        <flux:input wire:model="phone" :label="__('Telepon')" />
        <flux:input wire:model="password" :label="__('Password Baru')" type="password" />
        <flux:select wire:model="role" :label="__('Role')">
            @foreach ($this->roles as $roleOption)
                <flux:select.option value="{{ $roleOption->name }}">{{ $roleOption->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex justify-end gap-2">
            <flux:button variant="filled" :href="route('users.index')" wire:navigate>{{ __('Batal') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button>
        </div>
    </form>
</section>
