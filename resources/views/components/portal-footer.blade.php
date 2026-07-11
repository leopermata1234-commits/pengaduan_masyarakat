<footer {{ $attributes->class(['portal-footer w-full overflow-hidden py-10 text-white shadow-sm']) }}>
    <div class="grid w-full gap-10 px-6 lg:grid-cols-2 lg:px-12">
        <div class="space-y-5">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-white text-[#34A99D] ring-1 ring-white/40">
                    <x-app-logo-icon class="h-6 w-6" />
                </span>
                <h2 class="text-base font-semibold uppercase tracking-normal">{{ __('BANJAR PULUK-PULUK') }}</h2>
            </div>
            <p class="max-w-sm text-sm leading-6 text-white/80">
                {{ __('Website resmi Banjar Puluk-Puluk. Media informasi transparan dan pusat layanan pengaduan masyarakat terpadu.') }}
            </p>
        </div>

        <div class="space-y-5">
            <div>
                <h2 class="text-base font-semibold">{{ __('Hubungi Kami') }}</h2>
                <div class="mt-2 h-1 w-24 rounded-full bg-white/80"></div>
            </div>
            <div class="space-y-3 text-sm leading-6 text-white/80">
                <p>{{ __('+62 896-5499-3430') }}</p>
                <p>{{ __('layanan.banjarpulukpuluk@gmail.com') }}</p>
                <p>{{ __('Banjar Puluk-Puluk, Desa Senganan, Kecamatan Penebel, Kabupaten Tabanan, Provinsi Bali') }}</p>
            </div>
        </div>
    </div>
</footer>
