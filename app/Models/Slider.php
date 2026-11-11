<?php

namespace App\Models;

use App\Models\Concerns\FlushesModelCache;
use App\Models\Concerns\HasSortOrder;
use App\Models\Concerns\HasUniqueId;
use App\Support\ModelCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Slider extends Model
{
    use FlushesModelCache, HasSortOrder, HasUniqueId, SoftDeletes;

    protected static array $cacheGroups = [ModelCache::SLIDERS];

    protected $fillable = ['title', 'subtitle', 'button_text', 'link', 'image', 'sort_order', 'is_active',
        'title_color', 'subtitle_color', 'button_color', 'button_text_color', 'text_shade', 'show_text', 'button_position',
        'show_title', 'show_subtitle', 'show_button'];

    public const BUTTON_POSITIONS = [
        'below_text' => 'Right below the text',
        'bottom_left' => 'Bottom left',
        'bottom_center' => 'Bottom center',
        'bottom_right' => 'Bottom right',
    ];

    /** Button position, bottom_left when not set (or before the migration). */
    public function buttonPosition(): string
    {
        return array_key_exists((string) $this->button_position, self::BUTTON_POSITIONS) ? $this->button_position : 'bottom_left';
    }

    /**
     * Is this part switched on for the banner? $part: title | subtitle | button.
     * Falls back to the older single "show text" switch, then to on, until the migrations have run.
     */
    public function shows(string $part): bool
    {
        $value = $this->getAttribute("show_$part");
        if ($value === null) {
            return $this->show_text === null || (bool) $this->show_text;
        }

        return (bool) $value;
    }

    /** What the banner really shows: switched on AND filled in (the button also needs a link). */
    public function visibleParts(): array
    {
        return [
            'title' => $this->shows('title') && filled($this->title),
            'subtitle' => $this->shows('subtitle') && filled($this->subtitle),
            'button' => $this->shows('button') && filled($this->button_text) && filled($this->link),
        ];
    }

    /** Any text (title, subtitle or button) on the banner? */
    public function showsText(): bool
    {
        return in_array(true, $this->visibleParts(), true);
    }

    /** Parts switched off, for the admin list ("Hidden: title, button"). */
    public function hiddenParts(): array
    {
        return array_values(array_filter(['title', 'subtitle', 'button'], fn ($p) => ! $this->shows($p)));
    }

    public const SHADES = ['none' => 'None', 'dark' => 'Dark shade (light text)', 'light' => 'Light shade (dark text)'];

    /** Inline style for a banner text part, e.g. "color:#ffffff" — empty when the default is used. */
    public function colorStyle(string $part): string
    {
        return match ($part) {
            'title' => $this->title_color ? "color:{$this->title_color}" : '',
            'subtitle' => $this->subtitle_color ? "color:{$this->subtitle_color}" : '',
            'button' => implode(';', array_filter([
                $this->button_color ? "background-color:{$this->button_color};border-color:{$this->button_color}" : null,
                $this->button_text_color ? "color:{$this->button_text_color}" : null,
            ])),
            default => '',
        };
    }

    protected $casts = ['is_active' => 'boolean'];
}
