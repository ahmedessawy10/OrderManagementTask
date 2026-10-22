<?php

declare(strict_types=1);

namespace App\Facades\Client;

use App\Services\Client\PaymentService;
use Illuminate\Support\Facades\Facade;

final class PaymentFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PaymentService::class;
    }
}
