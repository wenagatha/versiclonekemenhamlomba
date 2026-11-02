<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Pengguna extends Authenticatable
{
    protected $table = 'pengguna';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = null;

    protected $fillable = [
        'nama_depan',
        'nama_belakang',
        'email',
        'password_hash',
        'role',
        'telepon',
        'gender',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat',
    ];

    protected $hidden = ['password_hash'];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'dibuat_pada' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function pendaftaran(): HasMany
    {
        return $this->hasMany(Pendaftaran::class, 'pengguna_id');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'pengguna_id');
    }
}
