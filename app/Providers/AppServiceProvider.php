<?php

namespace App\Providers;

use App\Models\DokumentasiKegiatan;
use App\Models\Pengaduan;
use App\Models\ProgramBanjar;
use App\Policies\DokumentasiKegiatanPolicy;
use App\Policies\PengaduanPolicy;
use App\Policies\ProgramBanjarPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function configureAuthorization(): void
    {
        Gate::before(fn ($user) => $user->hasRole('Super Admin') ? true : null);

        Gate::policy(Pengaduan::class, PengaduanPolicy::class);
        Gate::policy(ProgramBanjar::class, ProgramBanjarPolicy::class);
        Gate::policy(DokumentasiKegiatan::class, DokumentasiKegiatanPolicy::class);
    }
}
