<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Client;

use App\Exceptions\ValidationException;
use App\Facades\Client\OrderFacade;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\Order\StoreOrderRequest;
use App\Http\Requests\Api\V1\Client\Order\UpdateOrderRequest;
use App\Http\Resources\Api\V1\Client\OrderResource;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;

final class OrderController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $orders = OrderFacade::index($request);

        return $this->success(
            __('messages.order.retrieved'),
            OrderResource::collection($orders->getCollection()),
            200,
            $this->getPagination($orders)
        );
    }

    public function store(StoreOrderRequest $request)
    {
        try {
            $order = OrderFacade::store($request->validated());

            return $this->success(__('messages.order.created'), OrderResource::make($order), 201);
        } catch (ValidationException $e) {
            return $this->fail($e->getMessage(), $e->status);
        }
    }

    public function show(int $id)
    {
        $order = OrderFacade::show($id);

        return $this->success(__('messages.order.shown'), OrderResource::make($order));
    }

    public function update(UpdateOrderRequest $request, int $id)
    {
        try {
            $order = OrderFacade::update($id, $request->validated());

            return $this->success(__('messages.order.updated'), OrderResource::make($order));
        } catch (ValidationException $e) {
            return $this->fail($e->getMessage(), $e->status);
        }
    }

    public function destroy(int $id)
    {
        try {
            OrderFacade::destroy($id);

            return $this->success(__('messages.order.deleted'));
        } catch (ValidationException $e) {
            return $this->fail($e->getMessage(), $e->status);
        }
    }
}
