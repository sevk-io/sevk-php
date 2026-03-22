<?php

declare(strict_types=1);

namespace Sevk\Markup;

use Highlight\Highlighter;

/**
 * Sevk Markup Renderer
 * Converts Sevk markup to email-compatible HTML using regex-based parsing (like Node.js)
 */
class Renderer
{
    /**
     * One Dark theme - CSS class to inline style mapping
     * Maps highlight.php CSS classes to inline styles for email compatibility
     */
    private const ONE_DARK_THEME = [
        'base' => [
            'background-color' => '#282c34',
            'color' => '#abb2bf',
            'font-family' => "'Fira Code', 'Fira Mono', Menlo, Consolas, 'DejaVu Sans Mono', monospace",
            'font-size' => '13px',
            'line-height' => '1.5',
            'text-align' => 'left',
            'white-space' => 'pre',
            'word-spacing' => 'normal',
            'word-break' => 'normal',
            'padding' => '1em',
            'margin' => '0.5em 0',
            'overflow' => 'auto',
            'border-radius' => '0.3em',
        ],
        // Keywords: if, else, return, class, function, etc.
        'hljs-keyword' => ['color' => '#c678dd'],
        'hljs-selector-tag' => ['color' => '#c678dd'],
        'hljs-type' => ['color' => '#c678dd'],
        // Strings
        'hljs-string' => ['color' => '#98c379'],
        'hljs-regexp' => ['color' => '#98c379'],
        'hljs-addition' => ['color' => '#98c379'],
        'hljs-selector-attr' => ['color' => '#98c379'],
        'hljs-selector-pseudo' => ['color' => '#98c379'],
        // Numbers, constants, booleans
        'hljs-number' => ['color' => '#d19a66'],
        'hljs-literal' => ['color' => '#d19a66'],
        'hljs-attr' => ['color' => '#d19a66'],
        'hljs-selector-class' => ['color' => '#d19a66'],
        'hljs-selector-id' => ['color' => '#d19a66'],
        // Comments
        'hljs-comment' => ['color' => '#5c6370', 'font-style' => 'italic'],
        'hljs-quote' => ['color' => '#5c6370', 'font-style' => 'italic'],
        'hljs-meta' => ['color' => '#5c6370'],
        // Functions
        'hljs-title' => ['color' => '#61afef'],
        'hljs-title.function_' => ['color' => '#61afef'],
        'hljs-title.class_' => ['color' => '#e5c07b'],
        // Variables, properties
        'hljs-variable' => ['color' => '#e06c75'],
        'hljs-property' => ['color' => '#e06c75'],
        'hljs-tag' => ['color' => '#e06c75'],
        'hljs-name' => ['color' => '#e06c75'],
        'hljs-deletion' => ['color' => '#e06c75'],
        'hljs-symbol' => ['color' => '#e06c75'],
        // Built-in, section
        'hljs-built_in' => ['color' => '#e5c07b'],
        'hljs-section' => ['color' => '#e5c07b'],
        // Params, template variables
        'hljs-params' => ['color' => '#abb2bf'],
        'hljs-template-variable' => ['color' => '#d19a66'],
        'hljs-template-tag' => ['color' => '#abb2bf'],
        // Emphasis
        'hljs-emphasis' => ['font-style' => 'italic'],
        'hljs-strong' => ['font-weight' => 'bold'],
        // Subst
        'hljs-subst' => ['color' => '#e06c75'],
        // Punctuation
        'hljs-punctuation' => ['color' => '#abb2bf'],
        // Operator
        'hljs-operator' => ['color' => '#61afef'],
    ];

    private array $headSettings = [
        'title' => '',
        'previewText' => '',
        'styles' => '',
        'fonts' => [],
        'lang' => 'en',
        'dir' => 'ltr',
    ];

    /**
     * Render Sevk markup to HTML
     */
    public function render(string $markup): string
    {
        // Parse head settings from markup
        $this->parseHeadSettings($markup);

        // Extract clean body content (strips <mail>/<head> wrapper tags)
        $bodyContent = $this->extractBodyContent($markup);

        // Normalize markup
        $normalized = $this->normalizeMarkup($bodyContent);

        // Process markup using regex
        $processed = $this->processMarkup($normalized);

        return $this->generateHTML($processed);
    }

