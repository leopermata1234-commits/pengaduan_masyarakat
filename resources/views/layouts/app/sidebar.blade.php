<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen {{ auth()->check() && ! auth()->user()->hasRole('Masyarakat') ? 'admin-workspace' : '' }} {{ (! auth()->check() || auth()->user()->hasRole('Masyarakat')) && request()->routeIs('beranda', 'profil-banjar.*', 'program.*', 'dokumentasi.*', 'pengaduan.*') ? 'overflow-x-hidden overflow-y-auto bg-[#F4FAF9]' : 'bg-white' }} dark:bg-zinc-800">
        @if (auth()->check() && ! auth()->user()->hasRole('Masyarakat'))
            <flux:sidebar sticky collapsible="mobile" class="admin-sidebar border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:sidebar.header>
                    <div class="flex w-full items-center gap-2">
                        <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate class="min-w-0 flex-1" />
                        <livewire:notifications.pengaduan-bell position="bottom" />
                    </div>
                    <flux:sidebar.collapse class="lg:hidden" />
                </flux:sidebar.header>

                <flux:sidebar.nav>
                    <flux:sidebar.group :heading="__('Layanan')" class="grid">
                        @can('dashboard.view')
                            <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                                {{ __('Dashboard') }}
                            </flux:sidebar.item>
                        @endcan

                        @can('pengaduan.view')
                            <flux:sidebar.item icon="chat-bubble-left-right" :href="route('pengaduan.index')" :current="request()->routeIs('pengaduan.*')" wire:navigate>
                                {{ __('Tanggapan Pengaduan') }}
                            </flux:sidebar.item>
                        @endcan

                        @can('program.view')
                            <flux:sidebar.item icon="calendar-days" :href="route('program.index')" :current="request()->routeIs('program.*')" wire:navigate>
                                {{ __('Program') }}
                            </flux:sidebar.item>
                        @endcan

                        @can('dokumentasi.view')
                            <flux:sidebar.item icon="photo" :href="route('dokumentasi.index')" :current="request()->routeIs('dokumentasi.*')" wire:navigate>
                                {{ __('Galeri') }}
                            </flux:sidebar.item>
                        @endcan

                        @can('data.masyarakat')
                            <flux:sidebar.item icon="identification" :href="route('data-masyarakat.index')" :current="request()->routeIs('data-masyarakat.*')" wire:navigate>
                                {{ __('Data Masyarakat') }}
                            </flux:sidebar.item>
                        @endcan

                        @can('users.view')
                            <flux:sidebar.item icon="users" :href="route('users.index')" :current="request()->routeIs('users.*')" wire:navigate>
                                {{ __('Manajemen Akun') }}
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>

                    @canany(['role.view', 'permission.view'])
                        <flux:sidebar.group :heading="__('Administrasi')" class="grid">
                            @can('role.view')
                                <flux:sidebar.item icon="shield-check" :href="route('roles.index')" :current="request()->routeIs('roles.*')" wire:navigate>
                                    {{ __('Roles') }}
                                </flux:sidebar.item>
                            @endcan

                            @can('permission.view')
                                <flux:sidebar.item icon="key" :href="route('permissions.index')" :current="request()->routeIs('permissions.*')" wire:navigate>
                                    {{ __('Permissions') }}
                                </flux:sidebar.item>
                            @endcan
                        </flux:sidebar.group>
                    @endcanany
                </flux:sidebar.nav>

            <flux:spacer />
            </flux:sidebar>

            <!-- Mobile User Menu -->
            <flux:header class="admin-mobile-header lg:hidden">
                <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

                <flux:spacer />

                <livewire:notifications.pengaduan-bell />

                <flux:dropdown position="top" align="end">
                <button type="button" class="flex items-center gap-3 rounded-xl px-2 py-1.5 text-white transition hover:bg-white/10">
                    <flux:avatar :initials="auth()->user()->initials()" size="sm" />
                    <span class="hidden text-base font-medium sm:inline">{{ auth()->user()->name }}</span>
                </button>

                    <flux:menu>
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                    <flux:avatar
                                        :name="auth()->user()->name"
                                        :initials="auth()->user()->initials()"
                                    />

                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                        <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('profile.edit')" icon="identification" wire:navigate>
                                {{ __('Profil') }}
                            </flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item
                                as="button"
                                type="submit"
                                icon="arrow-right-start-on-rectangle"
                                class="w-full cursor-pointer"
                                data-test="logout-button"
                            >
                                {{ __('Log out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </flux:header>
        @endif

        {{ $slot }}

        @auth
            <livewire:create-team-modal />
        @endauth

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
