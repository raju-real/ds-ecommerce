<?php

namespace App\Repositories;

use App\Models\Setting;
use App\Support\ModelCache;

class SettingRepository
{
    protected ?array $settings = null;

    public function all(): array
    {
        return $this->settings ??= ModelCache::remember(ModelCache::SETTINGS, 'all',
            fn () => Setting::pluck('value', 'key')->all()
        );
    }

    public function get(string $key, $default = null)
    {
        $value = $this->all()[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    public function save(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->settings = null;
    }
}
