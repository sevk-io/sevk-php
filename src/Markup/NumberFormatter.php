<?php

declare(strict_types=1);

namespace Sevk\Markup;

final class NumberFormatter
{
    public static function format(float $value): string
    {
        if ($value === 0.0) {
            return '0';
        }
        if (is_nan($value)) {
            return 'NaN';
        }
        if (is_infinite($value)) {
            return $value > 0 ? 'Infinity' : '-Infinity';
        }

        [$digits, $point] = self::decimalDigits(abs($value));
        $count = strlen($digits);

        if ($count <= $point && $point <= 21) {
            $text = $digits . str_repeat('0', $point - $count);
        } elseif ($point > 0 && $point <= 21) {
            $text = substr($digits, 0, $point) . '.' . substr($digits, $point);
        } elseif ($point > -6 && $point <= 0) {
            $text = '0.' . str_repeat('0', -$point) . $digits;
        } else {
            $power = $point - 1;
            $mantissa = $count === 1 ? $digits : $digits[0] . '.' . substr($digits, 1);
            $text = $mantissa . 'e' . ($power < 0 ? '-' : '+') . abs($power);
        }

        return $value < 0 ? '-' . $text : $text;
    }

    /**
     * @return array{0: string, 1: int}
     */
    private static function decimalDigits(float $magnitude): array
    {
        $encoded = strtolower(json_encode($magnitude, JSON_THROW_ON_ERROR));
        [$mantissa, $exponent] = array_pad(explode('e', $encoded, 2), 2, '0');
        [$integerPart, $fractionPart] = array_pad(explode('.', $mantissa, 2), 2, '');
        $digits = $integerPart . rtrim($fractionPart, '0');
        $trimmed = ltrim($digits, '0');
        $leadingZeros = strlen($digits) - strlen($trimmed);

        return [$trimmed, strlen($integerPart) + (int) $exponent - $leadingZeros];
    }
}
