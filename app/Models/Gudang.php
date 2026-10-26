<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Table(name: 'gudang', key: 'id', keyType: 'string', incrementing: false, timestamps: false)]
class Gudang extends Model
{
    private const array GroupingAttributes = [
        'blok',
        'tipe_gudang',
        'luas_kavling',
        'total_luas_bangunan',
        'grand_total',
        'harga_sewa',
    ];

    /**
     * Get the attribute casts for the model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'foto' => 'array',
            'fasilitas' => 'array',
            'harga_sewa' => 'decimal:2',
            'luas_kavling' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'total_luas_bangunan' => 'decimal:2',
        ];
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
            if (! is_string($photo) || ! filter_var($photo, FILTER_VALIDATE_URL)) {
                continue;
            }

            if (parse_url($photo, PHP_URL_SCHEME) === 'https') {
                $photoUrls[] = $photo;
            }
        }

        return $photoUrls;
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
        $attributes = [];

        foreach (self::GroupingAttributes as $attribute) {
            $attributes[$attribute] = $this->getAttribute($attribute);
        }

        return rtrim(strtr(base64_encode(json_encode($attributes, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }

    /**
     * @return array<string, string|int|float|null>|null
     */
    public static function groupingAttributesFromIdentifier(string $identifier): ?array
    {
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
