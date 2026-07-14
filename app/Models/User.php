<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Concerns\HasTeams;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $nik
 * @property string|null $kk
 * @property Carbon|null $tanggal_lahir
 * @property string|null $jenis_kelamin
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int|null $current_team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $currentTeam
 * @property-read Collection<int, DokumentasiKegiatan> $dokumentasiKegiatan
 * @property-read Collection<int, Team> $ownedTeams
 * @property-read Collection<int, Pengaduan> $pengaduan
 * @property-read Collection<int, ProgramBanjar> $programBanjar
 * @property-read Collection<int, TanggapanPengaduan> $tanggapanPengaduan
 * @property-read Collection<int, Membership> $teamMemberships
 * @property-read Collection<int, Team> $teams
 */
#[Fillable(['name', 'email', 'phone', 'nik', 'kk', 'tanggal_lahir', 'jenis_kelamin', 'password', 'current_team_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasTeams, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable {
        HasTeams::teams insteadof HasRoles;
        HasRoles::teams as permissionTeams;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'tanggal_lahir' => 'date',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * @return HasMany<Pengaduan, $this>
     */
    public function pengaduan(): HasMany
    {
        return $this->hasMany(Pengaduan::class);
    }

    /**
     * @return HasMany<TanggapanPengaduan, $this>
     */
    public function tanggapanPengaduan(): HasMany
    {
        return $this->hasMany(TanggapanPengaduan::class, 'admin_id');
    }

    /**
     * @return HasMany<ProgramBanjar, $this>
     */
    public function programBanjar(): HasMany
    {
        return $this->hasMany(ProgramBanjar::class);
    }

    /**
     * @return HasMany<DokumentasiKegiatan, $this>
     */
    public function dokumentasiKegiatan(): HasMany
    {
        return $this->hasMany(DokumentasiKegiatan::class);
    }
}
