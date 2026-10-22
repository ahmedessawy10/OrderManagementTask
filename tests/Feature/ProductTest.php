<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Resources\Api\V1\Client\ProductResource;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_seeder_seeds_products_with_translations(): void
    {
        $this->seed(ProductSeeder::class);

        $this->assertDatabaseCount('products', 6);
        $this->assertDatabaseCount('product_translations', 12);

        $product = Product::with('translations')->first();

        $this->assertNotNull($product);
        $this->assertGreaterThan(0, (float) $product->price);

        $arTranslation = $product->translations->firstWhere('locale', 'ar');
        $enTranslation = $product->translations->firstWhere('locale', 'en');

        $this->assertNotNull($arTranslation);
        $this->assertNotNull($enTranslation);
        $this->assertNotEmpty($arTranslation->name);
        $this->assertNotEmpty($enTranslation->name);
    }

    public function test_product_translation_and_spatie_media_collections(): void
    {
        Storage::fake('public');

        $product = Product::create(['price' => 500.00]);
        $product->translations()->createMany([
            ['locale' => 'en', 'name' => 'Gaming Laptop'],
            ['locale' => 'ar', 'name' => 'لابتوب ألعاب'],
        ]);

        $product->addMedia(UploadedFile::fake()->image('laptop_en.jpg'))
            ->toMediaCollection('image_en');
        $product->addMedia(UploadedFile::fake()->image('laptop_ar.jpg'))
            ->toMediaCollection('image_ar');

        app()->setLocale('en');
        $data = ProductResource::make($product->fresh())->resolve();
        $this->assertEquals('Gaming Laptop', $data['name']);
        $this->assertNotNull($data['image_en']);
        $this->assertNotNull($data['image_ar']);
        $this->assertEquals($data['image_en'], $data['image']);

        app()->setLocale('ar');
        $data = ProductResource::make($product->fresh())->resolve();
        $this->assertEquals('لابتوب ألعاب', $data['name']);
        $this->assertEquals($data['image_ar'], $data['image']);
    }

    public function test_locale_configuration_uses_accept_language_header_and_user_preferred_language(): void
    {
        $user = User::factory()->create([
            'preferred_language' => 'ar',
        ]);

        $token = auth('api')->login($user);

        // When authenticated, uses user's preferred_language
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Accept-Language', 'en')
            ->getJson('/api/v1/auth/profile');

        $this->assertEquals('ar', app()->getLocale());

        // When unauthenticated, falls back to Accept-Language header
        auth('api')->logout();
        $this->withHeader('Accept-Language', 'en')
            ->getJson('/api/v1/notifications/unread-count');

        $this->assertEquals('en', app()->getLocale());
    }

    public function test_can_list_products_with_pagination(): void
    {
        $this->seed(ProductSeeder::class);

        $response = $this->getJson('/api/v1/products?per_page=2');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'seo_title',
                        'seo_description',
                        'price',
                        'image',
                        'image_en',
                        'image_ar',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        $this->assertTrue($response->json('status'));
        $this->assertCount(2, $response->json('data'));
        $this->assertEquals(6, $response->json('pagination.total'));
        $this->assertEquals(2, $response->json('pagination.per_page'));
    }

    public function test_can_filter_products_by_name(): void
    {
        $this->seed(ProductSeeder::class);

        $response = $this->getJson('/api/v1/products?filter[name]=iPhone');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('iPhone', $data[0]['name']);
    }

    public function test_can_filter_products_by_price_range(): void
    {
        $this->seed(ProductSeeder::class);

        $response = $this->getJson('/api/v1/products?filter[min_price]=200&filter[max_price]=350');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertNotEmpty($data);

        foreach ($data as $product) {
            $this->assertGreaterThanOrEqual(200, $product['price']);
            $this->assertLessThanOrEqual(350, $product['price']);
        }
    }

    public function test_can_filter_products_by_search_query(): void
    {
        $this->seed(ProductSeeder::class);

        $response = $this->getJson('/api/v1/products?filter[search]=Titanium');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    public function test_can_sort_products_by_price(): void
    {
        $this->seed(ProductSeeder::class);

        $ascResponse = $this->getJson('/api/v1/products?sort=price');
        $ascResponse->assertOk();
        $ascPrices = array_column($ascResponse->json('data'), 'price');
        $sortedAscPrices = $ascPrices;
        sort($sortedAscPrices);
        $this->assertEquals($sortedAscPrices, $ascPrices);

        $descResponse = $this->getJson('/api/v1/products?sort=-price');
        $descResponse->assertOk();
        $descPrices = array_column($descResponse->json('data'), 'price');
        $sortedDescPrices = $descPrices;
        rsort($sortedDescPrices);
        $this->assertEquals($sortedDescPrices, $descPrices);
    }

    public function test_can_sort_products_by_name(): void
    {
        $this->seed(ProductSeeder::class);

        $response = $this->getJson('/api/v1/products?sort=name');
        $response->assertOk();

        $names = array_column($response->json('data'), 'name');
        $sortedNames = $names;
        sort($sortedNames);
        $this->assertEquals($sortedNames, $names);
    }

    public function test_product_listing_returns_localized_data_based_on_accept_language_header(): void
    {
        $this->seed(ProductSeeder::class);

        $response = $this->withHeader('Accept-Language', 'ar')
            ->getJson('/api/v1/products?filter[name]=آيفون');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('آيفون', $data[0]['name']);
    }
}

