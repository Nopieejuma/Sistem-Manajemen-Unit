<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Gudang extends Model
{
    public const array WarehouseTypes = ['Versa', 'Heksa', 'MAXI 24', 'VERTA 56'];

    public const array ManualStatuses = ['tersedia', 'tidak tersedia', 'digunakan'];

    private const array GroupingAttributes = [
        'blok',
        'tipe_gudang',
        'luas_kavling',
        'total_luas_bangunan',
        'grand_total',
        'harga_sewa',
    ];

    protected $table = 'gudang';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'foto' => 'array',
            'fasilitas' => 'array',
            'kavling_l' => 'decimal:2',
            'kavling_p' => 'decimal:2',
            'luas_kavling' => 'decimal:2',
            'lt1_l' => 'decimal:2',
            'lt1_p' => 'decimal:2',
            'luas_lt1' => 'decimal:2',
            'lt2_l' => 'decimal:2',
            'lt2_p' => 'decimal:2',
            'extra_m2' => 'decimal:2',
            'luas_lt2' => 'decimal:2',
            'pos_l' => 'decimal:2',
            'pos_p' => 'decimal:2',
            'luas_pos' => 'decimal:2',
            'tinggi' => 'decimal:2',
            'total_luas_bangunan' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'harga_sewa' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Gudang $gudang): void {
            if (empty($gudang->getKey())) {
                $gudang->setAttribute($gudang->getKeyName(), (string) Str::uuid());
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

    public function statusLabel(): string
    {
        return match ($this->status) {
            'tersedia' => 'Tersedia',
            'sedang di-keep' => 'Sedang Di-keep',
            'tidak tersedia', 'digunakan' => 'Tidak Tersedia',
            default => $this->status ? Str::headline($this->status) : 'Status belum tersedia',
        };
    }

    public function firstPhotoUrl(): ?string
    {
        return $this->photoUrls()[0] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public function photoUrls(): array
    {
        $photoUrls = [];

        foreach ($this->foto ?? [] as $photo) {
            if (! is_string($photo)) {
                continue;
            }

            $isRelativePath = preg_match('~^gudang/[a-zA-Z0-9_./-]+$~D', $photo) === 1
                && ! in_array('..', explode('/', $photo), true);

            if (! $isRelativePath && ! filter_var($photo, FILTER_VALIDATE_URL)) {
                continue;
            }

            $url = $this->photoUrl($photo);

            if (filter_var($url, FILTER_VALIDATE_URL)
                && (parse_url($url, PHP_URL_SCHEME) === 'https'
                    || ($isRelativePath && app()->environment('local', 'testing') && parse_url($url, PHP_URL_SCHEME) === 'http'))) {
                $photoUrls[] = $url;
            }
        }

        return $photoUrls;
    }

    public function photoUrl(string $photo): string
    {
        if (filter_var($photo, FILTER_VALIDATE_URL)) {
            return $photo;
        }

        return Storage::disk('public')->url($photo);
    }

    public function groupKey(): string
    {
        return implode('|', array_map(
            fn (string $attribute): string => (string) $this->getAttribute($attribute),
            self::GroupingAttributes,
        ));
    }

    public function groupIdentifier(): string
    {
        return (string) $this->getKey();
    }

    /**
     * @return array<string, string|int|float|null>|null
     */
    public static function groupingAttributesFromIdentifier(string $identifier): ?array
    {
        if (Str::isUuid($identifier)) {
            $warehouse = static::query()->find($identifier);

            return $warehouse?->only(self::GroupingAttributes);
        }

        $encoded = strtr($identifier, '-_', '+/');
        $encoded .= str_repeat('=', (4 - strlen($encoded) % 4) % 4);
        $decoded = base64_decode($encoded, true);

        if ($decoded === false) {
            return null;
        }

        try {
            $attributes = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($attributes) || array_keys($attributes) !== self::GroupingAttributes) {
            return null;
        }

        foreach ($attributes as $attribute => $value) {
            if ($value === null) {
                continue;
            }

            if (in_array($attribute, ['blok', 'tipe_gudang'], true)) {
                if (! is_string($value)) {
                    return null;
                }
            } elseif ((! is_string($value) && ! is_int($value) && ! is_float($value)) || ! is_numeric($value) || ! is_finite((float) $value)) {
                return null;
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, string|int|float|null>  $attributes
     */
    public function scopeSameGroup(Builder $query, array $attributes): Builder
    {
        foreach ($attributes as $attribute => $value) {
            if ($value === null) {
                $query->whereNull($attribute);
            } elseif ($attribute === 'tipe_gudang') {
                $query->whereRaw('CAST(tipe_gudang AS TEXT) = ?', [$value]);
            } else {
                $query->where($attribute, $value);
            }
        }

        return $query;
    }
}
