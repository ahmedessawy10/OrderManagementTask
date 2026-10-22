<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class Product extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'price',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslation::class);
    }

    /**
     * Name in the current locale, falling back to the first translation.
     */
    public function translatedName(): string
    {
        $translation = $this->translations->firstWhere('locale', app()->getLocale()) ?? $this->translations->first();

        return $translation->name ?? '';
    }
}
