<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemuJanji extends Model
{
    protected $table = 'temu_janji';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['status'];

    protected function casts(): array
    {
        return ['tanggal_kunjungan' => 'date'];
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }
}
