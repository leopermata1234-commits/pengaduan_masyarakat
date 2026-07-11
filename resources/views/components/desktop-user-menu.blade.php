@props(['showTeam' => true, 'topbar' => false])

<flux:dropdown position="bottom" :align="$topbar ? 'end' : 'start'">
    <button type="button" @class([
        'group flex items-center rounded-xl p-2 transition',
        'admin-topbar-profile bg-[#13746e] pr-4 text-white shadow-sm hover:bg-[#0f625d]' => $topbar,
        'admin-sidebar-profile w-full border-white/15 bg-white/8 text-white hover:bg-white/15' => ! $topbar,
    ]) data-test="sidebar-menu-button">
        <flux:avatar :initials="auth()->user()->initials()" size="sm" />
        <div @class(['mx-2 grid flex-1 text-start text-sm leading-tight', 'in-data-flux-sidebar-collapsed-desktop:hidden' => ! $topbar])>
            <span class="truncate font-semibold text-white">{{ auth()->user()->name }}</span>
            @if($showTeam && auth()->user()->currentTeam)
                <span @class(['truncate text-xs', 'text-[#756b62]' => $topbar, 'text-white/65' => ! $topbar])>{{ auth()->user()->currentTeam->name }}</span>
            @endif
        </div>
        @if (! $topbar)
            <flux:icon name="chevrons-up-down" variant="micro" class="in-data-flux-sidebar-collapsed-desktop:hidden ms-auto size-4 text-white/75 group-hover:text-white" />
        @endif
    </button>

    <flux:menu>
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
        <flux:menu.separator />
        <flux:menu.radio.group>
            <flux:menu.item :href="route('profile.edit')" icon="identification" wire:navigate>
                {{ __('Profil') }}
            </flux:menu.item>
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
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>
