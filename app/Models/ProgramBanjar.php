<?php

namespace App\Models;

use Database\Factories\ProgramBanjarFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramBanjar extends Model
{
    public const STATUS_DRAFT = 'Draft';

    public const STATUS_RENCANA = 'Rencana';

    public const STATUS_BERJALAN = 'Berjalan';

    public const STATUS_SELESAI = 'Selesai';

    public const STATUS_PUBLISHED = self::STATUS_RENCANA;

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_RENCANA,
        self::STATUS_BERJALAN,
        self::STATUS_SELESAI,
    ];

    public const PUBLIC_STATUSES = [
        self::STATUS_RENCANA,
        self::STATUS_BERJALAN,
        self::STATUS_SELESAI,
    ];

    /** @use HasFactory<ProgramBanjarFactory> */
    use HasFactory;

    protected $table = 'program_banjar';

    protected $fillable = [
        'user_id',
        'judul',
        'deskripsi',
        'tanggal',
        'tanggal_mulai',
        'tanggal_selesai',
        'gambar',
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
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
        ];
    }
}
