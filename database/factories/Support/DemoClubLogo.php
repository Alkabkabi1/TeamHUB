<?php

namespace Database\Factories\Support;

/**
 * Generates a clean, logo-like club emblem: a solid brand-colored badge with
 * subtle radial depth, a thin ring accent, and a centered white Arabic
 * monogram. Reads as a real club mark/app icon rather than a plain colored
 * tile, while staying tinted to each club's theme color.
 *
 * A single isolated Arabic letter needs no contextual shaping, so it renders
 * correctly through GD's TrueType support (unlike connected multi-letter text).
 */
class DemoClubLogo
{
    private const DEFAULT_COLOR = '#006471';

    private const FONT = __DIR__.'/fonts/NotoKufiArabic-Black.ttf';

    /**
     * Generate a square emblem and return its raw JPEG bytes.
     *
     * @param  string  $monogram  A single Arabic letter to center on the badge.
     * @param  string|null  $hexColor  The club's theme color; falls back to brand teal.
     */
    public static function generate(string $monogram, ?string $hexColor = null, int $size = 600): string
    {
        $base = self::hexToRgb($hexColor ?: self::DEFAULT_COLOR);

        $image = imagecreatetruecolor($size, $size);
        imagealphablending($image, true);

        self::paintBackground($image, $size, $base);
        self::paintRing($image, $size);
        self::paintMonogram($image, $size, $monogram);

        ob_start();
        imagejpeg($image, null, 92);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    /**
     * Fill the badge with the brand color, lightened toward the center for a
     * soft spotlight that gives the flat emblem some depth.
     *
     * @param  \GdImage  $image
     * @param  array{0: int, 1: int, 2: int}  $base
     */
    private static function paintBackground($image, int $size, array $base): void
    {
        $light = self::adjustBrightness($base, 0.30);
        $dark = self::adjustBrightness($base, -0.25);
        $center = $size / 2;
        $maxDist = $center * 1.41;

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $dist = sqrt(($x - $center) ** 2 + ($y - $center) ** 2) / $maxDist;

                $color = imagecolorallocate(
                    $image,
                    (int) round($light[0] + ($dark[0] - $light[0]) * $dist),
                    (int) round($light[1] + ($dark[1] - $light[1]) * $dist),
                    (int) round($light[2] + ($dark[2] - $light[2]) * $dist),
                );

                imagesetpixel($image, $x, $y, $color);
            }
        }
    }

    /**
     * Draw a thin translucent white ring inset from the edge.
     *
     * @param  \GdImage  $image
     */
    private static function paintRing($image, int $size): void
    {
        $ring = imagecolorallocatealpha($image, 255, 255, 255, 100);
        $diameter = (int) ($size * 0.82);

        imagesetthickness($image, max(2, (int) ($size * 0.012)));
        imageellipse($image, (int) ($size / 2), (int) ($size / 2), $diameter, $diameter, $ring);
        imagesetthickness($image, 1);
    }

    /**
     * Center a large white monogram on the badge.
     *
     * @param  \GdImage  $image
     */
    private static function paintMonogram($image, int $size, string $monogram): void
    {
        $white = imagecolorallocate($image, 255, 255, 255);
        $fontSize = $size * 0.4;

        $box = imagettfbbox($fontSize, 0, self::FONT, $monogram);
        $textWidth = $box[2] - $box[0];
        $textHeight = $box[1] - $box[7];

        $x = (int) (($size - $textWidth) / 2 - $box[0]);
        $y = (int) (($size + $textHeight) / 2 - $box[1]);

        imagettftext($image, $fontSize, 0, $x, $y, $white, self::FONT, $monogram);
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
