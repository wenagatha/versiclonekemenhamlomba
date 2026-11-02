<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lomba extends Model
{
    protected $table = 'lomba';

    public $timestamps = false;

    protected $fillable = [
        'kode',
        'nama',
        'label_nomor',
        'label_asal',
        'batas_daftar',
        'butuh_word',
    ];

    protected function casts(): array
    {
        return [
            'batas_daftar' => 'datetime',
            'butuh_word' => 'boolean',
        ];
    }

    public function pendaftaran(): HasMany
    {
        return $this->hasMany(Pendaftaran::class, 'lomba_id');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'lomba_id');
    }
}
