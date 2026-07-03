<?php

namespace App\Models;

use Database\Factories\PengaduanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pengaduan extends Model
{
    public const STATUS_PENDING = 'Pending';

    public const STATUS_DIPROSES = 'Diproses';

    public const STATUS_DITOLAK = 'Ditolak';

    public const STATUS_SELESAI = 'Selesai';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_DIPROSES,
        self::STATUS_DITOLAK,
        self::STATUS_SELESAI,
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
    ];

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
