<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pendaftaran extends Model
{
    protected $table = 'pendaftaran';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = null;

    protected $fillable = [
        'pengguna_id',
        'lomba_id',
        'nama_peserta',
        'tipe_tim',
        'anggota2',
        'anggota3',
        'nomor_induk',
        'asal',
        'email',
        'whatsapp',
        'file_kartu',
        'tahap',
        'status',
        'catatan_admin',
    ];

    protected function casts(): array
    {
        return [
            'dibuat_pada' => 'datetime',
        ];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id');
    }

    public function lomba(): BelongsTo
    {
        return $this->belongsTo(Lomba::class, 'lomba_id');
    }

    public function karya(): HasOne
    {
        return $this->hasOne(Karya::class, 'pendaftaran_id');
    }
}
