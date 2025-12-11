<?php

declare(strict_types=1);

namespace Sevk\Markup;

/**
 * Sevk Markup Renderer
 * Converts Sevk markup to email-compatible HTML using regex-based parsing (like Node.js)
 */
class Renderer
{
    private array $headSettings = [
        'title' => '',
        'previewText' => '',
        'styles' => '',
        'fonts' => []
    ];

    /**
     * Render Sevk markup to HTML
     */
    public function render(string $markup): string
    {
        // Parse head settings from markup
        $this->parseHeadSettings($markup);

        // Normalize markup
        $markup = $this->normalizeMarkup($markup);

        // Process markup using regex
        $processed = $this->processMarkup($markup);

        return $this->generateHTML($processed);
    }

    /**
     * Normalize markup by wrapping if needed
     */
    private function normalizeMarkup(string $markup): string
    {
        // Replace <link> with <sevk-link> to avoid parsing issues
        $markup = preg_replace('/<link\s+href=/i', '<sevk-link href=', $markup);
        $markup = str_replace('</link>', '</sevk-link>', $markup);

        if (strpos($markup, '<sevk-email') !== false || strpos($markup, '<email') !== false || strpos($markup, '<mail') !== false) {
            return $markup;
        }

        return "<mail><body>{$markup}</body></mail>";
    }

    /**
     * Parse head settings from markup
     */
    private function parseHeadSettings(string $markup): void
    {
        // Extract title
        if (preg_match('/<title[^>]*>([\s\S]*?)<\/title>/i', $markup, $match)) {
            $this->headSettings['title'] = trim($match[1]);
        }

        // Extract preview
        if (preg_match('/<preview[^>]*>([\s\S]*?)<\/preview>/i', $markup, $match)) {
            $this->headSettings['previewText'] = trim($match[1]);
        }

        // Extract styles
        if (preg_match('/<style[^>]*>([\s\S]*?)<\/style>/i', $markup, $match)) {
            $this->headSettings['styles'] = trim($match[1]);
        }

        // Extract fonts
        preg_match_all('/<font[^>]*name=["\']([^"\']*)["\'][^>]*url=["\']([^"\']*)["\'][^>]*\/?>/i', $markup, $matches, PREG_SET_ORDER);
        foreach ($matches as $i => $match) {
            $this->headSettings['fonts'][] = [
                'id' => "font-{$i}",
                'name' => $match[1],
                'url' => $match[2]
            ];
        }
    }

