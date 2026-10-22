<?php

declare(strict_types=1);

namespace App\Facades\Client;

use App\Services\Client\OrderService;
use Illuminate\Support\Facades\Facade;

final class OrderFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return OrderService::class;
    }
}
