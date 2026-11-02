<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehousePhotoUpload extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'size' => 'integer'];
    }
}