    /**
     * Process markup using regex-based parsing (like Node.js)
     */
    private function processMarkup(string $content): string
    {
        $result = $content;

        // Process section tags
        $result = $this->processTag($result, 'section', function(array $attrs, string $inner): string {
            $style = $this->extractAllStyleAttributes($attrs);
            $styleStr = $this->styleToString($style);
            return sprintf(
                '<table align="center" width="100%%" border="0" cellPadding="0" cellSpacing="0" role="presentation" style="%s">
<tbody>
<tr>
<td>%s</td>
</tr>
</tbody>
</table>',
                $styleStr,
                $inner
            );
        });

        // Process row tags
        $result = $this->processTag($result, 'row', function(array $attrs, string $inner): string {
            $style = $this->extractAllStyleAttributes($attrs);
            $styleStr = $this->styleToString($style);
            return sprintf(
                '<table align="center" width="100%%" border="0" cellPadding="0" cellSpacing="0" role="presentation" style="%s">
<tbody style="width:100%%">
<tr style="width:100%%">%s</tr>
</tbody>
</table>',
                $styleStr,
                $inner
            );
        });

        // Process column tags
        $result = $this->processTag($result, 'column', function(array $attrs, string $inner): string {
            $style = $this->extractAllStyleAttributes($attrs);
            $styleStr = $this->styleToString($style);
            return sprintf('<td style="%s">%s</td>', $styleStr, $inner);
        });

        // Process container tags
        $result = $this->processTag($result, 'container', function(array $attrs, string $inner): string {
            $style = $this->extractAllStyleAttributes($attrs);
            $styleStr = $this->styleToString($style);
            return sprintf(
                '<table align="center" width="100%%" border="0" cellPadding="0" cellSpacing="0" role="presentation" style="%s">
<tbody>
<tr style="width:100%%">
<td>%s</td>
</tr>
</tbody>
</table>',
                $styleStr,
                $inner
            );
        });

        // Process heading tags
        $result = $this->processTag($result, 'heading', function(array $attrs, string $inner): string {
            $level = $attrs['level'] ?? '1';
            $style = $this->extractAllStyleAttributes($attrs);
            $styleStr = $this->styleToString($style);
            return sprintf('<h%s style="%s">%s</h%s>', $level, $styleStr, $inner, $level);
        });

        // Process paragraph tags
        $result = $this->processTag($result, 'paragraph', function(array $attrs, string $inner): string {
            $style = $this->extractAllStyleAttributes($attrs);
            $styleStr = $this->styleToString($style);
            return sprintf('<p style="%s">%s</p>', $styleStr, $inner);
        });

        // Process text tags
        $result = $this->processTag($result, 'text', function(array $attrs, string $inner): string {
            $style = $this->extractAllStyleAttributes($attrs);
            $styleStr = $this->styleToString($style);
            return sprintf('<span style="%s">%s</span>', $styleStr, $inner);
        });

        // Process button tags with MSO compatibility
        $result = $this->processTag($result, 'button', function(array $attrs, string $inner): string {
            return $this->processButton($attrs, $inner);
        });

        // Process image tags
        $result = preg_replace_callback('/<image([^>]*)\/?>/i', function($matches) {
            $attrs = $this->parseAttributes($matches[1] ?? '');
            $src = $attrs['src'] ?? '';
            $alt = $attrs['alt'] ?? '';
            $width = $attrs['width'] ?? null;
            $height = $attrs['height'] ?? null;

            $style = $this->extractAllStyleAttributes($attrs);
            if (!isset($style['outline'])) $style['outline'] = 'none';
            if (!isset($style['border'])) $style['border'] = 'none';
            if (!isset($style['text-decoration'])) $style['text-decoration'] = 'none';

            $styleStr = $this->styleToString($style);
            $widthAttr = $width ? sprintf(' width="%s"', $width) : '';
            $heightAttr = $height ? sprintf(' height="%s"', $height) : '';

            return sprintf('<img src="%s" alt="%s"%s%s style="%s" />', $src, $alt, $widthAttr, $heightAttr, $styleStr);
        }, $result);

        // Process divider tags
        $result = preg_replace_callback('/<divider([^>]*)\/?>/i', function($matches) {
            $attrs = $this->parseAttributes($matches[1] ?? '');
            $style = $this->extractAllStyleAttributes($attrs);
            $styleStr = $this->styleToString($style);
            $classAttr = $attrs['class'] ?? $attrs['className'] ?? null;
            $classStr = $classAttr ? sprintf(' class="%s"', $classAttr) : '';
            return sprintf('<hr style="%s"%s />', $styleStr, $classStr);
        }, $result);

        // Process link tags
        $result = $this->processTag($result, 'sevk-link', function(array $attrs, string $inner): string {
            $href = $attrs['href'] ?? '#';
            $target = $attrs['target'] ?? '_blank';
            $style = $this->extractAllStyleAttributes($attrs);
            $styleStr = $this->styleToString($style);
            return sprintf('<a href="%s" target="%s" style="%s">%s</a>', $href, $target, $styleStr, $inner);
        });

        // Process list tags
        $result = $this->processTag($result, 'list', function(array $attrs, string $inner): string {
            $listType = $attrs['type'] ?? 'unordered';
            $tag = $listType === 'ordered' ? 'ol' : 'ul';
            $style = $this->extractAllStyleAttributes($attrs);
            if (isset($attrs['list-style-type'])) {
                $style['list-style-type'] = $attrs['list-style-type'];
            }
            $styleStr = $this->styleToString($style);
            $classAttr = $attrs['class'] ?? $attrs['className'] ?? null;
            $classStr = $classAttr ? sprintf(' class="%s"', $classAttr) : '';
            return sprintf('<%s style="%s"%s>%s</%s>', $tag, $styleStr, $classStr, $inner, $tag);
        });

        // Process list item tags
        $result = $this->processTag($result, 'li', function(array $attrs, string $inner): string {
            $style = $this->extractAllStyleAttributes($attrs);
            $styleStr = $this->styleToString($style);
            $classAttr = $attrs['class'] ?? $attrs['className'] ?? null;
            $classStr = $classAttr ? sprintf(' class="%s"', $classAttr) : '';
            return sprintf('<li style="%s"%s>%s</li>', $styleStr, $classStr, $inner);
        });

        // Process codeblock tags
        $result = $this->processTag($result, 'codeblock', function(array $attrs, string $inner): string {
            $style = $this->extractAllStyleAttributes($attrs);
            if (!isset($style['width'])) $style['width'] = '100%';
            if (!isset($style['box-sizing'])) $style['box-sizing'] = 'border-box';
            $styleStr = $this->styleToString($style);
            $escaped = str_replace(['<', '>'], ['&lt;', '&gt;'], $inner);
            return sprintf('<pre style="%s"><code>%s</code></pre>', $styleStr, $escaped);
        });

        // Clean up wrapper tags
        $wrapperPatterns = [
            '/<sevk-email[^>]*>/i', '/<\/sevk-email>/i',
            '/<sevk-body[^>]*>/i', '/<\/sevk-body>/i',
            '/<email[^>]*>/i', '/<\/email>/i',
            '/<mail[^>]*>/i', '/<\/mail>/i',
            '/<body[^>]*>/i', '/<\/body>/i',
        ];
        foreach ($wrapperPatterns as $pattern) {
            $result = preg_replace($pattern, '', $result);
        }

        return trim($result);
    }