    /**
     * Extract body content from Sevk markup
     */
    private function extractBodyContent(string $markup): string
    {
        if (strpos($markup, '<mail') !== false || strpos($markup, '<email') !== false) {
            if (preg_match('/<body[^>]*>([\s\S]*?)<\/body>/i', $markup, $match)) {
                return trim($match[1]);
            }
        }
        return $markup;
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
        // Extract lang and dir from root tag (<mail> or <email>)
        if (preg_match('/<(?:mail|email)\b[^>]*>/i', $markup, $rootMatch)) {
            $rootTag = $rootMatch[0];
            if (preg_match('/\blang=["\']([^"\']*)["\']/', $rootTag, $langMatch)) {
                $this->headSettings['lang'] = $langMatch[1];
            }
            if (preg_match('/\bdir=["\']([^"\']*)["\']/', $rootTag, $dirMatch)) {
                $this->headSettings['dir'] = $dirMatch[1];
            }
        }

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

        // Convert <link> to <sevk-link> to avoid parsing issues
        $result = preg_replace('/<link\s+href=/i', '<sevk-link href=', $result);
        $result = str_replace('</link>', '</sevk-link>', $result);

        // Process block tags BEFORE other tags
        $result = $this->processTag($result, 'block', function(array $attrs, string $inner): string {
            return $this->processBlockTag($attrs, $inner);
        });

        // Also handle self-closing <block ... /> tags
        $result = preg_replace_callback('/<block([^>]*)\/\s*>/i', function($matches) {
            $attrs = $this->parseAttributes($matches[1] ?? '');
            return $this->processBlockTag($attrs);
        }, $result);

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

        // Process column tags before row (so row can count columns in inner HTML)
        $result = $this->processTag($result, 'column', function(array $attrs, string $inner): string {
            $style = $this->extractAllStyleAttributes($attrs);
            if (!isset($style['vertical-align'])) {
                $style['vertical-align'] = 'top';
            }
            $styleStr = $this->styleToString($style);
            return sprintf('<td class="sevk-column" style="%s">%s</td>', $styleStr, $inner);
        });

        // Process row tags
        $rowCounter = 0;
        $result = $this->processTag($result, 'row', function(array $attrs, string $inner) use (&$rowCounter): string {
            $gap = $attrs['gap'] ?? '0';
            $style = $this->extractAllStyleAttributes($attrs);
            unset($style['gap']);
            $gapPx = str_replace('px', '', $gap);
            $gapNum = (int)$gapPx;
            $rowId = 'sevk-row-' . $rowCounter++;

            // Assign equal widths to columns if more than one
            preg_match_all('/class="sevk-column"/', $inner, $colMatches);
            $columnCount = count($colMatches[0]);
            if ($columnCount > 1) {
                $equalWidth = floor(100 / $columnCount) . '%';
                $inner = preg_replace_callback(
                    '/<td class="sevk-column" style="([^"]*)"/',
                    function($m) use ($equalWidth) {
                        if (strpos($m[1], 'width:') !== false) {
                            return $m[0];
                        }
                        return '<td class="sevk-column" style="width:' . $equalWidth . ';' . $m[1] . '"';
                    },
                    $inner
                );
            }

            // Insert spacer <td> between columns for desktop gap
            $processedInner = $inner;
            $gapStyle = '';
            if ($gapNum > 0) {
                $spacerTd = sprintf('</td><td class="sevk-gap" style="width:%spx;min-width:%spx" width="%s"></td><td class="sevk-column"', $gapPx, $gapPx, $gapPx);
                $processedInner = preg_replace('/<\/td>\s*<td class="sevk-column"/', $spacerTd, $processedInner);
                $gapStyle = sprintf(
                    '<style>@media only screen and (max-width:479px){.%s .sevk-gap{display:none !important;}.%s > tbody > tr > td.sevk-column{display:block !important;width:100%% !important;margin-bottom:%spx !important;}.%s > tbody > tr > td.sevk-column:last-of-type{margin-bottom:0 !important;}}</style>',
                    $rowId, $rowId, $gapPx, $rowId
                );
            }

            $styleStr = $this->styleToString($style);
            return sprintf(
                '%s<table class="sevk-row-table %s" align="center" width="100%%" border="0" cellPadding="0" cellSpacing="0" role="presentation" style="%s">
<tbody style="width:100%%">
<tr style="width:100%%">%s</tr>
</tbody>
</table>',
                $gapStyle,
                $rowId,
                $styleStr,
                $processedInner
            );
        });

        // Process container tags
        $result = $this->processTag($result, 'container', function(array $attrs, string $inner): string {
            $style = $this->extractAllStyleAttributes($attrs);
            $tdStyle = [];
            $tableStyle = [];

            // Visual styles go on <td>, layout styles stay on <table>
            $visualKeys = [
                'background-color', 'background-image', 'background-size', 'background-position', 'background-repeat',
                'border', 'border-top', 'border-right', 'border-bottom', 'border-left',
                'border-color', 'border-width', 'border-style',
                'border-radius', 'border-top-left-radius', 'border-top-right-radius',
                'border-bottom-left-radius', 'border-bottom-right-radius',
                'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
            ];
            foreach ($style as $key => $value) {
                if (in_array($key, $visualKeys, true)) {
                    $tdStyle[$key] = $value;
                } else {
                    $tableStyle[$key] = $value;
                }
            }

            // Add border-collapse: separate when border-radius is used
            $hasBorderRadius = isset($tdStyle['border-radius'])
                || isset($tdStyle['border-top-left-radius'])
                || isset($tdStyle['border-top-right-radius'])
                || isset($tdStyle['border-bottom-left-radius'])
                || isset($tdStyle['border-bottom-right-radius']);
            if ($hasBorderRadius) {
                $tableStyle['border-collapse'] = 'separate';
            }

            // Make fixed widths responsive: width becomes max-width, width set to 100%
            if (isset($tableStyle['width']) && $tableStyle['width'] !== '100%' && $tableStyle['width'] !== 'auto') {
                if (!isset($tableStyle['max-width'])) {
                    $tableStyle['max-width'] = $tableStyle['width'];
                }
                $tableStyle['width'] = '100%';
            }

            $tableStyleStr = $this->styleToString($tableStyle);
            $tdStyleStr = $this->styleToString($tdStyle);
            return sprintf(
                '<table align="center" width="100%%" border="0" cellPadding="0" cellSpacing="0" role="presentation" style="%s">
<tbody>
<tr style="width:100%%">
<td style="%s">%s</td>
</tr>
</tbody>
</table>',
                $tableStyleStr,
                $tdStyleStr,
                $inner
            );
        });

        // Process heading tags
        $result = $this->processTag($result, 'heading', function(array $attrs, string $inner): string {
            $level = $attrs['level'] ?? '1';
            $style = $this->extractAllStyleAttributes($attrs);
            if (!isset($style['margin'])) $style['margin'] = '0';
            $styleStr = $this->styleToString($style);
            return sprintf('<h%s style="%s">%s</h%s>', $level, $styleStr, $inner, $level);
        });

        // Process paragraph tags
        $result = $this->processTag($result, 'paragraph', function(array $attrs, string $inner): string {
            $style = $this->extractAllStyleAttributes($attrs);
            if (!isset($style['margin'])) $style['margin'] = '0';
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
            if (!isset($style['vertical-align'])) $style['vertical-align'] = 'middle';
            if (!isset($style['max-width'])) $style['max-width'] = '100%';
            if (!isset($style['outline'])) $style['outline'] = 'none';
            if (!isset($style['border'])) $style['border'] = 'none';
            if (!isset($style['text-decoration'])) $style['text-decoration'] = 'none';

            $styleStr = $this->styleToString($style);
            $widthAttr = $width ? sprintf(' width="%s"', str_replace('px', '', $width)) : '';
            $heightAttr = $height ? sprintf(' height="%s"', str_replace('px', '', $height)) : '';

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

        // Clean up stray </divider> closing tags
        $result = preg_replace('/<\/divider>/i', '', $result);

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
            if (!isset($style['margin'])) $style['margin'] = '0';
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
            return $this->processCodeBlock($attrs, $inner);
        });

        // Clean up stray </block> closing tags
        $result = preg_replace('/<\/block>/i', '', $result);

        // Clean up stray Sevk closing tags
        $strayClosingTags = [
            '</container>', '</section>', '</row>', '</column>',
            '</heading>', '</paragraph>', '</text>', '</button>', '</sevk-link>',
        ];
        foreach ($strayClosingTags as $tag) {
            $result = str_ireplace($tag, '', $result);
        }

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
     * Check if a value is truthy for template conditionals.
     */
    private function isTruthy($val): bool
    {
        if ($val === null || $val === '' || $val === false || $val === 0 || $val === '0') {
            return false;
        }
        if (is_array($val) && empty($val)) {
            return false;
        }
        return true;
    }

    /**
     * Evaluate a condition expression supporting ==, !=, &&, || operators.
     */
    private function evaluateCondition(string $expr, array $config): bool
    {
        $trimmed = trim($expr);

        // OR: split on ||, return true if any part is true
        if (strpos($trimmed, '||') !== false) {
            $parts = explode('||', $trimmed);
            foreach ($parts as $part) {
                if ($this->evaluateCondition($part, $config)) return true;
            }
            return false;
        }

        // AND: split on &&, return true if all parts are true
        if (strpos($trimmed, '&&') !== false) {
            $parts = explode('&&', $trimmed);
            foreach ($parts as $part) {
                if (!$this->evaluateCondition($part, $config)) return false;
            }
            return true;
        }

        // Equality: key == "value"
        if (preg_match('/^(\w+)\s*==\s*"([^"]*)"$/', $trimmed, $eqMatch)) {
            return (string)($config[$eqMatch[1]] ?? '') === $eqMatch[2];
        }

        // Inequality: key != "value"
        if (preg_match('/^(\w+)\s*!=\s*"([^"]*)"$/', $trimmed, $neqMatch)) {
            return (string)($config[$neqMatch[1]] ?? '') !== $neqMatch[2];
        }

        // Simple truthy check
        return $this->isTruthy($config[$trimmed] ?? null);
    }

    /**
     * Render a template string with config values using template syntax.
     */
    private function renderTemplate(string $template, array $config): string
    {
        $result = $template;

        // Process {%#each array as alias%}...{%/each%} loops
        $result = preg_replace_callback(
            '/\{%#each\s+(\w+)\s+as\s+(\w+)%\}([\s\S]*?)\{%\/each%\}/',
            function (array $m) use ($config): string {
                $arrayKey = $m[1];
                $alias = $m[2];
                $body = $m[3];
                $items = $config[$arrayKey] ?? [];
                if (!is_array($items)) {
                    return '';
                }
                $out = '';
                foreach ($items as $item) {
                    $iterBody = $body;
                    if (is_array($item)) {
                        // Replace {%alias.prop%} and {%alias.prop ?? fallback%}
                        $iterBody = preg_replace_callback(
                            '/\{%' . preg_quote($alias, '/') . '\.(\w+)(?:\s*\?\?\s*(.*?))?\s*%\}/',
                            function (array $vm) use ($item): string {
                                $val = $item[$vm[1]] ?? null;
                                if ($val !== null && $val !== '') {
                                    return (string)$val;
                                }
                                return isset($vm[2]) ? trim($vm[2]) : '';
                            },
                            $iterBody
                        );
                    } else {
                        // Scalar item: replace {%alias%} and {%alias ?? fallback%}
                        $iterBody = preg_replace_callback(
                            '/\{%' . preg_quote($alias, '/') . '(?:\s*\?\?\s*(.*?))?\s*%\}/',
                            function (array $vm) use ($item): string {
                                if ($item !== null && $item !== '') {
                                    return (string)$item;
                                }
                                return isset($vm[1]) ? trim($vm[1]) : '';
                            },
                            $iterBody
                        );
                    }
                    $out .= $iterBody;
                }
                return $out;
            },
            $result
        );

        // Process nested {%#if condition%}...{%/if%} (innermost first)
        $ifPattern = '/\{%#if\s+([^%]+)%\}((?:(?!\{%#if\s)[\s\S])*?)\{%\/if%\}/';
        $maxIter = 50;
        while ($maxIter-- > 0 && preg_match($ifPattern, $result)) {
            $result = preg_replace_callback(
                $ifPattern,
                function (array $m) use ($config): string {
                    $condition = $m[1];
                    $body = $m[2];
                    $condResult = $this->evaluateCondition($condition, $config);
                    if (strpos($body, '{%else%}') !== false) {
                        $parts = explode('{%else%}', $body, 2);
                        return $condResult ? $parts[0] : $parts[1];
                    }
                    return $condResult ? $body : '';
                },
                $result
            );
        }

        // Process {%variable ?? fallback%} (must come before plain variable)
        $result = preg_replace_callback(
            '/\{%(\w+)\s*\?\?\s*(.*?)%\}/',
            function (array $m) use ($config): string {
                $val = $config[$m[1]] ?? null;
                if ($val !== null && $val !== '') {
                    return is_array($val) ? json_encode($val) : (string)$val;
                }
                return trim($m[2]);
            },
            $result
        );

        // Process {%variable%} simple injection
        $result = preg_replace_callback(
            '/\{%(\w+)%\}/',
            function (array $m) use ($config): string {
                $val = $config[$m[1]] ?? '';
                return is_array($val) ? json_encode($val) : (string)$val;
            },
            $result
        );

        return $result;
    }

    /**
     * Process a <block> tag using template-based rendering.
     */
    private function processBlockTag(array $attrs, string $inner = ''): string
    {
        $template = trim($inner) ?: ($attrs['template'] ?? '');
        if (!$template) {
            return '';
        }
        $configStr = $attrs['config'] ?? '{}';
        $configStr = str_replace(["'", '&quot;', '&amp;'], ['"', '"', '&'], $configStr);
        $config = json_decode($configStr, true) ?? [];
        return $this->renderTemplate($template, $config);
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
     * Process codeblock with syntax highlighting using highlight.php
     */
    private function processCodeBlock(array $attrs, string $inner): string
    {
        $language = $attrs['language'] ?? null;
        $customStyle = $this->extractAllStyleAttributes($attrs);

        // Merge base theme styles with custom styles
        $preStyle = array_merge(self::ONE_DARK_THEME['base'], [
            'width' => '100%',
            'box-sizing' => 'border-box',
        ], $customStyle);

        // Decode any HTML entities in the inner content first
        $code = html_entity_decode($inner, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // If no language specified or highlight.php not available, fall back to plain rendering
        if ($language === null) {
            $escaped = str_replace(['<', '>'], ['&lt;', '&gt;'], $code);
            $styleStr = $this->styleToString($preStyle);
            return sprintf('<pre style="%s"><code>%s</code></pre>', $styleStr, $escaped);
        }

        try {
            $highlighter = new Highlighter();
            $result = $highlighter->highlight($language, $code);
            $highlighted = $result->value;
        } catch (\Exception $e) {
            // Language not recognized, fall back to plain rendering
            $escaped = str_replace(['<', '>'], ['&lt;', '&gt;'], $code);
            $styleStr = $this->styleToString($preStyle);
            return sprintf('<pre style="%s"><code>%s</code></pre>', $styleStr, $escaped);
        }

        // Convert CSS classes to inline styles for email compatibility
        $inlined = $this->convertHljsClassesToInlineStyles($highlighted);

        // Render line by line like the Node SDK
        $lines = preg_split('/\r\n|\r|\n/', $inlined);
        $linesHTML = [];
        foreach ($lines as $line) {
            $linesHTML[] = sprintf('<p style="margin:0;min-height:1em">%s</p>', $line);
        }

        $styleStr = $this->styleToString($preStyle);
        return sprintf('<pre style="%s"><code>%s</code></pre>', $styleStr, implode('', $linesHTML));
    }

    /**
     * Convert highlight.php CSS class-based spans to inline-styled spans
     * This is needed because email clients don't support <style> blocks reliably.
     */
    private function convertHljsClassesToInlineStyles(string $html): string
    {
        return preg_replace_callback(
            '/<span class="([^"]*)">([\s\S]*?)<\/span>/',
            function (array $matches): string {
                $classes = explode(' ', $matches[1]);
                $content = $matches[2];
                $styles = [];

                foreach ($classes as $class) {
                    $class = trim($class);
                    if ($class === '') continue;

                    // Try exact match first (e.g. "hljs-keyword")
                    if (isset(self::ONE_DARK_THEME[$class])) {
                        $styles = array_merge($styles, self::ONE_DARK_THEME[$class]);
                        continue;
                    }

                    // Try compound class match (e.g. "hljs-title function_" -> "hljs-title.function_")
                    foreach ($classes as $otherClass) {
                        $otherClass = trim($otherClass);
                        if ($otherClass === '' || $otherClass === $class) continue;
                        $compound = "{$class}.{$otherClass}";
                        if (isset(self::ONE_DARK_THEME[$compound])) {
                            $styles = array_merge($styles, self::ONE_DARK_THEME[$compound]);
                        }
                    }
                }

                if (empty($styles)) {
                    // Recursively process nested spans even if this span has no styles
                    $content = $this->convertHljsClassesToInlineStyles($content);
                    return $content;
                }

                // Recursively process nested spans
                $content = $this->convertHljsClassesToInlineStyles($content);
                $styleStr = $this->styleToString($styles);
                return sprintf('<span style="%s">%s</span>', $styleStr, $content);
            },
            $html
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

                // Find the next close tag after this opening tag using case-insensitive regex
                // Note: stripos() is avoided because it lowercases the full string internally,
                // which corrupts byte offsets when multibyte characters like Turkish İ change
                // length on case conversion. preg_match with PREG_OFFSET_CAPTURE returns
                // correct byte positions against the original string.
                $closePattern = '/<\/' . preg_quote($tagName, '/') . '>/i';
                if (!preg_match($closePattern, $result, $closeMatch, PREG_OFFSET_CAPTURE, $innerStart)) {
                    continue;
                }
                $closePos = $closeMatch[0][1];

                $inner = substr($result, $innerStart, $closePos - $innerStart);

                // Check if there's another opening tag inside using case-insensitive regex
                $nestedOpenPattern = '/<' . preg_quote($tagName, '/') . '[\s>]/i';
                if (preg_match($nestedOpenPattern, $inner)) {
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
        if (preg_match_all('/([\w-]+)=(?:"([^"]*)"|\'([^\']*)\')/', $attrsStr, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attrs[$match[1]] = $match[2] !== '' ? $match[2] : ($match[3] ?? '');
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
        if (isset($attrs['max-height'])) $style['max-height'] = $attrs['max-height'];
        if (isset($attrs['min-width'])) $style['min-width'] = $attrs['min-width'];
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

        // Background image
        $backgroundImage = $attrs['background-image'] ?? null;
        if ($backgroundImage) {
            $style['background-image'] = "url('{$backgroundImage}')";
            $style['background-size'] = $attrs['background-size'] ?? 'cover';
            $style['background-position'] = $attrs['background-position'] ?? 'center';
            $style['background-repeat'] = $attrs['background-repeat'] ?? 'no-repeat';
        } else {
            if (isset($attrs['background-size'])) $style['background-size'] = $attrs['background-size'];
            if (isset($attrs['background-position'])) $style['background-position'] = $attrs['background-position'];
            if (isset($attrs['background-repeat'])) $style['background-repeat'] = $attrs['background-repeat'];
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
<html lang="{$this->headSettings['lang']}" dir="{$this->headSettings['dir']}" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta content="text/html; charset=UTF-8" http-equiv="Content-Type"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="x-apple-disable-message-reformatting"/>
<meta content="IE=edge" http-equiv="X-UA-Compatible"/>
<meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no"/>
<!--[if mso]>
<noscript>
<xml>
<o:OfficeDocumentSettings>
<o:AllowPNG/>
<o:PixelsPerInch>96</o:PixelsPerInch>
</o:OfficeDocumentSettings>
</xml>
</noscript>
<![endif]-->
<style type="text/css">
#outlook a { padding: 0; }
body { margin: 0; padding: 0; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
table, td { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
.sevk-row-table { border-collapse: separate !important; }
img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
@media only screen and (max-width: 479px) {
  .sevk-row-table { width: 100% !important; }
  .sevk-column { display: block !important; width: 100% !important; max-width: 100% !important; }
}
</style>
{$title}
{$fontLinks}
{$styles}
</head>
<body style="margin:0;padding:0;word-spacing:normal;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;font-family:ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif">
<div aria-roledescription="email" role="article">
{$previewText}
{$content}
</div>
</body>
</html>
HTML;
    }
}
