<?php

/* @chisel-email-verification */
use Illuminate\Contracts\Auth\MustVerifyEmail;
/* @end-chisel-email-verification */
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profil Saya')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $jenis_kelamin = '';
    public string $password = '';
    public string $nik = '';
    public string $kk = '';
    public string $tanggal_lahir = '';

    public function mount(): void
    {
        $user = Auth::user();

        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
        $this->phone = (string) ($user->phone ?? '');
        $this->jenis_kelamin = (string) ($user->jenis_kelamin ?? '');
        $this->nik = (string) ($user->nik ?? '');
        $this->kk = (string) ($user->kk ?? '');
        $this->tanggal_lahir = $user->tanggal_lahir?->format('Y-m-d') ?? '';
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'jenis_kelamin' => ['nullable', Rule::in(['Laki-laki', 'Perempuan'])],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', Password::default()],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?: null,
            'jenis_kelamin' => $validated['jenis_kelamin'] ?: null,
            'email' => $validated['email'],
        ]);

        if (filled($validated['password'])) {
            $user->password = $validated['password'];
            $this->password = '';
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profil berhasil diperbarui.'));
    }

    /* @chisel-email-verification */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }
    /* @end-chisel-email-verification */
}; ?>

<section class="min-h-screen bg-slate-50 px-4 py-6 text-slate-900 sm:px-6 lg:px-8">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-teal-700">{{ __('Pengaturan Akun') }}</p>
                <h1 class="mt-1 text-3xl font-bold tracking-normal text-slate-900">{{ __('Profil Saya') }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    {{ __('Kelola data profil masyarakat dan pengaturan akun yang digunakan untuk mengakses layanan banjar.') }}
                </p>
            </div>

            <a href="{{ auth()->user()->hasRole('Masyarakat') ? route('beranda') : route('dashboard') }}" wire:navigate class="inline-flex w-fit items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100">
                {{ __('Kembali') }}
            </a>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <form id="profile-form" wire:submit="updateProfileInformation">
                <div class="grid gap-0 lg:grid-cols-[.9fr_1.1fr]">
                <div class="border-b border-slate-200 p-6 lg:border-b-0 lg:border-r">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('Data Kependudukan') }}</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('Data ini bersifat referensi dan tidak dapat diubah langsung dari halaman profil.') }}</p>
                    </div>

                    <div class="mt-6 space-y-4">
                        <div>
                            <label for="nik" class="text-sm font-medium text-slate-700">{{ __('Nomor Induk Kependudukan (NIK)') }}</label>
                            <input id="nik" type="text" value="{{ $nik ?: '-' }}" disabled class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 shadow-sm disabled:cursor-not-allowed">
                        </div>

                        <div>
                            <label for="kk" class="text-sm font-medium text-slate-700">{{ __('Nomor Kartu Keluarga (KK)') }}</label>
                            <input id="kk" type="text" value="{{ $kk ?: '-' }}" disabled class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 shadow-sm disabled:cursor-not-allowed">
                        </div>

                        <div>
                            <label for="tanggal_lahir" class="text-sm font-medium text-slate-700">{{ __('Tanggal Lahir') }}</label>
                            <input id="tanggal_lahir" type="text" value="{{ $tanggal_lahir ?: '-' }}" disabled class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 shadow-sm disabled:cursor-not-allowed">
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ __('Pengaturan Akun') }}</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('Perbarui informasi akun yang dapat digunakan untuk identitas dan komunikasi layanan.') }}</p>
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="name" class="text-sm font-medium text-slate-700">{{ __('Nama Tampilan') }}</label>
                            <input id="name" wire:model="name" type="text" required autocomplete="name" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-teal-600 focus:ring-4 focus:ring-teal-600/10">
                            @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="phone" class="text-sm font-medium text-slate-700">{{ __('No. HP (WhatsApp)') }}</label>
                            <input id="phone" wire:model="phone" type="text" autocomplete="tel" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-teal-600 focus:ring-4 focus:ring-teal-600/10">
                            @error('phone') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="jenis_kelamin" class="text-sm font-medium text-slate-700">{{ __('Jenis Kelamin') }}</label>
                            <select id="jenis_kelamin" wire:model="jenis_kelamin" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-teal-600 focus:ring-4 focus:ring-teal-600/10">
                                <option value="">{{ __('Pilih jenis kelamin') }}</option>
                                <option value="Laki-laki">{{ __('Laki-laki') }}</option>
                                <option value="Perempuan">{{ __('Perempuan') }}</option>
                            </select>
                            @error('jenis_kelamin') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="email" class="text-sm font-medium text-slate-700">{{ __('Email Akun') }}</label>
                            <input id="email" wire:model="email" type="email" required autocomplete="email" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-teal-600 focus:ring-4 focus:ring-teal-600/10">
                            @error('email') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                            {{-- @chisel-email-verification --}}
                            @if ($this->hasUnverifiedEmail)
                                <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                    {{ __('Email akun belum diverifikasi.') }}
                                    <button type="button" wire:click.prevent="resendVerificationNotification" class="font-semibold underline">
                                        {{ __('Kirim ulang verifikasi') }}
                                    </button>

                                    @if (session('status') === 'verification-link-sent')
                                        <p class="mt-2 font-semibold text-teal-700">{{ __('Link verifikasi baru sudah dikirim.') }}</p>
                                    @endif
                                </div>
                            @endif
                            {{-- @end-chisel-email-verification --}}
                        </div>

                        <div class="sm:col-span-2">
                            <label for="password" class="text-sm font-medium text-slate-700">{{ __('Ubah Password Baru') }}</label>
                            <input id="password" wire:model="password" type="password" autocomplete="new-password" placeholder="{{ __('Kosongkan jika tidak ingin mengubah password') }}" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-teal-600 focus:ring-4 focus:ring-teal-600/10">
                            @error('password') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>
            </form>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex w-fit items-center justify-center rounded-xl border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50">
                        {{ __('Logout') }}
                    </button>
                </form>

                <button type="submit" form="profile-form" class="inline-flex items-center justify-center rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-teal-600/20">
                    {{ __('Simpan Perubahan') }}
                </button>
            </div>
        </div>
    </div>
</section>