    /**
     * Process button with MSO compatibility (like Node.js)
     */
    private function processButton(array $attrs, string $inner): string
    {
        $href = $attrs['href'] ?? '#';
        $style = $this->extractAllStyleAttributes($attrs);

        // Parse padding
        [$paddingTop, $paddingRight, $paddingBottom, $paddingLeft] = $this->parsePadding($style);

        $y = $paddingTop + $paddingBottom;
        $textRaise = $this->pxToPt($y);

        [$plFontWidth, $plSpaceCount] = $this->computeFontWidthAndSpaceCount($paddingLeft);
        [$prFontWidth, $prSpaceCount] = $this->computeFontWidthAndSpaceCount($paddingRight);

        $buttonStyle = [
            'line-height' => '100%',
            'text-decoration' => 'none',
            'display' => 'inline-block',
            'max-width' => '100%',
            'mso-padding-alt' => '0px',
        ];

        // Merge with extracted styles
        foreach ($style as $k => $v) {
            $buttonStyle[$k] = $v;
        }

        // Override padding with parsed values
        $buttonStyle['padding-top'] = "{$paddingTop}px";
        $buttonStyle['padding-right'] = "{$paddingRight}px";
        $buttonStyle['padding-bottom'] = "{$paddingBottom}px";
        $buttonStyle['padding-left'] = "{$paddingLeft}px";

        $styleStr = $this->styleToString($buttonStyle);

        $leftMsoSpaces = str_repeat('&#8202;', $plSpaceCount);
        $rightMsoSpaces = str_repeat('&#8202;', $prSpaceCount);

        return sprintf(
            '<a href="%s" target="_blank" style="%s"><!--[if mso]><i style="mso-font-width:%d%%;mso-text-raise:%d" hidden>%s</i><![endif]--><span style="max-width:100%%;display:inline-block;line-height:120%%;mso-padding-alt:0px;mso-text-raise:%d">%s</span><!--[if mso]><i style="mso-font-width:%d%%" hidden>%s&#8203;</i><![endif]--></a>',
            $href,
            $styleStr,
            (int)round($plFontWidth * 100),
            $textRaise,
            $leftMsoSpaces,
            $this->pxToPt($paddingBottom),
            $inner,
            (int)round($prFontWidth * 100),
            $rightMsoSpaces
        );
    }

    /**
     * Parse padding values from style
     */
    private function parsePadding(array $style): array
    {
        if (isset($style['padding'])) {
            $parts = preg_split('/\s+/', trim($style['padding']));
            switch (count($parts)) {
                case 1:
                    $val = $this->parsePx($parts[0]);
                    return [$val, $val, $val, $val];
                case 2:
                    $vertical = $this->parsePx($parts[0]);
                    $horizontal = $this->parsePx($parts[1]);
                    return [$vertical, $horizontal, $vertical, $horizontal];
                case 4:
                    return [
                        $this->parsePx($parts[0]),
                        $this->parsePx($parts[1]),
                        $this->parsePx($parts[2]),
                        $this->parsePx($parts[3])
                    ];
            }
        }

        return [
            $this->parsePx($style['padding-top'] ?? '0'),
            $this->parsePx($style['padding-right'] ?? '0'),
            $this->parsePx($style['padding-bottom'] ?? '0'),
            $this->parsePx($style['padding-left'] ?? '0'),
        ];
    }

