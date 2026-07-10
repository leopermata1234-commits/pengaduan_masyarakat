<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => __('Masuk')])
    </head>
    <body class="min-h-screen bg-[#172231] font-sans text-slate-900 antialiased">
        <main class="relative isolate flex min-h-screen items-center overflow-hidden px-4 py-6 sm:px-6 lg:px-10 lg:py-8">
            <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
                <div class="absolute -left-32 top-1/3 h-96 w-96 rounded-full bg-teal-500/8 blur-3xl"></div>
                <div class="absolute -right-24 -top-24 h-[30rem] w-[30rem] rounded-full bg-slate-500/10 blur-3xl"></div>
                <div class="absolute bottom-0 left-1/2 h-56 w-[42rem] -translate-x-1/2 rounded-full bg-black/20 blur-3xl"></div>
            </div>

            <div class="mx-auto w-full max-w-6xl">
                <nav class="mb-5 flex items-center justify-between lg:mb-7" aria-label="Navigasi autentikasi">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-white" wire:navigate>
                        <span class="flex size-10 items-center justify-center rounded-xl bg-teal-500/15 ring-1 ring-white/15">
                            <x-app-logo-icon class="size-6 text-teal-300" />
                        </span>
                        <span class="hidden text-sm font-semibold tracking-wide sm:block">Portal Pengaduan Masyarakat</span>
                    </a>

                    <div class="flex items-center gap-2.5 sm:gap-3">
                        <a href="{{ route('login') }}" aria-current="page" class="rounded-lg border border-white/55 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10 sm:px-7">
                            Masuk
                        </a>
                        <a href="{{ $teamInvitation ? route('register', ['invitation' => $teamInvitation['code']]) : route('register') }}" class="rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-teal-950/20 transition hover:bg-teal-500 sm:px-7" wire:navigate>
                            Daftar
                        </a>
                    </div>
                </nav>

                <section class="grid overflow-hidden rounded-[1.35rem] bg-white shadow-[0_28px_80px_rgba(2,8,23,0.38)] lg:grid-cols-2">
                    <div class="px-6 py-8 sm:px-10 sm:py-10 lg:px-14 lg:py-12">
                        <div class="mb-7 flex flex-col items-center text-center">
                            <div class="mb-3 flex items-center gap-3">
                                <span class="flex size-14 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-500 to-teal-700 text-white shadow-lg shadow-teal-700/20">
                                    <x-app-logo-icon class="size-8" />
                                </span>
                                <div class="text-left leading-tight">
                                    <span class="block text-xl font-extrabold tracking-tight text-slate-800">Portal Pengaduan</span>
                                    <span class="block text-xs font-semibold uppercase tracking-[0.18em] text-teal-600">Banjar Puluk-Puluk</span>
                                </div>
                            </div>
                            <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">Selamat Datang</h1>
                            <p class="mt-3 text-sm leading-6 text-slate-500 sm:text-base">Silakan masuk ke akun Anda untuk melanjutkan laporan.</p>
                        </div>

                        <x-auth-session-status class="mb-4 text-center" :status="session('status')" />

                        @if ($teamInvitation)
                            <div class="mb-4">
                                <x-team-invitation-alert :invitation="$teamInvitation" :action="__('Masuk')" />
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                            @csrf

                            <div>
                                <label for="email" class="sr-only">Alamat Email</label>
                                <div class="relative">
                                    <svg class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.1a7.5 7.5 0 0 1 15 0A17.9 17.9 0 0 1 12 21.75a17.9 17.9 0 0 1-7.5-1.65Z"/></svg>
                                    <input id="email" name="email" value="{{ old('email') }}" type="email" required autofocus autocomplete="email" placeholder="Alamat Email" class="h-14 w-full rounded-xl border bg-white pl-12 pr-4 text-[15px] text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10 {{ $errors->has('email') ? 'border-red-400' : 'border-slate-300' }}">
                                </div>
                                @error('email')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password" class="sr-only">Password</label>
                                <div class="relative">
                                    <svg class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 0 0-9 0v3.75m-.75 11.25h10.5A2.25 2.25 0 0 0 19.5 19.5v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                                    <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Password" class="h-14 w-full rounded-xl border border-slate-300 bg-white pl-12 pr-28 text-[15px] text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-4 focus:ring-teal-500/10">
                                    @if (Route::has('password.request'))
                                        <a href="{{ route('password.request') }}" class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-semibold text-teal-700 transition hover:text-teal-600 sm:text-sm" wire:navigate>Lupa password?</a>
                                    @endif
                                </div>
                                @error('password')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-500">
                                <input name="remember" type="checkbox" @checked(old('remember')) class="size-4 rounded border-slate-300 text-teal-600 accent-teal-600 focus:ring-teal-500">
                                Ingat saya
                            </label>

                            <button type="submit" data-test="login-button" class="flex h-14 w-full items-center justify-center rounded-xl bg-gradient-to-r from-teal-600 to-teal-500 font-bold text-white shadow-lg shadow-teal-700/20 transition hover:-translate-y-0.5 hover:from-teal-500 hover:to-teal-500 focus:outline-none focus:ring-4 focus:ring-teal-500/25 active:translate-y-0">
                                Masuk
                            </button>
                        </form>

                        <p class="mt-5 text-center text-sm text-slate-600">
                            Belum punya akun?
                            <a href="{{ $teamInvitation ? route('register', ['invitation' => $teamInvitation['code']]) : route('register') }}" data-test="register-link" class="font-bold text-teal-700 hover:text-teal-600" wire:navigate>Daftar Sekarang</a>
                        </p>
                    </div>

                    <aside class="relative hidden min-h-full overflow-hidden bg-gradient-to-br from-[#16404a] via-[#16545a] to-[#168477] px-12 py-14 text-white lg:flex lg:flex-col lg:justify-center">
                        <div class="absolute -right-24 -top-24 size-72 rounded-full bg-teal-300/15 blur-2xl"></div>
                        <div class="absolute -bottom-32 -left-20 size-80 rounded-full bg-slate-950/30 blur-3xl"></div>
                        <div class="relative z-10 max-w-md">
                            <div class="mb-7 flex items-center gap-5">
                                <h2 class="text-4xl font-extrabold leading-[1.12] tracking-tight xl:text-5xl">Transparansi<br>dan Responsif</h2>
                                <svg class="mt-1 size-16 shrink-0 text-teal-100/65" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="m32 4 7 5 9-1 3 8 8 4-1 9 5 7-5 7 1 9-8 4-3 8-9-1-7 5-7-5-9 1-3-8-8-4 1-9-5-7 5-7-1-9 8-4 3-8 9 1 7-5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="m21 35 7 7 15-17"/></svg>
                            </div>
                            <blockquote class="text-xl italic leading-9 text-teal-50/90">
                                “Sampaikan aspirasi dan laporan Anda langsung kepada instansi berwenang demi kemajuan komunitas kita.”
                            </blockquote>
                            <p class="mt-14 text-sm font-medium text-teal-50/80">© {{ date('Y') }} Layanan Resmi Banjar Puluk-Puluk.</p>
                        </div>
                    </aside>
                </section>
            </div>
        </main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
