<?php

declare(strict_types=1);

namespace Sevk\Markup;

final class ButtonRenderer
{
    public function __construct(private readonly StyleSerializer $styles)
    {
    }

    /**
     * @param array<string, string> $attributes
     */
    public function render(array $attributes, string $inner): string
    {
        $href = ($attributes['href'] ?? '') !== '' ? $attributes['href'] : '#';
        $style = $this->styles->extract($attributes);
        [$top, $right, $bottom, $left] = $this->styles->padding($style);

        [$leftFontWidth, $leftSpaceCount] = $this->msoSpacing($left);
        [$rightFontWidth, $rightSpaceCount] = $this->msoSpacing($right);

        $buttonStyle = [
            'line-height' => '100%',
            'text-decoration' => 'none',
            'display' => 'inline-block',
            'max-width' => '100%',
            'mso-padding-alt' => '0px',
            ...$style,
            'padding-top' => "{$top}px",
            'padding-right' => "{$right}px",
            'padding-bottom' => "{$bottom}px",
            'padding-left' => "{$left}px",
        ];

        // Outlook ignores regular anchor padding, so hidden hair spaces and
        // mso-text-raise preserve the same visual box as standards-based clients.
        return sprintf(
            '<a href="%s" target="_blank" style="%s"><!--[if mso]><i style="mso-font-width:%d%%;mso-text-raise:%d" hidden>%s</i><![endif]--><span style="max-width:100%%;display:inline-block;line-height:120%%;mso-padding-alt:0px;mso-text-raise:%d">%s</span><!--[if mso]><i style="mso-font-width:%d%%" hidden>%s&#8203;</i><![endif]--></a>',
            $href,
            $this->styles->serialize($buttonStyle),
            (int) floor($leftFontWidth * 100 + 0.5),
            $this->pixelsToPoints($top + $bottom),
            str_repeat('&#8202;', $leftSpaceCount),
            $this->pixelsToPoints($bottom),
            $inner,
            (int) floor($rightFontWidth * 100 + 0.5),
            str_repeat('&#8202;', $rightSpaceCount)
        );
    }

    private function pixelsToPoints(int $pixels): int
    {
        return (int) floor(($pixels * 3 + 2) / 4);
    }

    /**
     * @return array{0: float|int, 1: int}
     */
    private function msoSpacing(int $width): array
    {
        if ($width <= 0) {
            return [0, 0];
        }

        $spaceCount = min(1000, max(1, (int) ceil($width / 10)));

        return [$width / $spaceCount / 2.0, $spaceCount];
    }
}
