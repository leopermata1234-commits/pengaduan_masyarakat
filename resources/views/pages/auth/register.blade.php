<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
    <head>
        @include('partials.head', ['title' => __('Daftar')])
    </head>
    <body class="min-h-screen bg-[#172231] font-sans text-slate-900 antialiased">
        <main class="relative isolate flex min-h-screen items-center overflow-hidden px-4 py-6 sm:px-6 lg:px-10 lg:py-8">
            <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
                <div class="absolute -left-32 top-1/3 h-96 w-96 rounded-full bg-teal-500/10 blur-3xl"></div>
                <div class="absolute -right-24 -top-24 h-[30rem] w-[30rem] rounded-full bg-[#a7774d]/10 blur-3xl"></div>
                <div class="absolute bottom-0 left-1/2 h-56 w-[42rem] -translate-x-1/2 rounded-full bg-black/20 blur-3xl"></div>
            </div>

            <section class="mx-auto grid w-full max-w-6xl overflow-hidden rounded-[1.35rem] bg-white shadow-[0_28px_80px_rgba(2,8,23,.38)] lg:grid-cols-2">
                <aside class="relative hidden min-h-full overflow-hidden bg-gradient-to-br from-[#16404a] via-[#16545a] to-[#168477] px-12 py-14 text-white lg:flex lg:flex-col lg:justify-center">
                    <div class="absolute -right-24 -top-24 size-72 rounded-full bg-teal-300/15 blur-2xl"></div>
                    <div class="absolute -bottom-32 -left-20 size-80 rounded-full bg-slate-950/30 blur-3xl"></div>
                    <div class="relative z-10 max-w-md">
                        <span class="flex size-14 items-center justify-center rounded-2xl bg-white/15 text-white ring-1 ring-white/20">
                            <x-app-logo-icon class="size-8" />
                        </span>
                        <h2 class="mt-7 font-serif text-4xl font-bold leading-tight xl:text-5xl">{{ __('Bergabung dengan Layanan Banjar') }}</h2>
                        <p class="mt-5 text-base leading-7 text-teal-50/85">{{ __('Buat akun untuk menyampaikan pengaduan, memantau proses penanganan, dan menerima informasi terbaru dari Banjar Puluk-Puluk.') }}</p>

                        <div class="mt-9 space-y-4 text-sm text-teal-50/90">
                            @foreach ([
                                __('Kirim dan lacak pengaduan secara langsung'),
                                __('Pilih laporan publik atau privat'),
                                __('Terima perkembangan penanganan laporan'),
                            ] as $benefit)
                                <div class="flex items-center gap-3">
                                    <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-[#d4a16d] text-xs font-bold text-white">&check;</span>
                                    <span>{{ $benefit }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </aside>

                <div class="px-6 py-8 sm:px-10 sm:py-10 lg:px-14 lg:py-12">
                    <div class="mb-7 text-center">
                        <div class="mx-auto flex w-fit items-center gap-3 text-left">
                            <span class="flex size-14 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-500 to-teal-700 text-white shadow-lg shadow-teal-700/20">
                                <x-app-logo-icon class="size-8" />
                            </span>
                            <div class="leading-tight">
                                <span class="block text-xl font-extrabold tracking-tight text-slate-800">{{ __('Portal Pengaduan') }}</span>
                                <span class="block text-xs font-semibold uppercase tracking-[.18em] text-teal-600">{{ __('Banjar Puluk-Puluk') }}</span>
                            </div>
                        </div>
                        <h1 class="mt-5 font-serif text-3xl font-bold tracking-tight text-[#2f241b] sm:text-4xl">{{ __('Buat Akun') }}</h1>
                        <p class="mt-3 text-sm leading-6 text-slate-500">{{ __('Lengkapi data berikut untuk mulai menggunakan layanan masyarakat.') }}</p>
                    </div>

                    <x-auth-session-status class="mb-4 text-center" :status="session('status')" />

                    @if ($teamInvitation)
                        <div class="mb-4">
                            <x-team-invitation-alert :invitation="$teamInvitation" :action="__('Daftar')" />
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label for="name" class="text-sm font-semibold text-slate-700">{{ __('Nama Lengkap') }}</label>
                            <input id="name" name="name" value="{{ old('name') }}" type="text" required autofocus autocomplete="name" placeholder="{{ __('Nama lengkap') }}" class="mt-2 h-13 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10">
                            @error('name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="email" class="text-sm font-semibold text-slate-700">{{ __('Alamat Email') }}</label>
                            <input id="email" name="email" value="{{ old('email') }}" type="email" required autocomplete="email" placeholder="email@example.com" class="mt-2 h-13 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10">
                            @error('email') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="password" class="text-sm font-semibold text-slate-700">{{ __('Password') }}</label>
                                <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="{{ __('Password') }}" class="mt-2 h-13 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10">
                                @error('password') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="password_confirmation" class="text-sm font-semibold text-slate-700">{{ __('Konfirmasi Password') }}</label>
                                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="{{ __('Ulangi password') }}" class="mt-2 h-13 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10">
                            </div>
                        </div>

                        <button type="submit" data-test="register-user-button" class="flex h-14 w-full items-center justify-center rounded-xl bg-gradient-to-r from-teal-600 to-teal-500 font-bold text-white shadow-lg shadow-teal-700/20 transition hover:-translate-y-0.5 hover:from-teal-500 hover:to-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-500/25 active:translate-y-0">
                            {{ __('Daftar') }}
                        </button>
                    </form>

                    <p class="mt-5 text-center text-sm text-slate-600">
                        {{ __('Sudah memiliki akun?') }}
                        <a href="{{ $teamInvitation ? route('login', ['invitation' => $teamInvitation['code']]) : route('login') }}" data-test="team-invitation-login-link" class="font-bold text-teal-700 hover:text-teal-600" wire:navigate>{{ __('Masuk') }}</a>
                    </p>
                </div>
            </section>
        </main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
