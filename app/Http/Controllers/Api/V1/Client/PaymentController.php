<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Client;

use App\Enums\PaymentStatus;
use App\Exceptions\ValidationException;
use App\Facades\Client\PaymentFacade;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\Payment\ProcessPaymentRequest;
use App\Http\Resources\Api\V1\Client\PaymentResource;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;

final class PaymentController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $payments = PaymentFacade::index($request);

        return $this->success(
            __('messages.payment.retrieved'),
            PaymentResource::collection($payments->getCollection()),
            200,
            $this->getPagination($payments)
        );
    }

    public function show(int $id)
    {
        $payment = PaymentFacade::show($id);

        return $this->success(__('messages.payment.shown'), PaymentResource::make($payment));
    }

    public function store(ProcessPaymentRequest $request, int $id)
    {
        try {
            $payment = PaymentFacade::process($id, $request->validated('payment_method'));

            return match ($payment->status) {
                PaymentStatus::Successful => $this->success(__('messages.payment.processed'), PaymentResource::make($payment), 201),
                PaymentStatus::Failed => $this->success(__('messages.payment.failed'), PaymentResource::make($payment), 201),
                default => $this->success(__('messages.payment.processing'), PaymentResource::make($payment), 202),
            };
        } catch (ValidationException $e) {
            return $this->fail($e->getMessage(), $e->status);
        }
    }
}
