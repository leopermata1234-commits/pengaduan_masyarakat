@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Layanan Banjar" {{ $attributes->class(['admin-sidebar-brand text-white!']) }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-white/15 text-white ring-1 ring-white/25">
            <x-app-logo-icon class="size-5 fill-current text-white" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Layanan Banjar" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
        </x-slot>
    </flux:brand>
@endif
