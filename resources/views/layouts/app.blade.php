<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        @if (auth()->user()->hasRole('Masyarakat') && request()->routeIs('profil-banjar.*', 'program.*', 'dokumentasi.*', 'pengaduan.*'))
            <header class="mx-auto mb-6 flex w-full max-w-[1680px] flex-col gap-4 rounded-lg bg-[#34A99D] px-5 py-4 text-white shadow-sm lg:flex-row lg:items-center lg:justify-between lg:px-8">
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
                    <a href="{{ route('pengaduan.index') }}" wire:navigate class="shrink-0 rounded-md bg-teal-600 px-4 py-2 text-white shadow-sm transition hover:bg-teal-700 {{ request()->routeIs('pengaduan.*') ? 'ring-2 ring-white/70' : '' }}">{{ __('Pengaduan') }}</a>
                    <flux:dropdown position="bottom" align="end">
                        <button type="button" class="flex shrink-0 items-center gap-2 rounded-md border border-white/30 px-3 py-2 text-white/90 transition hover:bg-white/10">
                            <flux:avatar :initials="auth()->user()->initials()" size="xs" />
                            <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
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

                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                                {{ __('Profil') }}
                            </flux:menu.item>

                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">
                                    {{ __('Keluar') }}
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                </nav>
            </header>
        @endif

        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
