@php
    $isMasyarakatPortal = (! auth()->check() || auth()->user()->hasRole('Masyarakat'))
        && request()->routeIs('beranda', 'profil-banjar.*', 'program.*', 'dokumentasi.*', 'pengaduan.*');
@endphp

<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main :class="$isMasyarakatPortal ? 'p-0!' : ''">
        @if (auth()->check() && ! auth()->user()->hasRole('Masyarakat'))
            <div class="mb-6 hidden items-center justify-end border-b border-[#dfd4c6] pb-4 lg:flex">
                <x-desktop-user-menu :showTeam="false" topbar />
            </div>
        @endif

        @if ($isMasyarakatPortal && ! request()->routeIs('beranda'))
            <header class="portal-header w-full text-white shadow-lg">
                <div class="flex w-full flex-col gap-4 px-5 py-4 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                <a href="{{ route('beranda') }}" wire:navigate class="flex min-w-0 items-center gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white text-[#34A99D] ring-2 ring-white/70">
                        <x-app-logo-icon class="h-7 w-7" />
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-base font-semibold">{{ __('Banjar Puluk-Puluk') }}</span>
                        <span class="block truncate text-sm text-white/85">{{ __('Layanan Masyarakat') }}</span>
                    </span>
                </a>

                <nav class="flex gap-2 overflow-x-auto text-sm font-medium">
                    <a href="{{ route('beranda') }}" wire:navigate class="shrink-0 border-b-2 border-transparent px-2 py-2 text-white/90 hover:border-white/80">{{ __('Beranda') }}</a>
                    <a href="{{ route('profil-banjar.index') }}" wire:navigate class="shrink-0 border-b-2 px-2 py-2 {{ request()->routeIs('profil-banjar.*') ? 'border-white text-white' : 'border-transparent text-white/90 hover:border-white/80' }}">{{ __('Profil Banjar') }}</a>
                    <a href="{{ route('program.index') }}" wire:navigate class="shrink-0 border-b-2 px-2 py-2 {{ request()->routeIs('program.*') ? 'border-white text-white' : 'border-transparent text-white/90 hover:border-white/80' }}">{{ __('Program') }}</a>
                    <a href="{{ route('dokumentasi.index') }}" wire:navigate class="shrink-0 border-b-2 px-2 py-2 {{ request()->routeIs('dokumentasi.*') ? 'border-white text-white' : 'border-transparent text-white/90 hover:border-white/80' }}">{{ __('Galeri') }}</a>
                    <a href="{{ route('pengaduan.index') }}" class="shrink-0 rounded-md bg-teal-600 px-4 py-2 text-white shadow-sm transition hover:bg-teal-700 {{ request()->routeIs('pengaduan.*') ? 'ring-2 ring-white/70' : '' }}">{{ __('Pengaduan') }}</a>
                    @auth
                    <flux:dropdown position="bottom" align="end">
                        <button type="button" class="flex shrink-0 items-center gap-3 rounded-xl px-2 py-1.5 text-white transition hover:bg-white/10">
                            <flux:avatar :initials="auth()->user()->initials()" size="sm" />
                            <span class="hidden text-base font-medium sm:inline">{{ auth()->user()->name }}</span>
                        </button>

                        <flux:menu>
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />
                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>

                            <flux:menu.separator />

                            <flux:menu.item :href="route('profile.edit')" icon="identification" wire:navigate>
                                {{ __('Profil') }}
                            </flux:menu.item>

                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">
                                {{ __('Log out') }}
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                    @else
                        <a href="{{ route('login') }}" class="shrink-0 rounded-md border border-white/40 px-4 py-2 text-white transition hover:bg-white/10">
                            {{ __('Masuk') }}
                        </a>
                    @endauth
                </nav>
                </div>
            </header>
        @endif

        @if ($isMasyarakatPortal && ! request()->routeIs('beranda'))
            <div class="public-portal relative isolate min-h-[calc(100vh-5.5rem)] w-full overflow-hidden bg-[#fffdf8] py-6 dark:bg-zinc-800 lg:py-8">
                @if (request()->routeIs('profil-banjar.*', 'program.*', 'dokumentasi.*', 'pengaduan.*'))
                    <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10 overflow-hidden text-[#9b6a3c]/8">
                        <svg viewBox="0 0 240 240" class="absolute -right-20 -top-20 h-[28rem] w-[28rem]">
                            <g fill="none" stroke="currentColor" stroke-width="5">
                                <circle cx="120" cy="120" r="28"/><circle cx="120" cy="120" r="49"/>
                                <path d="M120 12c17 24 21 45 0 59-21-14-17-35 0-59ZM120 228c-17-24-21-45 0-59 21 14 17 35 0 59ZM12 120c24-17 45-21 59 0-14 21-35 17-59 0ZM228 120c-24 17-45 21-59 0 14-21 35-17 59 0Z"/>
                                <path d="M44 44c29 5 46 17 41 42-25 5-37-12-41-42ZM196 196c-29-5-46-17-41-42 25-5 37 12 41 42ZM44 196c5-29 17-46 42-41 5 25-12 37-42 41ZM196 44c-5 29-17 46-42 41-5-25 12-37 42-41Z"/>
                            </g>
                        </svg>
                        <svg viewBox="0 0 240 240" class="absolute -bottom-28 -left-24 h-[25rem] w-[25rem]">
                            <g fill="none" stroke="currentColor" stroke-width="5">
                                <circle cx="120" cy="120" r="28"/><circle cx="120" cy="120" r="49"/>
                                <path d="M120 12c17 24 21 45 0 59-21-14-17-35 0-59ZM120 228c-17-24-21-45 0-59 21 14 17 35 0 59ZM12 120c24-17 45-21 59 0-14 21-35 17-59 0ZM228 120c-24 17-45 21-59 0 14-21 35-17 59 0Z"/>
                                <path d="M44 44c29 5 46 17 41 42-25 5-37-12-41-42ZM196 196c-29-5-46-17-41-42 25-5 37 12 41 42ZM44 196c5-29 17-46 42-41 5 25-12 37-42 41ZM196 44c-5 29-17 46-42 41-5-25 12-37 42-41Z"/>
                            </g>
                        </svg>
                    </div>
                @endif
                <div class="portal-page-stage mx-auto w-full max-w-6xl px-6 lg:px-8">
                    {{ $slot }}
                </div>
            </div>
            <x-portal-footer />
        @else
            {{ $slot }}
        @endif
    </flux:main>
</x-layouts::app.sidebar>
