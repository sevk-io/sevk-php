<?php

declare(strict_types=1);

namespace Sevk\Markup;

final class LayoutTagRenderer
{
    private int $rowCounter = 0;

    /** @var list<string> */
    private array $gapRules = [];

    public function __construct(private readonly StyleSerializer $styles)
    {
    }

    public function reset(): void
    {
        $this->rowCounter = 0;
        $this->gapRules = [];
    }

    /**
     * @return list<string>
     */
    public function gapRules(): array
    {
        return $this->gapRules;
    }

    /**
     * @param array<string, string> $attributes
     */
    public function section(array $attributes, string $inner): string
    {
        $tableStyle = $this->styles->extract($attributes);
        $cellStyle = [];

        foreach (['padding', 'text-align'] as $name) {
            if (isset($tableStyle[$name])) {
                $cellStyle[$name] = $tableStyle[$name];
                unset($tableStyle[$name]);
            }
        }

        foreach ([
            'border-radius',
            'border-top-left-radius',
            'border-top-right-radius',
            'border-bottom-left-radius',
            'border-bottom-right-radius',
        ] as $name) {
            if (isset($tableStyle[$name])) {
                // Collapsed table borders suppress rounded corners in email clients.
                $tableStyle['border-collapse'] = 'separate';
                break;
            }
        }

        return sprintf(
            '<table align="center" width="100%%" border="0" cellPadding="0" cellSpacing="0" role="presentation" style="%s">
<tbody>
<tr>
<td style="%s">%s</td>
</tr>
</tbody>
</table>',
            $this->styles->serialize($tableStyle),
            $this->styles->serialize($cellStyle),
            $inner
        );
    }

    /**
     * @param array<string, string> $attributes
     */
    public function column(array $attributes, string $inner): string
    {
        $style = $this->styles->extract($attributes);
        $style['vertical-align'] ??= 'top';

        return sprintf(
            '<td class="sevk-column" style="%s">%s</td>',
            $this->styles->serialize($style),
            $inner
        );
    }

    /**
     * @param array<string, string> $attributes
     */
    public function row(array $attributes, string $inner): string
    {
        $gap = $attributes['gap'] ?? '0';
        $style = $this->styles->extract($attributes);
        unset($style['gap']);
        $gapPixels = preg_replace('/px/', '', $gap, 1) ?? $gap;
        $rowId = 'sevk-row-' . $this->rowCounter++;

        preg_match_all('/class="sevk-column"/', $inner, $columnMatches);
        $columnCount = count($columnMatches[0]);

        if ($columnCount > 1) {
            $equalWidth = floor(100 / $columnCount) . '%';
            $inner = preg_replace_callback(
                '/<td class="sevk-column" style="([^"]*)"/',
                static function (array $match) use ($equalWidth): string {
                    if (str_contains($match[1], 'width:')) {
                        return $match[0];
                    }

                    return '<td class="sevk-column" style="width:' . $equalWidth . ';' . $match[1] . '"';
                },
                $inner
            ) ?? $inner;
        }

        if (PixelValue::parse($gapPixels) > 0) {
            $spacer = sprintf(
                '</td><td class="sevk-gap" style="width:%spx;min-width:%spx" width="%s"></td><td class="sevk-column"',
                $gapPixels,
                $gapPixels,
                $gapPixels
            );
            $inner = preg_replace('/<\/td>\s*<td class="sevk-column"/', $spacer, $inner) ?? $inner;
            $this->gapRules[] = sprintf(
                '.%s .sevk-gap{display:none !important;}.%s > tbody > tr > td.sevk-column{display:block !important;width:100%% !important;margin-bottom:%spx !important;}.%s > tbody > tr > td.sevk-column:last-of-type{margin-bottom:0 !important;}',
                $rowId,
                $rowId,
                $gapPixels,
                $rowId
            );
        }

        return sprintf(
            '<table align="center" width="100%%" border="0" cellPadding="0" cellSpacing="0" role="presentation" class="sevk-row-table %s" style="%s">
<tbody style="width:100%%">
<tr style="width:100%%">%s</tr>
</tbody>
</table>',
            $rowId,
            $this->styles->serialize($style),
            $inner
        );
    }

    /**
     * @param array<string, string> $attributes
     */
    public function container(array $attributes, string $inner): string
    {
        $style = $this->styles->extract($attributes);
        $cellStyle = [];
        $tableStyle = [];
        $visualProperties = [
            'background-color', 'background-image', 'background-size', 'background-position', 'background-repeat',
            'border', 'border-top', 'border-right', 'border-bottom', 'border-left',
            'border-color', 'border-width', 'border-style',
            'border-radius', 'border-top-left-radius', 'border-top-right-radius',
            'border-bottom-left-radius', 'border-bottom-right-radius',
            'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
        ];

        foreach ($style as $name => $value) {
            if (in_array($name, $visualProperties, true)) {
                $cellStyle[$name] = $value;
            } else {
                $tableStyle[$name] = $value;
            }
        }

        if (
            isset($cellStyle['border-radius'])
            || isset($cellStyle['border-top-left-radius'])
            || isset($cellStyle['border-top-right-radius'])
            || isset($cellStyle['border-bottom-left-radius'])
            || isset($cellStyle['border-bottom-right-radius'])
        ) {
            $tableStyle['border-collapse'] = 'separate';
        }

        if (isset($tableStyle['width']) && $tableStyle['width'] !== '100%' && $tableStyle['width'] !== 'auto') {
            $tableStyle['max-width'] ??= $tableStyle['width'];
            $tableStyle['width'] = '100%';
        }

        return sprintf(
            '<table width="100%%" border="0" cellPadding="0" cellSpacing="0" role="presentation" style="%s">
<tbody>
<tr style="width:100%%">
<td style="%s">%s</td>
</tr>
</tbody>
</table>',
            $this->styles->serialize($tableStyle),
            $this->styles->serialize($cellStyle),
            $inner
        );
    }
}
