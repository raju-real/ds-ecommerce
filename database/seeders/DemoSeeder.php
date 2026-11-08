<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Slider;
use App\Models\Vendor;
use App\Services\ImageService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Sample catalogue with generated images: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(ImageService $images)
    {
        if (Product::exists()) {
            $this->command?->warn('Products already exist — demo seeding skipped.');

            return;
        }

        $vendors = collect([
            ['name' => 'Gadget Hub BD', 'contact_person' => 'Rakib Hasan', 'phone' => '01811000000', 'address' => 'Elephant Road, Dhaka'],
            ['name' => 'Style Point Wholesale', 'contact_person' => 'Nusrat Jahan', 'phone' => '01911000000', 'address' => 'Chawkbazar, Chattogram'],
            ['name' => 'Home Essentials Ltd', 'contact_person' => 'Tanvir Ahmed', 'phone' => '01611000000', 'address' => 'Mirpur 10, Dhaka'],
        ])->map(fn ($v) => Vendor::create($v + ['is_active' => true]));

        $brands = collect([
            ['Nova Tech', [79, 70, 229]], ['Zentro', [14, 165, 233]], ['Urban Edge', [236, 72, 153]],
            ['HomeCraft', [16, 185, 129]], ['Glowify', [168, 85, 247]],
        ])->map(fn ($b, $i) => Brand::create([
            'name' => $b[0], 'slug' => Str::slug($b[0]), 'sort_order' => $i, 'is_active' => true,
            'logo' => $images->placeholder($b[0], 'brands', 300, 300, $b[1]),
        ]));
        // Brand per catalog section: electronics → Nova/Zentro, fashion → Urban Edge, home → HomeCraft, beauty → Glowify/Zentro
        $brandFor = [[0, 1], [2, 2], [3, 3], [4, 1]];

        $palette = [[79, 70, 229], [14, 165, 233], [16, 185, 129], [245, 158, 11], [239, 68, 68], [168, 85, 247], [236, 72, 153], [20, 184, 166]];

        $catalog = [
            'Electronics' => [0, ['Smart Watch', 'Earbuds', 'Power Bank'], [
                ['T800 Ultra Smart Watch', 1850, 1490, 980], ['Pro 5 Wireless Earbuds', 1450, 1150, 690],
                ['20000mAh Fast Charging Power Bank', 2200, 1790, 1180], ['Bluetooth Neckband Sports', 990, 750, 420],
                ['Mini Portable Bluetooth Speaker', 1250, null, 760], ['Fitness Band M7', 890, 690, 380],
            ]],
            'Fashion' => [1, ['Watches', 'Sunglasses', 'Bags'], [
                ['Classic Leather Strap Watch', 1650, 1290, 720], ['Polarized Aviator Sunglasses', 850, 650, 290],
                ['Premium Leather Wallet', 790, null, 360], ['Travel Backpack Waterproof', 2100, 1690, 1050],
                ['Ladies Party Clutch Bag', 1350, 990, 540], ['Stainless Steel Bracelet', 650, 490, 210],
            ]],
            'Home & Kitchen' => [2, ['Kitchen Tools', 'Lighting', 'Decor'], [
                ['Electric Hand Blender 300W', 2450, 1990, 1350], ['Rechargeable LED Desk Lamp', 1150, 890, 520],
                ['Non-stick Fry Pan 26cm', 1350, null, 820], ['Moon Night Light 3D', 950, 750, 380],
                ['Vegetable Chopper 12 in 1', 1250, 950, 560], ['Wall Clock Modern Silent', 1100, 850, 470],
            ]],
            'Beauty & Care' => [0, ['Grooming', 'Hair Care', 'Skin Care'], [
                ['Professional Hair Trimmer', 1890, 1450, 890], ['Hair Straightener Ceramic', 1650, 1290, 760],
                ['Facial Cleansing Brush', 990, 790, 410], ['Electric Nail Care Kit', 1150, null, 590],
                ['Hot Air Brush 3 in 1', 2650, 2190, 1420], ['Mini Massage Gun', 2950, 2450, 1650],
            ]],
        ];

        $c = 0;
        foreach ($catalog as $catName => [$vendorIdx, $subs, $products]) {
            $color = $palette[$c % count($palette)];
            $category = Category::create([
                'name' => $catName, 'slug' => Str::slug($catName), 'sort_order' => $c, 'is_active' => true,
                'image' => $images->placeholder($catName, 'categories', 300, 300, $color),
            ]);
            $subModels = collect($subs)->map(fn ($s, $i) => $category->subcategories()->create([
                'name' => $s, 'slug' => Str::slug($catName.' '.$s), 'sort_order' => $i, 'is_active' => true,
            ]));

            foreach ($products as $i => [$name, $price, $discount, $cost]) {
                $pColor = $palette[($c * 3 + $i) % count($palette)];
                $product = Product::create([
                    'category_id' => $category->id,
                    'subcategory_id' => $subModels[$i % $subModels->count()]->id,
                    'vendor_id' => $vendors[$vendorIdx]->id,
                    'brand_id' => $brands[$brandFor[$c][$i % 2]]->id,
                    'name' => $name,
                    'code' => 'DS-'.str_pad((string) ($c * 10 + $i + 1), 4, '0', STR_PAD_LEFT),
                    'slug' => Str::slug($name),
                    'short_description' => "High quality $name with premium finish. 7 days replacement guarantee and cash on delivery all over Bangladesh.",
                    'description' => "<h3>About this product</h3><p>The <strong>$name</strong> is built for everyday use with durable materials and a modern look.</p><ul><li>Premium build quality</li><li>Tested before dispatch</li><li>7 days replacement warranty</li><li>Cash on delivery available</li></ul><h3>Package includes</h3><p>1 × $name, user manual.</p>",
                    'unit_price' => $price,
                    'discount_price' => $discount,
                    'drop_shipping_price' => $cost,
                    'in_stock' => true,
                    'is_featured' => $i % 2 === 0,
                    'is_active' => true,
                ]);

                $label = Str::limit($name, 18, '');
                $product->update([
                    'thumbnail_large' => $images->placeholder($label, 'products/demo', 800, 800, $pColor),
                    'thumbnail' => $images->placeholder($label, 'products/demo', 400, 400, $pColor),
                ]);
                foreach ([$palette[($c * 3 + $i + 4) % count($palette)], $palette[($c * 5 + $i + 2) % count($palette)]] as $k => $imgColor) {
                    $product->images()->create([
                        'path' => $images->placeholder($label.' '.($k + 2), 'products/demo', 800, 800, $imgColor),
                        'thumb' => $images->placeholder($label.' '.($k + 2), 'products/demo', 400, 400, $imgColor),
                        'sort_order' => $k,
                    ]);
                }
            }
            $c++;
        }

        $slides = [
            ['Mega Gadget Sale', 'Up to 30% off on smart watches & earbuds', 'Shop Gadgets', 'category/electronics', [79, 70, 229]],
            ['New Season Fashion', 'Fresh arrivals for every style', 'Explore Fashion', 'category/fashion', [236, 72, 153]],
            ['Smart Home Living', 'Kitchen & lighting essentials at the best price', 'Shop Now', 'category/home-kitchen', [16, 185, 129]],
        ];
        foreach ($slides as $i => [$title, $sub, $btn, $link, $color]) {
            Slider::create([
                'title' => $title, 'subtitle' => $sub, 'button_text' => $btn, 'link' => $link, 'sort_order' => $i, 'is_active' => true,
                'image' => $images->placeholder(' ', 'sliders', 1600, 600, $color),
            ]);
        }
    }
}
