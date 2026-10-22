<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ValidationException;
use App\Facades\Client\PaymentFacade;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Client\PaymentResource;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;

final class WebhookController extends Controller
{
    use ApiResponse;

    public function handle(Request $request, string $gateway)
    {
        try {
            $payment = PaymentFacade::webhook($gateway, $request->getContent());

            return $this->success(__('messages.webhook.received'), PaymentResource::make($payment));
        } catch (ValidationException $e) {
            return $this->fail($e->getMessage(), $e->status);
        }
    }
}
