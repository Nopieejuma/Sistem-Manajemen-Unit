<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jadwal extends Model
{
    protected $table = 'jadwal';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'jam_mulai',
        'jam_selesai',
        'kuota',
        'status_aktif',
    ];

    protected function casts(): array
    {
        return [
            'kuota' => 'integer',
            'status_aktif' => 'boolean',
        ];
    }
}
