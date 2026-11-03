<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemuJanji extends Model
{
    private const string DetailsPrefix = 'booking-details:v1:';

    protected $table = 'temu_janji';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['status'];

    /** @return array{catatan: ?string, jenis_barang_disimpan?: string} */
    public static function detailsForStorage(string $goods, ?string $notes): array
    {
        $model = new static;
        if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'jenis_barang_disimpan')) {
            return ['catatan' => $notes, 'jenis_barang_disimpan' => $goods];
        }

        return ['catatan' => self::DetailsPrefix.json_encode([
            'jenis_barang_disimpan' => $goods,
            'catatan' => $notes,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)];
    }

    protected function jenisBarangDisimpan(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes): ?string => $value
                ?? self::legacyDetails($attributes['catatan'] ?? null)['jenis_barang_disimpan'] ?? null,
        );
    }

    protected function catatan(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                $details = self::legacyDetails($value);

                return $details === null ? $value : $details['catatan'];
            },
        );
    }

    /** @return array{jenis_barang_disimpan: string, catatan: ?string}|null */
    private static function legacyDetails(?string $value): ?array
    {
        if ($value === null || ! str_starts_with($value, self::DetailsPrefix)) {
            return null;
        }

        $details = json_decode(substr($value, strlen(self::DetailsPrefix)), true);
        if (! is_array($details) || array_keys($details) !== ['jenis_barang_disimpan', 'catatan']
            || ! is_string($details['jenis_barang_disimpan'])
            || (! is_null($details['catatan']) && ! is_string($details['catatan']))) {
            return null;
        }

        return $details;
    }

    protected function casts(): array
    {
        return ['tanggal_kunjungan' => 'date'];
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }
}
