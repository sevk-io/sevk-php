<?php

declare(strict_types=1);

namespace Sevk\Markup;

final class PixelValue
{
    public static function parse(string $value): int
    {
        return preg_match('/^\s*[+-]?\d+/', $value, $match) === 1 ? (int) $match[0] : 0;
    }
}
