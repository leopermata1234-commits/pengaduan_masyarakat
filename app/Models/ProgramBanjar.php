<?php

namespace App\Models;

use Database\Factories\ProgramBanjarFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramBanjar extends Model
{
    public const STATUS_DRAFT = 'Draft';

    public const STATUS_PUBLISHED = 'Published';

    public const STATUS_SELESAI = 'Selesai';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
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
        ];
    }
}
