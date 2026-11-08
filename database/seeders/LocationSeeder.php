<?php

namespace Database\Seeders;

use App\Models\District;
use App\Support\ModelCache;
use App\Support\UniqueId;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocationSeeder extends Seeder
{
    public function run()
    {
        if (District::exists()) {
            return;
        }

        $now = now();

        // Bulk insert (no model events), so bump the cache manually afterwards.
        foreach (require __DIR__.'/data/bd_locations.php' as $meta => $thanas) {
            [$name, $bn, $charge] = explode('|', $meta);
            $districtId = DB::table('districts')->insertGetId([
                'unique_id' => UniqueId::for('districts'), 'name' => $name, 'bn_name' => $bn, 'delivery_charge' => $charge,
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);

            DB::table('thanas')->insert(collect(explode(',', $thanas))->map(fn ($t) => [
                'unique_id' => UniqueId::for('thanas'), 'district_id' => $districtId, 'name' => trim($t), 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ])->all());
        }

        ModelCache::bump(ModelCache::LOCATIONS);
    }
}
