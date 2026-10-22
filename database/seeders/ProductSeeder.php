<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductTranslation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'price' => 399.99,
                'image_en' => 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=800&auto=format&fit=crop&q=80',
                'image_ar' => 'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=800&auto=format&fit=crop&q=80',
                'translations' => [
                    'en' => [
                        'name' => 'iPhone 16 Pro Max 256GB',
                        'description' => 'Titanium design with larger 6.9-inch Super Retina XDR display, Camera Control, 4K 120 fps Dolby Vision, and A18 Pro chip.',
                        'seo_title' => 'Buy iPhone 16 Pro Max 256GB - Best Price',
                        'seo_description' => 'Shop the new iPhone 16 Pro Max featuring the powerful A18 Pro chip, titanium design, and revolutionary camera system.',
                    ],
                    'ar' => [
                        'name' => 'آيفون 16 برو ماكس 256 جيجابايت',
                        'description' => 'تصميم من التيتانيوم مع شاشة ريتينا XDR بمقاس 6.9 إنش، زر التحكم في الكاميرا، تصوير 4K بتردد 120 إطار في الثانية، ومعالج A18 برو.',
                        'seo_title' => 'شراء آيفون 16 برو ماكس 256 جيجابايت بأفضل سعر',
                        'seo_description' => 'تسوق هاتف آيفون 16 برو ماكس الجديد المزود بمعالج A18 برو القوي وتصميم التيتانيوم الفاخر.',
                    ],
                ],
            ],
            [
                'price' => 249.50,
                'image_en' => 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=800&auto=format&fit=crop&q=80',
                'image_ar' => 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=800&auto=format&fit=crop&q=80',
                'translations' => [
                    'en' => [
                        'name' => 'PlayStation 5 Pro Console',
                        'description' => 'Experience next-level gaming with advanced ray tracing, super sharp image clarity with PSSR, and high frame rate gameplay up to 120fps.',
                        'seo_title' => 'PlayStation 5 Pro Gaming Console',
                        'seo_description' => 'Discover the PlayStation 5 Pro console with enhanced GPU, 2TB SSD, and ultra-smooth next-gen performance.',
                    ],
                    'ar' => [
                        'name' => 'جهاز بلايستيشن 5 برو',
                        'description' => 'عش تجربة الألعاب من الجيل التالي مع تتبع الأشعة المتقدم، وتقنية PSSR للوضوح الفائق، ومعدل إطارات يصل إلى 120 إطاراً في الثانية.',
                        'seo_title' => 'جهاز ألعاب بلايستيشن 5 برو الأصلي',
                        'seo_description' => 'اكتشف جهاز بلايستيشن 5 برو مع وحدة معالجة رسومات مطورة ومساحة تخزين 2 تيرابايت للألعاب فائقة الجودة.',
                    ],
                ],
            ],
            [
                'price' => 119.00,
                'image_en' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800&auto=format&fit=crop&q=80',
                'image_ar' => 'https://images.unsplash.com/photo-1484704849700-f032a568e944?w=800&auto=format&fit=crop&q=80',
                'translations' => [
                    'en' => [
                        'name' => 'Sony WH-1000XM5 Wireless Headphones',
                        'description' => 'Industry-leading noise cancelling with two processors and 8 microphones for unprecedented sound and call quality. 30-hour battery life.',
                        'seo_title' => 'Sony WH-1000XM5 Noise Cancelling Headphones',
                        'seo_description' => 'Premium over-ear noise cancelling wireless headphones by Sony with Hi-Res Audio and crystal clear hands-free calling.',
                    ],
                    'ar' => [
                        'name' => 'سماعات سوني WH-1000XM5 اللاسلكية',
                        'description' => 'سماعات رأس رائدة في إلغاء الضوضاء مزودة بمعالجين و8 ميكروفونات لصوت فائق النقاء وجودة مكالمات استثنائية وعمر بطارية يدوم 30 ساعة.',
                        'seo_title' => 'سماعات سوني WH-1000XM5 عازلة للضوضاء',
                        'seo_description' => 'سماعات رأس لاسلكية متميزة تدعم الصوت عالي الدقة مع خاصية إلغاء الضوضاء الذكية من سوني.',
                    ],
                ],
            ],
            [
                'price' => 749.00,
                'image_en' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800&auto=format&fit=crop&q=80',
                'image_ar' => 'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=800&auto=format&fit=crop&q=80',
                'translations' => [
                    'en' => [
                        'name' => 'MacBook Pro 16-inch M3 Max',
                        'description' => 'Incredible performance with 16-core CPU, 40-core GPU, Liquid Retina XDR display, up to 22 hours of battery life, and pro connectivity.',
                        'seo_title' => 'Apple MacBook Pro 16 M3 Max',
                        'seo_description' => 'The ultimate pro laptop with M3 Max chip, Liquid Retina XDR display, and unmatched speed for professionals.',
                    ],
                    'ar' => [
                        'name' => 'ماك بوك برو 16 إنش بمعالج M3 ماكس',
                        'description' => 'أداء مذهل مع معالج 16 نواة، ومعالج رسومات 40 نواة، وشاشة ليكويد ريتينا XDR وبطارية تدوم حتى 22 ساعة مع منافذ متكاملة.',
                        'seo_title' => 'أبل ماك بوك برو 16 إنش معالج M3 ماكس',
                        'seo_description' => 'أقوى لابتوب للمحترفين مع شريحة M3 ماكس وشاشة ريتينا الاحترافية لتنفيذ أصعب المهام البرمجية والتصميمية.',
                    ],
                ],
            ],
            [
                'price' => 320.00,
                'image_en' => 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=800&auto=format&fit=crop&q=80',
                'image_ar' => 'https://images.unsplash.com/photo-1580910051074-3eb694886505?w=800&auto=format&fit=crop&q=80',
                'translations' => [
                    'en' => [
                        'name' => 'Samsung Galaxy S24 Ultra 512GB',
                        'description' => 'Meet Galaxy S24 Ultra with Galaxy AI, durable titanium frame, 200MP camera, built-in S Pen, and stunning flat AMOLED display.',
                        'seo_title' => 'Samsung Galaxy S24 Ultra 512GB 5G',
                        'seo_description' => 'Experience Galaxy AI on the Samsung Galaxy S24 Ultra featuring quad telephoto cameras and bright anti-reflective display.',
                    ],
                    'ar' => [
                        'name' => 'سامسونج جالاكسي إس 24 ألترا 512 جيجابايت',
                        'description' => 'استمتع بالذكاء الاصطناعي مع جالاكسي S24 ألترا، هيكل متين من التيتانيوم، كاميرا 200 ميجابكسل، قلم S Pen مدمج، وشاشة AMOLED مسطحة رائعة.',
                        'seo_title' => 'سامسونج جالاكسي S24 ألترا الجيل الخامس 512 جيجا',
                        'seo_description' => 'شراء هاتف سامسونج جالاكسي S24 ألترا المزود بتقنيات الذكاء الاصطناعي والكاميرا الخارقة بأفضل الأسعار.',
                    ],
                ],
            ],
            [
                'price' => 185.00,
                'image_en' => 'https://images.unsplash.com/photo-1517668808822-9ebb02ae2a0e?w=800&auto=format&fit=crop&q=80',
                'image_ar' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=800&auto=format&fit=crop&q=80',
                'translations' => [
                    'en' => [
                        'name' => 'Breville Barista Touch Espresso Machine',
                        'description' => 'Automated touch screen operation simplifies espresso making with integrated conical burr grinder and automatic microfoam milk texturing.',
                        'seo_title' => 'Breville Barista Touch Espresso Coffee Maker',
                        'seo_description' => 'Brew third wave specialty coffee at home with intuitive touchscreen display and instant thermo-jet heating system.',
                    ],
                    'ar' => [
                        'name' => 'ماكينة قهوة إسبريسو بريفيل باريستا تاتش',
                        'description' => 'شاشة لمس ذكية تجعل إعداد الإسبريسو غاية في السهولة مع مطحنة حبوب مدمجة ونظام تبخير حليب أوتوماتيكي فائق النعومة.',
                        'seo_title' => 'ماكينة قهوة بريفيل باريستا تاتش المنزلية',
                        'seo_description' => 'حضّر قهوة مختصة بجودة المقاهي في منزلك مع ماكينة بريفيل باريستا تاتش الإيطالية الفاخرة.',
                    ],
                ],
            ],
        ];

        DB::transaction(function () use ($products) {
            foreach ($products as $productData) {
                $translations = $productData['translations'];
                $imageEn = $productData['image_en'];
                $imageAr = $productData['image_ar'];

                $product = Product::create([
                    'price' => $productData['price'],
                ]);

                foreach ($translations as $locale => $translation) {
                    ProductTranslation::create([
                        'product_id' => $product->id,
                        'locale' => $locale,
                        'name' => $translation['name'],
                        'description' => $translation['description'],
                        'seo_title' => $translation['seo_title'],
                        'seo_description' => $translation['seo_description'],
                    ]);
                }

                if (! empty($imageEn)) {
                    try {
                        $product->addMediaFromUrl($imageEn)->toMediaCollection('image_en');
                    } catch (Throwable) {
                        // Ignore network failures gracefully
                    }
                }

                if (! empty($imageAr)) {
                    try {
                        $product->addMediaFromUrl($imageAr)->toMediaCollection('image_ar');
                    } catch (Throwable) {
                        // Ignore network failures gracefully
                    }
                }
            }
        });
    }
}
