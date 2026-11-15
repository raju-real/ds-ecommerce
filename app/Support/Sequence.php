<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/** Gap-free numbers from the counters table (complaints 00001, 00002 …). Call inside a DB transaction. */
class Sequence
{
    public static function next(string $name): int
    {
        $current = DB::table('counters')->where('name', $name)->lockForUpdate()->value('value');
        if ($current === null) {
            DB::table('counters')->insertOrIgnore(['name' => $name, 'value' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $current = DB::table('counters')->where('name', $name)->lockForUpdate()->value('value');
        }
        $value = (int) $current + 1;
        DB::table('counters')->where('name', $name)->update(['value' => $value, 'updated_at' => now()]);

        return $value;
    }
}
