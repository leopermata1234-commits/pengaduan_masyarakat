<?php

namespace App\Models;

use Database\Factories\PengaduanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pengaduan extends Model
{
    public const STATUS_MENUNGGU = 'Menunggu';

    public const STATUS_PENDING = self::STATUS_MENUNGGU;

    public const STATUS_DIPROSES = 'Diproses';

    public const STATUS_DITOLAK = 'Ditolak';

    public const STATUS_SELESAI = 'Selesai';

    public const VISIBILITAS_PUBLIK = 'Publik';

    public const VISIBILITAS_PRIVAT = 'Privat';

    public const STATUSES = [
        self::STATUS_MENUNGGU,
        self::STATUS_DIPROSES,
        self::STATUS_DITOLAK,
        self::STATUS_SELESAI,
    ];

    public const VISIBILITAS = [
        self::VISIBILITAS_PUBLIK,
        self::VISIBILITAS_PRIVAT,
    ];

    /** @use HasFactory<PengaduanFactory> */
    use HasFactory;

    protected $table = 'pengaduan';

    protected $fillable = [
        'user_id',
        'judul',
        'isi_pengaduan',
        'foto',
        'status',
        'visibilitas',
    ];

    /**
     * @param  Builder<Pengaduan>  $query
     * @return Builder<Pengaduan>
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user?->hasRole('Masyarakat')) {
            return $query;
        }

        return $query->where(fn (Builder $query) => $query
            ->where('user_id', $user->id)
            ->orWhere('visibilitas', self::VISIBILITAS_PUBLIK));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<TanggapanPengaduan, $this>
     */
    public function tanggapan(): HasMany
    {
        return $this->hasMany(TanggapanPengaduan::class);
    }
}
