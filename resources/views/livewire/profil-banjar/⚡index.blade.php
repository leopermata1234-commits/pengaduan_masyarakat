<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profil Banjar')] class extends Component
{
    public array $missions = [
        'Meningkatkan pelayanan informasi dan pengaduan masyarakat secara terbuka.',
        'Menjaga nilai adat, budaya, dan kebersamaan warga Banjar Puluk-Puluk.',
        'Mendorong partisipasi masyarakat dalam kegiatan pembangunan banjar.',
        'Mengelola administrasi banjar dengan tertib, transparan, dan bertanggung jawab.',
    ];
};
?>

<section class="flex w-full flex-col gap-6">
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
            <span>{{ __('Layanan') }}</span>
            <span>/</span>
            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ __('Profil Banjar') }}</span>
        </div>

        <div class="flex flex-col gap-1">
            <h1 class="text-3xl font-bold tracking-normal text-zinc-950 dark:text-white">{{ __('Profil Banjar') }}</h1>
            <p class="max-w-3xl text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                {{ __('Informasi umum Banjar Puluk-Puluk, mulai dari visi misi, peta lokasi, hingga struktur organisasi pengurus banjar.') }}
            </p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_1.3fr]">
        <article class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-semibold uppercase tracking-normal text-[#34A99D]">{{ __('1. Visi') }}</p>
            <h2 class="mt-3 text-2xl font-semibold leading-8 text-zinc-950 dark:text-white">{{ __('Mewujudkan Banjar Puluk-Puluk yang harmonis, tertib, transparan, dan berdaya dalam pelayanan masyarakat.') }}</h2>
        </article>

        <article class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-semibold uppercase tracking-normal text-[#34A99D]">{{ __('Misi') }}</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach ($missions as $mission)
                    <div class="rounded-lg border border-[#34A99D]/20 bg-[#EAF8F6] p-4 text-sm leading-6 text-zinc-700 dark:border-[#34A99D]/30 dark:bg-[#34A99D]/10 dark:text-zinc-200">
                        {{ $mission }}
                    </div>
                @endforeach
            </div>
        </article>
    </div>

    <article class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-col gap-2">
            <p class="text-sm font-semibold uppercase tracking-normal text-[#34A99D]">{{ __('2. Struktur Organisasi') }}</p>
            <h2 class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ __('Pengurus Banjar Puluk-Puluk') }}</h2>
            <p class="text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                {{ __('Struktur organisasi Banjar Puluk-Puluk berdasarkan bagan pengurus yang tersedia.') }}
            </p>
        </div>

        <div class="mt-6 overflow-x-auto rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-950">
            <img
                src="{{ asset('images/struktur-banjar.png') }}"
                alt="{{ __('Struktur Organisasi Banjar Puluk-Puluk') }}"
                class="mx-auto h-auto w-full min-w-[480px] max-w-3xl object-contain"
            >
        </div>
    </article>

    <div class="grid gap-6 xl:grid-cols-[1.15fr_.85fr]">
        <article class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                <p class="text-sm font-semibold uppercase tracking-normal text-[#34A99D]">{{ __('Peta Lokasi') }}</p>
                <h2 class="mt-2 text-xl font-semibold text-zinc-950 dark:text-white">{{ __('Banjar Puluk-Puluk') }}</h2>
                <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                    {{ __('Titik lokasi dapat diganti nanti dengan tautan Google Maps atau koordinat resmi banjar.') }}
                </p>
            </div>

            <div class="relative aspect-[16/9] min-h-72 overflow-hidden bg-[#EAF8F6]">
                <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(52,169,157,.18)_1px,transparent_1px),linear-gradient(0deg,rgba(52,169,157,.18)_1px,transparent_1px)] bg-[size:48px_48px]"></div>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(52,169,157,.35),transparent_36%)]"></div>
                <div class="absolute left-1/2 top-1/2 flex -translate-x-1/2 -translate-y-1/2 flex-col items-center gap-3 text-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-[#34A99D] text-2xl font-bold text-white shadow-lg ring-8 ring-white/80">
                        {{ __('P') }}
                    </div>
                    <div class="rounded-lg bg-white px-5 py-3 text-sm font-semibold text-zinc-800 shadow-md ring-1 ring-zinc-200">
                        {{ __('Lokasi Banjar Puluk-Puluk') }}
                    </div>
                </div>
            </div>
        </article>

        <article class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-semibold uppercase tracking-normal text-[#34A99D]">{{ __('Data Banjar') }}</p>
            <div class="mt-5 space-y-4 text-sm leading-6 text-zinc-700 dark:text-zinc-300">
                <div>
                    <p class="font-semibold text-zinc-950 dark:text-white">{{ __('Nama Banjar') }}</p>
                    <p>{{ __('Banjar Puluk-Puluk') }}</p>
                </div>
                <div>
                    <p class="font-semibold text-zinc-950 dark:text-white">{{ __('Desa') }}</p>
                    <p>{{ __('Desa Senganan') }}</p>
                </div>
                <div>
                    <p class="font-semibold text-zinc-950 dark:text-white">{{ __('Kecamatan') }}</p>
                    <p>{{ __('Penebel') }}</p>
                </div>
                <div>
                    <p class="font-semibold text-zinc-950 dark:text-white">{{ __('Kabupaten') }}</p>
                    <p>{{ __('Tabanan, Bali') }}</p>
                </div>
            </div>
        </article>
    </div>
</section>
