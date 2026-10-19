<?php

declare(strict_types=1);

namespace Sevk\Markup;

final class StyleSerializer
{
    private const ORDER = [
        'color', 'background-color', 'background-image', 'background-size', 'background-position', 'background-repeat',
        'font-size', 'font-family', 'font-weight', 'line-height', 'text-align', 'text-decoration',
        'width', 'height', 'max-width', 'max-height', 'min-width', 'min-height',
        'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
        'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
        'border', 'border-color', 'border-width', 'border-style',
        'border-top', 'border-right', 'border-bottom', 'border-left',
        'border-radius', 'border-top-left-radius', 'border-top-right-radius',
        'border-bottom-left-radius', 'border-bottom-right-radius',
        'border-collapse', 'list-style-type', 'vertical-align',
    ];

    /**
     * @param array<string, string> $attributes
     * @return array<string, string>
     */
    public function extract(array $attributes): array
    {
        $style = [];

        if (($attributes['text-color'] ?? '') !== '') {
            $style['color'] = $attributes['text-color'];
        } elseif (($attributes['color'] ?? '') !== '') {
            $style['color'] = $attributes['color'];
        }
        $this->copy($attributes, $style, 'background-color');

        $backgroundImage = ($attributes['background-image'] ?? '') !== '' ? $attributes['background-image'] : null;

        if ($backgroundImage !== null) {
            $style['background-image'] = "url('{$backgroundImage}')";
        }
        if (($attributes['background-size'] ?? '') !== '') {
            $style['background-size'] = $attributes['background-size'];
        } elseif ($backgroundImage !== null) {
            $style['background-size'] = 'cover';
        }
        if (($attributes['background-position'] ?? '') !== '') {
            $style['background-position'] = $attributes['background-position'];
        } elseif ($backgroundImage !== null) {
            $style['background-position'] = 'center';
        }
        if (($attributes['background-repeat'] ?? '') !== '') {
            $style['background-repeat'] = $attributes['background-repeat'];
        } elseif ($backgroundImage !== null) {
            $style['background-repeat'] = 'no-repeat';
        }

        foreach ([
            'font-size', 'font-family', 'font-weight', 'line-height', 'text-align', 'text-decoration',
            'width', 'height', 'max-width', 'max-height', 'min-width', 'min-height',
            'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
            'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
            'border', 'border-color', 'border-width', 'border-style',
            'border-top', 'border-right', 'border-bottom', 'border-left',
            'border-radius', 'border-top-left-radius', 'border-top-right-radius',
            'border-bottom-left-radius', 'border-bottom-right-radius',
        ] as $name) {
            $this->copy($attributes, $style, $name);
        }

        return $style;
    }

    /**
     * @param array<string, string|int|float|null> $style
     */
    public function serialize(array $style): string
    {
        $seen = [];
        $parts = [];

        foreach (self::ORDER as $name) {
            if (!array_key_exists($name, $style)) {
                continue;
            }
            $value = $style[$name];

            if ($value === null || $value === '') {
                continue;
            }
            $parts[] = "{$name}:{$value}";
            $seen[$name] = true;
        }

        foreach ($style as $name => $value) {
            if (isset($seen[$name]) || $value === null || $value === '') {
                continue;
            }
            $parts[] = "{$name}:{$value}";
        }

        return implode(';', $parts);
    }

    /**
     * @param array<string, string> $style
     * @return array{0: int, 1: int, 2: int, 3: int}
     */
    public function padding(array $style): array
    {
        $top = $right = $bottom = $left = 0;

        if (isset($style['padding'])) {
            $parts = preg_split('/\s+/', trim($style['padding'])) ?: [];
            switch (count($parts)) {
                case 1:
                    $top = $right = $bottom = $left = $this->pixels($parts[0]);
                    break;
                case 2:
                    $top = $bottom = $this->pixels($parts[0]);
                    $right = $left = $this->pixels($parts[1]);
                    break;
                case 3:
                    $top = $this->pixels($parts[0]);
                    $right = $left = $this->pixels($parts[1]);
                    $bottom = $this->pixels($parts[2]);
                    break;
                case 4:
                    $top = $this->pixels($parts[0]);
                    $right = $this->pixels($parts[1]);
                    $bottom = $this->pixels($parts[2]);
                    $left = $this->pixels($parts[3]);
                    break;
            }
        }

        $top = isset($style['padding-top']) ? $this->pixels($style['padding-top']) : $top;
        $right = isset($style['padding-right']) ? $this->pixels($style['padding-right']) : $right;
        $bottom = isset($style['padding-bottom']) ? $this->pixels($style['padding-bottom']) : $bottom;
        $left = isset($style['padding-left']) ? $this->pixels($style['padding-left']) : $left;

        return [$top, $right, $bottom, $left];
    }

    /**
     * @param array<string, string> $source
     * @param array<string, string> $target
     */
    private function copy(array $source, array &$target, string $name): void
    {
        if (($source[$name] ?? '') !== '') {
            $target[$name] = $source[$name];
        }
    }

    private function pixels(string $value): int
    {
        return PixelValue::parse(preg_replace('/px/', '', $value, 1) ?? $value);
    }
}
