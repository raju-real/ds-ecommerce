<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Resizes uploads with GD and stores them as WebP under public/uploads.
 *
 * Modes:
 *  - contain: fit inside WxH on a white canvas (product grids stay aligned)
 *  - cover:   fill WxH and crop the overflow (banners, category tiles)
 *  - max:     shrink to fit WxH keeping aspect & transparency (logos)
 */
class ImageService
{
    public function storeProduct(UploadedFile $file): array
    {
        $sizes = config('shop.images.product');
        $base = 'products/'.date('Y/m').'/'.Str::random(20);
        $src = $this->load($file->getRealPath());

        $path = $this->write($this->resize($src, $sizes['large'][0], $sizes['large'][1], 'contain'), $base.'.webp');
        $thumb = $this->write($this->resize($src, $sizes['thumb'][0], $sizes['thumb'][1], 'contain'), $base.'-thumb.webp');
        imagedestroy($src);

        return compact('path', 'thumb');
    }

    public function store(UploadedFile $file, string $folder, array $size, string $mode = 'cover', string $format = 'webp'): string
    {
        $src = $this->load($file->getRealPath());
        $path = $this->write($this->resize($src, $size[0], $size[1], $mode), $folder.'/'.Str::random(20).'.'.$format);
        imagedestroy($src);

        return $path;
    }

    public function delete(?string ...$paths): void
    {
        foreach (array_filter($paths) as $path) {
            File::delete(public_path('uploads/'.$path));
        }
    }

    /** Solid gradient image with a label — used by the demo seeder. */
    public function placeholder(string $label, string $folder, int $w, int $h, array $rgb): string
    {
        $img = imagecreatetruecolor($w, $h);
        for ($y = 0; $y < $h; $y++) {
            $t = $y / $h;
            $c = imagecolorallocate($img, (int) ($rgb[0] * (1 - $t * .35)), (int) ($rgb[1] * (1 - $t * .35)), (int) ($rgb[2] * (1 - $t * .35)));
            imageline($img, 0, $y, $w, $y, $c);
        }

        $white = imagecolorallocate($img, 255, 255, 255);
        $font = collect(['C:/Windows/Fonts/arialbd.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'])->first(fn ($f) => is_file($f));
        if ($font) {
            $size = max(14, (int) ($w / max(10, strlen($label) * 1.1)));
            $box = imagettfbbox($size, 0, $font, $label);
            imagettftext($img, $size, 0, (int) (($w - ($box[2] - $box[0])) / 2), (int) (($h + $size) / 2), $white, $font, $label);
        } else {
            imagestring($img, 5, 10, (int) ($h / 2), $label, $white);
        }

        return $this->write($img, $folder.'/'.Str::random(20).'.webp');
    }

    protected function load(string $file)
    {
        $img = @imagecreatefromstring(file_get_contents($file));
        if (! $img) {
            throw new RuntimeException('Unsupported image file.');
        }

        // Respect phone camera orientation.
        if (function_exists('exif_read_data') && ($exif = @exif_read_data($file)) && ! empty($exif['Orientation'])) {
            $img = match ((int) $exif['Orientation']) {
                3 => imagerotate($img, 180, 0),
                6 => imagerotate($img, -90, 0),
                8 => imagerotate($img, 90, 0),
                default => $img,
            };
        }

        return $img;
    }

    protected function resize($src, int $w, int $h, string $mode)
    {
        $sw = imagesx($src);
        $sh = imagesy($src);

        if ($mode === 'max') {
            $ratio = min($w / $sw, $h / $sh, 1);
            $dw = (int) round($sw * $ratio);
            $dh = (int) round($sh * $ratio);
            $dst = $this->canvas($dw, $dh, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $dw, $dh, $sw, $sh);

            return $dst;
        }

        $dst = $this->canvas($w, $h, false);

        if ($mode === 'cover') {
            $ratio = max($w / $sw, $h / $sh);
            $cw = (int) round($w / $ratio);
            $ch = (int) round($h / $ratio);
            imagecopyresampled($dst, $src, 0, 0, (int) (($sw - $cw) / 2), (int) (($sh - $ch) / 2), $w, $h, $cw, $ch);

            return $dst;
        }

        // contain
        $ratio = min($w / $sw, $h / $sh, 1);
        $dw = (int) round($sw * $ratio);
        $dh = (int) round($sh * $ratio);
        imagecopyresampled($dst, $src, (int) (($w - $dw) / 2), (int) (($h - $dh) / 2), 0, 0, $dw, $dh, $sw, $sh);

        return $dst;
    }

    protected function canvas(int $w, int $h, bool $transparent)
    {
        $img = imagecreatetruecolor($w, $h);
        if ($transparent) {
            imagealphablending($img, false);
            imagesavealpha($img, true);
            imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
        } else {
            imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
        }

        return $img;
    }

    protected function write($img, string $relative): string
    {
        $full = public_path('uploads/'.$relative);
        File::ensureDirectoryExists(dirname($full));

        match (pathinfo($full, PATHINFO_EXTENSION)) {
            'png' => imagepng($img, $full, 8),
            'jpg', 'jpeg' => imagejpeg($img, $full, config('shop.images.quality')),
            default => imagewebp($img, $full, config('shop.images.quality')),
        };
        imagedestroy($img);

        return $relative;
    }
}
