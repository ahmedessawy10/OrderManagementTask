<?php

declare(strict_types=1);

namespace App\Facades\Client;

use App\Services\Client\AuthService;
use Illuminate\Support\Facades\Facade;

final class AuthFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AuthService::class;
    }
}
