<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Base data:  php artisan db:seed
     * Demo store: php artisan db:seed --class=DemoSeeder
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            AdminSeeder::class,
            SettingSeeder::class,
            LocationSeeder::class,
        ]);
    }
}