    private function parsePx(string $s): int
    {
        return (int)str_replace('px', '', $s);
    }

    /**
     * Convert px to pt for MSO
     */
    private function pxToPt(int $px): int
    {
        return (int)(($px * 3) / 4);
    }

    /**
     * Compute font width and space count for MSO padding
     */
    private function computeFontWidthAndSpaceCount(int $expectedWidth): array
    {
        if ($expectedWidth === 0) {
            return [0, 0];
        }

        $smallestSpaceCount = 0;
        $maxFontWidth = 5.0;

        while (true) {
            if ($smallestSpaceCount > 0) {
                $requiredFontWidth = $expectedWidth / $smallestSpaceCount / 2.0;
            } else {
                $requiredFontWidth = INF;
            }

            if ($requiredFontWidth <= $maxFontWidth) {
                return [$requiredFontWidth, $smallestSpaceCount];
            }
            $smallestSpaceCount++;
        }
    }

    /**
     * Process a tag with regex-based parsing
     */
    private function processTag(string $content, string $tagName, callable $processor): string
    {
        $result = $content;
        $openPattern = "/<{$tagName}([^>]*)>/i";
        $closeTag = "</{$tagName}>";
        $openTagStart = "<{$tagName}";

        $maxIterations = 10000;
        $iterations = 0;

        while ($iterations < $maxIterations) {
            $iterations++;

            // Find all opening tags
            if (!preg_match_all($openPattern, $result, $matches, PREG_OFFSET_CAPTURE)) {
                break;
            }

            $processed = false;

            // Find the innermost tag (one that has no nested same tags)
            for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
                $fullMatch = $matches[0][$i];
                $attrsMatch = $matches[1][$i];

                $start = $fullMatch[1];
                $innerStart = $start + strlen($fullMatch[0]);
                $attrsStr = $attrsMatch[0];

                // Find the next close tag after this opening tag
                $closePos = stripos($result, $closeTag, $innerStart);
                if ($closePos === false) {
                    continue;
                }

                $inner = substr($result, $innerStart, $closePos - $innerStart);

                // Check if there's another opening tag inside
                if (stripos($inner, $openTagStart) !== false) {
                    // This tag has nested same tags, skip it
                    continue;
                }

                // This is an innermost tag, process it
                $attrs = $this->parseAttributes($attrsStr);
                $replacement = $processor($attrs, $inner);
                $end = $closePos + strlen($closeTag);

                $result = substr($result, 0, $start) . $replacement . substr($result, $end);
                $processed = true;
                break;
            }

            if (!$processed) {
                break;
            }
        }

