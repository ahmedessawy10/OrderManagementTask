<?php

declare(strict_types=1);

namespace App\Services\Client;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\ValidationException;
use App\Http\Traits\Paginatable;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class OrderService
{
    use Paginatable;

    /**
     * Biggest total that fits orders.total_amount decimal(10,2), in cents.
     */
    private const MAX_TOTAL_CENTS = 9_999_999_999;

    public function index(Request $request): LengthAwarePaginator
    {
        return QueryBuilder::for(auth('api')->user()->orders(), $request)
            ->with(['items.product.translations', 'user'])
            ->allowedFilters(AllowedFilter::exact('status'))
            ->allowedSorts('id', 'created_at', 'total_amount')
            ->defaultSort('-id')
            ->paginate($this->perPage($request))
            ->appends($request->query());
    }

    public function show(int $id): Order
    {
        return auth('api')->user()->orders()
            ->with(['items.product.translations', 'user', 'payments' => fn ($query) => $query->latest('id')])
            ->findOrFail($id);
    }

    public function store(array $data): Order
    {
        $items = $this->buildOrderItems($data['items']);
        $total = $this->calculateTotal($items);

        return DB::transaction(function () use ($items, $total) {
            $order = $this->createInitialOrder($total);

            $this->createOrderItems($order, $items);

            return $order->load(['items.product.translations', 'user']);
        });
    }

    public function update(int $id, array $data): Order
    {
        return DB::transaction(function () use ($id, $data) {
            // بنقفل الأوردر عشان تغيير الحالة أو الإجمالي مايتداخلش مع طلب دفع شغال على نفس الأوردر
            $order = auth('api')->user()->orders()->lockForUpdate()->findOrFail($id);

            if (isset($data['items'])) {
                $this->validateOrderEditable($order);
                $this->replaceOrderItems($order, $data['items']);
            }

            if (isset($data['status'])) {
                $this->changeStatus($order, OrderStatus::from($data['status']));
            }

            $order->save();

            return $order->load(['items.product.translations', 'user']);
        });
    }

    public function destroy(int $id): void
    {
        $order = auth('api')->user()->orders()->findOrFail($id);

        $this->validateOrderHasNoPayments($order);

        $order->delete();
    }

    /**
     * Create the order record; the total is always calculated on the server.
     */
    private function createInitialOrder(string $total): Order
    {
        return auth('api')->user()->orders()->create([
            'status' => OrderStatus::Pending,
            'total_amount' => $total,
            'currency' => config('payment.currency'),
        ]);
    }

    /**
     * Turn the request items (product_id + quantity) into order item rows.
     */
    private function buildOrderItems(array $items): array
    {
        $prices = Product::whereIn('id', array_column($items, 'product_id'))->pluck('price', 'id');

        // السعر من جدول المنتجات مش من العميل، وبنحفظه نسخة في الأوردر وقت الطلب
        return array_map(fn (array $item) => [
            'product_id' => $item['product_id'],
            'price' => $prices[$item['product_id']],
            'quantity' => $item['quantity'],
        ], $items);
    }

    private function createOrderItems(Order $order, array $items): void
    {
        $order->items()->createMany($items);
    }

    private function replaceOrderItems(Order $order, array $items): void
    {
        $items = $this->buildOrderItems($items);

        $order->items()->delete();
        $this->createOrderItems($order, $items);

        $order->total_amount = $this->calculateTotal($items);
    }

    private function changeStatus(Order $order, OrderStatus $to): void
    {
        if ($order->status === $to) {
            return;
        }

        $this->validateStatusTransition($order, $to);

        if ($to === OrderStatus::Cancelled) {
            $this->validateOrderCanBeCancelled($order);
        }

        $order->status = $to;
    }

    // ─── Checks ───

    private function validateOrderEditable(Order $order): void
    {
        if ($order->status !== OrderStatus::Pending) {
            throw new ValidationException(__('messages.order.not_editable'), status: 409);
        }
    }

    private function validateStatusTransition(Order $order, OrderStatus $to): void
    {
        if (! $order->status->canTransitionTo($to)) {
            throw new ValidationException(__('messages.order.invalid_transition', [
                'from' => $order->status->value,
                'to' => $to->value,
            ]));
        }
    }

    private function validateOrderCanBeCancelled(Order $order): void
    {
        // أوردر مدفوع أو عليه دفعة لسه ماخلصتش مايتلغاش، وإلا ممكن الفلوس تتخصم على أوردر ملغي
        $hasMoney = $order->paid_at !== null
            || $order->payments()->whereIn('status', PaymentStatus::active())->exists();

        if ($hasMoney) {
            throw new ValidationException(__('messages.order.cannot_cancel'), status: 409);
        }
    }

    private function validateOrderHasNoPayments(Order $order): void
    {
        if ($order->payments()->exists()) {
            throw new ValidationException(__('messages.order.has_payments'), status: 409);
        }
    }

    private function calculateTotal(array $items): string
    {
        $cents = 0;

        foreach ($items as $item) {
            $cents += (int) round((float) $item['price'] * 100) * (int) $item['quantity'];
        }

        if ($cents > self::MAX_TOTAL_CENTS) {
            throw new ValidationException(__('validation.custom.items.total_max', ['max' => '99999999.99']));
        }

        return number_format($cents / 100, 2, '.', '');
    }
}
