<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Client\Order\Concerns;

/**
 * Rules and translated messages shared by StoreOrderRequest and UpdateOrderRequest.
 */
trait OrderValidationRules
{
    /**
     * @return array<string, list<mixed>>
     */
    protected function itemRules(): array
    {
        return [
            // السعر والاسم مابيجوش من العميل، بنجيبهم من جدول المنتجات
            'items.*' => ['required', 'array:product_id,quantity'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function orderMessages(): array
    {
        return [
            'items.required' => __('validation.custom.items.required'),
            'items.array' => __('validation.custom.items.array'),
            'items.min' => __('validation.custom.items.min'),
            'items.max' => __('validation.custom.items.max'),
            'items.*.required' => __('validation.custom.items.item.required'),
            'items.*.array' => __('validation.custom.items.item.array'),
            'items.*.product_id.required' => __('validation.custom.items.product_id.required'),
            'items.*.product_id.integer' => __('validation.custom.items.product_id.integer'),
            'items.*.product_id.distinct' => __('validation.custom.items.product_id.distinct'),
            'items.*.product_id.exists' => __('validation.custom.items.product_id.exists'),
            'items.*.quantity.required' => __('validation.custom.items.quantity.required'),
            'items.*.quantity.integer' => __('validation.custom.items.quantity.integer'),
            'items.*.quantity.min' => __('validation.custom.items.quantity.min'),
            'items.*.quantity.max' => __('validation.custom.items.quantity.max'),
        ];
    }
}
