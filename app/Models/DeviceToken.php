<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class DeviceToken extends Model
{
    protected $fillable = [
        'device_id',
        'device_token',
        'device',
        'tokenable_id',
        'tokenable_type',
    ];

    public function tokenable(): MorphTo
    {
        return $this->morphTo();
    }
}
