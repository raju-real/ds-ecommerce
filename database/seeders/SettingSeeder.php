<?php

namespace Database\Seeders;

use App\Repositories\SettingRepository;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run()
    {
        app(SettingRepository::class)->save([
            'site_name' => 'DS Shop',
            'tagline' => 'Quality products, delivered to your door',
            'topbar_text' => 'Cash on delivery all over Bangladesh 🇧🇩',
            'footer_about' => 'We bring you hand-picked quality products at the best price, with cash on delivery anywhere in Bangladesh.',
            'copyright_text' => '© '.date('Y').' DS Shop. All rights reserved.',
            'default_delivery_charge' => 130,
            'phone' => '01700000000',
            'whatsapp' => '01700000000',
            'email' => 'support@example.com',
            'address' => 'House 1, Road 1, Dhanmondi, Dhaka 1205',
            'facebook' => 'https://facebook.com',
            'meta_title' => 'DS Shop — Online Shopping in Bangladesh',
            'meta_description' => 'Shop quality gadgets, fashion and home products online with cash on delivery all over Bangladesh.',
            'meta_keywords' => 'online shopping bangladesh, cash on delivery, gadgets, fashion',
            'sms_driver' => 'log',
        ]);
    }
}
