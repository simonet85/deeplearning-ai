<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The design system promises its text pairs hold 4.5:1 (3:1 for control edges) in
 * both themes. This reads the real values from resources/css/tokens.css.
 */
class DesignTokensTest extends TestCase
{
    /** Text pairs: [foreground, ground]. */
    public static function textPairs(): array
    {
        $pairs = [];
        $text = ['ink', 'muted', 'scrub', 'balm', 'critical'];
        $grounds = ['surface', 'raised', 'sunk'];

        foreach (['light', 'dark'] as $theme) {
            foreach ($text as $fg) {
                foreach ($grounds as $bg) {
                    $pairs["$theme $fg on $bg"] = [$theme, $fg, $bg, 4.5];
                }
            }
            $pairs["$theme ink on scrub-soft"] = [$theme, 'ink', 'scrub-soft', 4.5];
            $pairs["$theme scrub on scrub-soft"] = [$theme, 'scrub', 'scrub-soft', 4.5];
            $pairs["$theme balm on balm-soft"] = [$theme, 'balm', 'balm-soft', 4.5];
            $pairs["$theme dose-ink on dose-soft"] = [$theme, 'dose-ink', 'dose-soft', 4.5];
            $pairs["$theme critical on critical-soft"] = [$theme, 'critical', 'critical-soft', 4.5];
            $pairs["$theme raised (button label) on scrub"] = [$theme, 'raised', 'scrub', 4.5];
            $pairs["$theme raised (button label) on critical"] = [$theme, 'raised', 'critical', 4.5];
            $pairs["$theme line-strong edge on surface"] = [$theme, 'line-strong', 'surface', 3.0];
            $pairs["$theme line-strong edge on raised"] = [$theme, 'line-strong', 'raised', 3.0];
            $pairs["$theme scrub focus ring on surface"] = [$theme, 'scrub', 'surface', 3.0];
        }

        return $pairs;
    }

    #[DataProvider('textPairs')]
    public function test_pair_meets_contrast(string $theme, string $foreground, string $ground, float $minimum): void
    {
        $tokens = self::tokens($theme);

        $this->assertGreaterThanOrEqual(
            $minimum,
            self::contrast($tokens[$foreground], $tokens[$ground]),
            "$foreground on $ground ($theme)"
        );
    }

    /** @return array<string, array{int, int, int}> */
    private static function tokens(string $theme): array
    {
        $css = file_get_contents(__DIR__.'/../../resources/css/tokens.css');
        [$light, $dark] = preg_split('/@media \(prefers-color-scheme: dark\)/', $css, 2);

        preg_match_all('/--c-([a-z-]+):\s*(\d+) (\d+) (\d+);/', $theme === 'light' ? $light : $dark, $m, PREG_SET_ORDER);

        $tokens = [];
        foreach ($m as $row) {
            $tokens[$row[1]] = [(int) $row[2], (int) $row[3], (int) $row[4]];
        }

        return $tokens;
    }

    private static function luminance(array $rgb): float
    {
        [$r, $g, $b] = array_map(function (int $c): float {
            $c /= 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, $rgb);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    private static function contrast(array $a, array $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }
}
