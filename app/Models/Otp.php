<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OtpType;
use App\Enums\SmsProvider;
use Illuminate\Database\Eloquent\Model;

final class Otp extends Model
{
    protected $fillable = [
        'phone',
        'code',
        'type',
        'provider',
        'expires_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => OtpType::class,
            'provider' => SmsProvider::class,
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }
}
