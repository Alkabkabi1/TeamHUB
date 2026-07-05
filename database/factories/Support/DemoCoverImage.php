<?php

namespace Database\Factories\Support;

/**
 * Generates attractive, deterministic placeholder cover images for demo
 * seeding. Each image is a diagonal gradient tinted with a base brand color
 * (a club's theme, when given) plus a few translucent geometric accents whose
 * size and position are derived from a seed string — so two events of the same
 * club look related yet distinct, and re-seeding always produces the same art.
 *
 * Intentionally renders NO text: GD cannot shape Arabic script (it would emit
 * disconnected, reversed glyphs), so a clean branded gradient reads far better
 * than broken typography on a card.
 */
class DemoCoverImage
{
    /**
     * The default brand gold, used when a model has no theme color.
     */
    private const DEFAULT_COLOR = '#c8924a';

    /**
     * Generate a JPEG cover image and return its raw bytes.
     *
     * @param  string  $seed  Stable string (e.g. "event-12") that drives the layout.
     * @param  string|null  $hexColor  Base color, typically the club's theme. Falls back to brand gold.
     */
    public static function generate(string $seed, ?string $hexColor = null, int $width = 1200, int $height = 800): string
    {
        $base = self::hexToRgb($hexColor ?: self::DEFAULT_COLOR);
        $hash = crc32($seed);

        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, true);

        self::paintGradient($image, $width, $height, $base, $hash);
        self::paintAccents($image, $width, $height, $base, $hash);

        ob_start();
        imagejpeg($image, null, 88);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    /**
     * Fill the canvas with a diagonal two-stop gradient: a lighter tint of the
     * base color in the top corner blending into a darker shade in the
     * opposite corner. The blend direction flips based on the seed for variety.
     *
     * @param  \GdImage  $image
     * @param  array{0: int, 1: int, 2: int}  $base
     */
    private static function paintGradient($image, int $width, int $height, array $base, int $hash): void
    {
        $light = self::adjustBrightness($base, 0.35);
        $dark = self::adjustBrightness($base, -0.45);
        $flip = ($hash & 1) === 1;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $ratio = (($flip ? $width - $x : $x) / $width + $y / $height) / 2;

                $color = imagecolorallocate(
                    $image,
                    (int) round($light[0] + ($dark[0] - $light[0]) * $ratio),
                    (int) round($light[1] + ($dark[1] - $light[1]) * $ratio),
                    (int) round($light[2] + ($dark[2] - $light[2]) * $ratio),
                );

                imagesetpixel($image, $x, $y, $color);
            }
        }
    }

    /**
     * Lay translucent circles over the gradient for depth. Count, position and
     * radius are all derived from the seed hash so the composition is stable
     * yet varies between images.
     *
     * @param  \GdImage  $image
     * @param  array{0: int, 1: int, 2: int}  $base
     */
    private static function paintAccents($image, int $width, int $height, array $base, int $hash): void
    {
        $light = self::adjustBrightness($base, 0.55);
        $count = 3 + ($hash % 3);

        for ($i = 0; $i < $count; $i++) {
            $h = $hash >> ($i * 4);

            $cx = (int) ($width * ((($h & 0xFF) / 255) * 1.2 - 0.1));
            $cy = (int) ($height * (((($h >> 8) & 0xFF) / 255) * 1.2 - 0.1));
            $diameter = (int) ($width * (0.25 + (($h >> 16) & 0xFF) / 255 * 0.5));

            // 8–22% opacity translucent fill (alpha 127 = fully transparent).
            $alpha = 100 + ($i * 7) % 20;
            $color = imagecolorallocatealpha($image, $light[0], $light[1], $light[2], $alpha);

            imagefilledellipse($image, $cx, $cy, $diameter, $diameter, $color);
        }
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Lighten ($factor > 0) or darken ($factor < 0) an RGB triplet, clamped to
     * the 0–255 range.
     *
     * @param  array{0: int, 1: int, 2: int}  $rgb
     * @return array{0: int, 1: int, 2: int}
     */
    private static function adjustBrightness(array $rgb, float $factor): array
    {
        return array_map(function (int $channel) use ($factor): int {
            $target = $factor >= 0 ? 255 : 0;
            $value = $channel + ($target - $channel) * abs($factor);

            return (int) max(0, min(255, round($value)));
        }, $rgb);
    }
}
