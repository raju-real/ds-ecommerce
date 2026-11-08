<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Public record identifiers such as "aeoitoudop": 10 lowercase letters,
 * used in every admin URL instead of the auto-increment id.
 */
class UniqueId
{
    public const LENGTH = 10;

    public const PATTERN = '[a-z]{10}';

    public static function make(): string
    {
        $id = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $id .= chr(random_int(97, 122));
        }

        return $id;
    }

    /** A value not used by any row of $table, soft-deleted rows included. */
    public static function for(string $table): string
    {
        do {
            $id = self::make();
        } while (DB::table($table)->where('unique_id', $id)->exists());

        return $id;
    }
}