        return $result;
    }

    /**
     * Parse attributes from an attribute string
     */
    private function parseAttributes(string $attrsStr): array
    {
        $attrs = [];
        if (preg_match_all('/([\w-]+)=["\']([^"\']*)["\']/', $attrsStr, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attrs[$match[1]] = $match[2];
            }
        }
        return $attrs;
    }

    /**
     * Extract all style attributes from element attributes (like Node.js extractStyleAttributes)
     */
    private function extractAllStyleAttributes(array $attrs): array
    {
        $style = [];

        // Typography attributes
        if (isset($attrs['text-color'])) {
            $style['color'] = $attrs['text-color'];
        } elseif (isset($attrs['color'])) {
            $style['color'] = $attrs['color'];
        }
        if (isset($attrs['background-color'])) $style['background-color'] = $attrs['background-color'];
        if (isset($attrs['font-size'])) $style['font-size'] = $attrs['font-size'];
        if (isset($attrs['font-family'])) $style['font-family'] = $attrs['font-family'];
        if (isset($attrs['font-weight'])) $style['font-weight'] = $attrs['font-weight'];
        if (isset($attrs['line-height'])) $style['line-height'] = $attrs['line-height'];
        if (isset($attrs['text-align'])) $style['text-align'] = $attrs['text-align'];
        if (isset($attrs['text-decoration'])) $style['text-decoration'] = $attrs['text-decoration'];

        // Dimensions
        if (isset($attrs['width'])) $style['width'] = $attrs['width'];
        if (isset($attrs['height'])) $style['height'] = $attrs['height'];
        if (isset($attrs['max-width'])) $style['max-width'] = $attrs['max-width'];
        if (isset($attrs['min-height'])) $style['min-height'] = $attrs['min-height'];

        // Spacing - Padding
        if (isset($attrs['padding'])) {
            $style['padding'] = $attrs['padding'];
        } else {
            if (isset($attrs['padding-top'])) $style['padding-top'] = $attrs['padding-top'];
            if (isset($attrs['padding-right'])) $style['padding-right'] = $attrs['padding-right'];
            if (isset($attrs['padding-bottom'])) $style['padding-bottom'] = $attrs['padding-bottom'];
            if (isset($attrs['padding-left'])) $style['padding-left'] = $attrs['padding-left'];
        }

        // Spacing - Margin
        if (isset($attrs['margin'])) {
            $style['margin'] = $attrs['margin'];
        } else {
            if (isset($attrs['margin-top'])) $style['margin-top'] = $attrs['margin-top'];
            if (isset($attrs['margin-right'])) $style['margin-right'] = $attrs['margin-right'];
            if (isset($attrs['margin-bottom'])) $style['margin-bottom'] = $attrs['margin-bottom'];
            if (isset($attrs['margin-left'])) $style['margin-left'] = $attrs['margin-left'];
        }

        // Borders
        if (isset($attrs['border'])) {
            $style['border'] = $attrs['border'];
        } else {
            if (isset($attrs['border-top'])) $style['border-top'] = $attrs['border-top'];
            if (isset($attrs['border-right'])) $style['border-right'] = $attrs['border-right'];
            if (isset($attrs['border-bottom'])) $style['border-bottom'] = $attrs['border-bottom'];
            if (isset($attrs['border-left'])) $style['border-left'] = $attrs['border-left'];
            if (isset($attrs['border-color'])) $style['border-color'] = $attrs['border-color'];
            if (isset($attrs['border-width'])) $style['border-width'] = $attrs['border-width'];
            if (isset($attrs['border-style'])) $style['border-style'] = $attrs['border-style'];
        }

        // Border Radius
        if (isset($attrs['border-radius'])) {
            $style['border-radius'] = $attrs['border-radius'];
        } else {
            if (isset($attrs['border-top-left-radius'])) $style['border-top-left-radius'] = $attrs['border-top-left-radius'];
            if (isset($attrs['border-top-right-radius'])) $style['border-top-right-radius'] = $attrs['border-top-right-radius'];
            if (isset($attrs['border-bottom-left-radius'])) $style['border-bottom-left-radius'] = $attrs['border-bottom-left-radius'];
            if (isset($attrs['border-bottom-right-radius'])) $style['border-bottom-right-radius'] = $attrs['border-bottom-right-radius'];
        }

        return $style;
    }

    /**
     * Convert style array to inline style string
     */
    private function styleToString(array $style): string
    {
        $parts = [];
        foreach ($style as $k => $v) {
            $parts[] = "{$k}:{$v}";
        }
        return implode(';', $parts);
    }

    /**
     * Generate final HTML document
     */
    private function generateHTML(string $content): string
    {
        $title = $this->headSettings['title'] ? "<title>{$this->headSettings['title']}</title>" : '';

        $fontLinks = '';
        foreach ($this->headSettings['fonts'] as $font) {
            $fontLinks .= sprintf('<link href="%s" rel="stylesheet" type="text/css" />', htmlspecialchars($font['url'], ENT_QUOTES));
        }

        $styles = $this->headSettings['styles'] ? "<style type=\"text/css\">{$this->headSettings['styles']}</style>" : '';

        $previewText = '';
        if ($this->headSettings['previewText']) {
            $previewText = sprintf(
                '<div style="display:none;font-size:1px;color:#ffffff;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;">%s</div>',
                htmlspecialchars($this->headSettings['previewText'], ENT_QUOTES)
            );
        }

        return <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en" dir="ltr">
<head>
<meta content="text/html; charset=UTF-8" http-equiv="Content-Type"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
{$title}
{$fontLinks}
{$styles}
</head>
<body style="margin:0;padding:0;font-family:ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;background-color:#ffffff">
{$previewText}
{$content}
</body>
</html>
HTML;
    }
}
