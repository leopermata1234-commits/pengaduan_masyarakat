<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Appearance settings')] class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Appearance settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Tampilan')" :subheading="__('Website menggunakan satu tema terang yang konsisten.')">
        <div class="rounded-xl border border-[#dfd4c6] bg-[#fffdf8] p-4 text-sm text-[#625b53]">
            {{ __('Tema terang aktif untuk seluruh halaman.') }}
        </div>
    </x-pages::settings.layout>
</section>
