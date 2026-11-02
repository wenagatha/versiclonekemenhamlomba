<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Karya extends Model
{
    protected $table = 'karya';

    public const CREATED_AT = 'dikirim_pada';

    public const UPDATED_AT = null;

    protected $fillable = [
        'pendaftaran_id',
        'judul',
        'file_pdf',
        'file_word',
        'file_kartu',
        'file_pernyataan',
    ];

    protected function casts(): array
    {
        return [
            'dikirim_pada' => 'datetime',
        ];
    }

    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class, 'pendaftaran_id');
    }
}
