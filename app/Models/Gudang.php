<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Gudang extends Model
{
    public const array WarehouseTypes = ['Versa', 'Heksa', 'MAXI 24', 'VERTA 56'];

    public const array ManualStatuses = ['tersedia', 'tidak tersedia', 'digunakan'];

    protected $table = 'gudang';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = [
        'foto' => 'array',
        'fasilitas' => 'array',
        'kavling_l' => 'float',
        'kavling_p' => 'float',
        'luas_kavling' => 'float',
        'lt1_l' => 'float',
        'lt1_p' => 'float',
        'luas_lt1' => 'float',
        'lt2_l' => 'float',
        'lt2_p' => 'float',
        'extra_m2' => 'float',
        'luas_lt2' => 'float',
        'pos_l' => 'float',
        'pos_p' => 'float',
        'luas_pos' => 'float',
        'tinggi' => 'float',
        'total_luas_bangunan' => 'float',
        'grand_total' => 'float',
        'harga_sewa' => 'float',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function temuJanji(): HasMany
    {
        return $this->hasMany(TemuJanji::class, 'gudang_id');
    }

    public function hasApprovedBookings(): bool
    {
        return $this->temuJanji()->where('status', 'approved')->exists();
    }

    public function photoUrl(string $photo): string
    {
        if (filter_var($photo, FILTER_VALIDATE_URL)) {
            return $photo;
        }

        return Storage::disk('public')->url($photo);
    }
}
