<?php

namespace App\Models;

use Database\Factories\DokumentasiKegiatanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumentasiKegiatan extends Model
{
    public const STATUS_DRAFT = 'Draft';

    public const STATUS_PUBLISHED = 'Published';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
    ];

    /** @use HasFactory<DokumentasiKegiatanFactory> */
    use HasFactory;

    protected $table = 'dokumentasi_kegiatan';

    protected $fillable = [
        'user_id',
        'judul',
        'deskripsi',
        'tanggal',
        'foto',
        'fotos',
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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'fotos' => 'array',
        ];
    }
}
