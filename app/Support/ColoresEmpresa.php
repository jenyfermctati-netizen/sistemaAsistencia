<?php

namespace App\Support;

final class ColoresEmpresa
{
    public static function variables(?string $color): array
    {
        if (! is_string($color) || ! preg_match('/^#[0-9a-f]{6}$/i', $color) || strtolower($color) === '#00a9c7') {
            return [];
        }
        [$r, $g, $b] = array_map(fn ($n) => $n / 255, sscanf($color, '#%02x%02x%02x'));
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;
        $light = ($max + $min) / 2;
        $saturation = $delta ? $delta / (1 - abs(2 * $light - 1)) : 0;
        $hue = $delta ? match ($max) {
            $r => 60 * fmod(($g - $b) / $delta, 6),
            $g => 60 * (($b - $r) / $delta + 2),
            default => 60 * (($r - $g) / $delta + 4),
        } : 0;
        $variables = [];
        foreach (config('colores_empresa', []) as $name => [$h, $s, $l]) {
            $h = fmod($h + $hue - 189 + 720, 360);
            $s *= $saturation;
            // Mantiene el contraste de textos oscuros y la suavidad de fondos claros.
            $variables[$name] = sprintf('hsl(%.2f %.2f%% %.2f%%)', $h, $s * 100, $l * 100);
        }

        return $variables;
    }
}
