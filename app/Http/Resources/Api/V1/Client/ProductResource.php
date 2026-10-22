<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Client;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
final class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();
        $translations = $this->resource->loadMissing('translations')->translations;
        $translation = $translations->firstWhere('locale', $locale) ?? $translations->first();

        $imageEn = $this->getFirstMediaUrl('image_en') ?: null;
        $imageAr = $this->getFirstMediaUrl('image_ar') ?: null;

        return [
            'id' => $this->id,
            'name' => $translation?->name ?? '',
            'description' => $translation?->description ?? '',
            'seo_title' => $translation?->seo_title ?? '',
            'seo_description' => $translation?->seo_description ?? '',
            'price' => (float) $this->price,
            'image' => $locale === 'ar' ? ($imageAr ?? $imageEn) : ($imageEn ?? $imageAr),
            'image_en' => $imageEn,
            'image_ar' => $imageAr,
        ];
    }
}
