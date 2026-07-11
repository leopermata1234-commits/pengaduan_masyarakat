<?php

namespace App\Models;

use Database\Factories\TanggapanPengaduanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TanggapanPengaduan extends Model
{
    /** @use HasFactory<TanggapanPengaduanFactory> */
    use HasFactory;

    protected $table = 'tanggapan_pengaduan';

    protected $fillable = [
        'pengaduan_id',
        'admin_id',
        'isi_tanggapan',
        'status',
        'foto',
    ];

    /**
     * @return BelongsTo<Pengaduan, $this>
     */
    public function pengaduan(): BelongsTo
    {
        return $this->belongsTo(Pengaduan::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
