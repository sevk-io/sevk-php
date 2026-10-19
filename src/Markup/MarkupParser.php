<?php

declare(strict_types=1);

namespace Sevk\Markup;

final class MarkupParser
{
    private const DEPTH_TAGS = [
        'block', 'section', 'column', 'row', 'container', 'heading', 'paragraph', 'text', 'button', 'link',
        'sevk-link', 'list', 'li', 'codeblock',
    ];

    /**
     * @return array{
     *     title: string,
     *     previewText: string,
     *     styles: string,
     *     fonts: list<array{id: string, name: string, url: string}>,
     *     lang: string,
     *     dir: string
     * }
     */
    public function parseHead(string $markup): array
    {
        $settings = [
            'title' => '',
            'previewText' => '',
            'styles' => '',
            'fonts' => [],
            'lang' => 'en',
            'dir' => 'ltr',
        ];

        if (!$this->isSevkDocument($markup)) {
            return $this->parseHtmlHead($markup, $settings);
        }

        if (preg_match('/<email([^>]*)>/i', $markup, $rootMatch) || preg_match('/<mail([^>]*)>/i', $markup, $rootMatch)) {
            if (preg_match('/lang=["\']([^"\']*)["\']/i', $rootMatch[1], $langMatch)) {
                $settings['lang'] = $langMatch[1];
            }
            if (preg_match('/dir=["\']([^"\']*)["\']/i', $rootMatch[1], $dirMatch)) {
                $settings['dir'] = $dirMatch[1];
            }
        }

        if (preg_match('/<title[^>]*>([\s\S]*?)<\/title>/i', $markup, $match)) {
            $settings['title'] = trim($match[1]);
        }
        if (preg_match('/<preview[^>]*>([\s\S]*?)<\/preview>/i', $markup, $match)) {
            $settings['previewText'] = trim($match[1]);
        }
        if (preg_match('/<style[^>]*>([\s\S]*?)<\/style>/i', $markup, $match)) {
            $settings['styles'] = trim($match[1]);
        }

        preg_match_all('/<font([^>]*)>/i', $markup, $matches, PREG_SET_ORDER);
        $seen = [];

        foreach ($matches as $font) {
            if (
                !preg_match('/name=["\']([^"\']*)["\']/i', $font[1], $name)
                || !preg_match('/url=["\']([^"\']*)["\']/i', $font[1], $url)
                || isset($seen[$url[1]])
            ) {
                continue;
            }
            $seen[$url[1]] = true;
            $index = count($settings['fonts']);
            $settings['fonts'][] = [
                'id' => "font-{$index}",
                'name' => $name[1],
                'url' => $url[1],
            ];
        }

        return $settings;
    }

    public function extractBody(string $markup): string
    {
        if (!$this->isSevkDocument($markup)) {
            $body = preg_match('/<body[^>]*>([\s\S]*?)<\/body>/i', $markup, $match) ? $match[1] : $markup;
            $preview = $this->hiddenPreview($markup);

            if ($preview !== null) {
                $body = str_replace($preview[0], '', $body);
            }

            return trim($body);
        }

        if (preg_match('/<body[^>]*>([\s\S]*?)<\/body>/i', $markup, $match) && trim($match[1]) !== '') {
            return trim($match[1]);
        }

        $body = preg_replace([
            '/<(?:email|mail)[^>]*>/i', '/<\/(?:email|mail)>/i',
            '/<head[^>]*>[\s\S]*?<\/head>/i',
            '/<title[^>]*>[\s\S]*?<\/title>/i',
            '/<preview[^>]*>[\s\S]*?<\/preview>/i',
            '/<style[^>]*>[\s\S]*?<\/style>/i',
            '/<font[^>]*>/i', '/<\/font>/i',
        ], '', $markup) ?? $markup;

        return trim($body);
    }

    public function normalize(string $markup): string
    {
        $normalized = preg_replace('/<link(\s+(?:[\w-]+=(?:"[^"]*"|\'[^\']*\')\s+)*)href=/i', '<sevk-link${1}href=', $markup) ?? $markup;
        $normalized = str_ireplace('</link>', '</sevk-link>', $normalized);

        if (
            preg_match('/<(?:sevk-email|email|mail)(?=[\s>])/i', $normalized)
        ) {
            return $normalized;
        }

        return "<mail><body>{$normalized}</body></mail>";
    }

    public function assertDepth(string $content): void
    {
        $depths = [];
        $length = strlen($content);
        $index = 0;

        while ($index < $length) {
            $open = strpos($content, '<', $index);

            if ($open === false) {
                break;
            }
            $close = strpos($content, '>', $open + 1);

            if ($close === false) {
                break;
            }
            $index = $close + 1;
            $position = $open + 1;
            $closing = $content[$position] === '/';

            if ($closing) {
                $position++;
            }
            $nameEnd = $position;

            while ($nameEnd < $close && (ctype_alpha($content[$nameEnd]) || $content[$nameEnd] === '-')) {
                $nameEnd++;
            }
            $name = strtolower(substr($content, $position, $nameEnd - $position));

            if (!in_array($name, self::DEPTH_TAGS, true)) {
                continue;
            }

            if ($closing) {
                if (($depths[$name] ?? 0) > 0) {
                    $depths[$name]--;
                }
            } elseif ($content[$close - 1] !== '/') {
                $depths[$name] = ($depths[$name] ?? 0) + 1;

                if ($depths[$name] > RenderBudget::MAX_MARKUP_DEPTH) {
                    throw new \InvalidArgumentException(sprintf(
                        'Email markup nesting exceeds the maximum depth of %d',
                        RenderBudget::MAX_MARKUP_DEPTH
                    ));
                }
            }
        }
    }

    /**
     * @param callable(array<string, string>, string): string $processor
     */
    public function processTag(string $content, string $tagName, callable $processor, ?RenderBudget $budget = null): string
    {
        $budget ??= new RenderBudget();
        $result = $content;
        $openPattern = "/<{$tagName}([^>]*)>/i";
        $closeTag = "</{$tagName}>";

        for ($iteration = 0; $iteration < RenderBudget::MAX_TAG_ITERATIONS; $iteration++) {
            $budget->chargeScanWork(strlen($result));

            if (!preg_match_all($openPattern, $result, $matches, PREG_OFFSET_CAPTURE)) {
                break;
            }

            /** @var list<array{0: int, 1: int, 2: string}> $replacements */
            $replacements = [];

            foreach ($matches[0] as $index => $fullMatch) {
                $start = $fullMatch[1];

                if ($replacements !== [] && $start < $replacements[array_key_last($replacements)][1]) {
                    continue;
                }

                $innerStart = $start + strlen($fullMatch[0]);
                $closePattern = '/<\/' . preg_quote($tagName, '/') . '>/i';
                $found = preg_match($closePattern, $result, $closeMatch, PREG_OFFSET_CAPTURE, $innerStart) === 1;
                $budget->chargeScanWork(($found ? $closeMatch[0][1] : strlen($result)) - $innerStart);

                if (!$found) {
                    continue;
                }

                $closePosition = $closeMatch[0][1];
                $inner = substr($result, $innerStart, $closePosition - $innerStart);
                $nestedOpenPattern = '/<' . preg_quote($tagName, '/') . '/i';

                if (preg_match($nestedOpenPattern, $inner)) {
                    continue;
                }

                $attributes = $this->parseAttributes($matches[1][$index][0]);
                $replacements[] = [
                    $start,
                    $closePosition + strlen($closeTag),
                    $processor($attributes, $inner),
                ];
            }

            if ($replacements === []) {
                break;
            }

            $rebuilt = '';
            $cursor = 0;

            foreach ($replacements as [$start, $end, $replacement]) {
                $rebuilt .= substr($result, $cursor, $start - $cursor) . $replacement;
                $cursor = $end;

                if (strlen($rebuilt) > RenderBudget::MAX_RENDER_OUTPUT) {
                    throw new \InvalidArgumentException(sprintf(
                        'Email markup expands beyond the maximum size of %d bytes',
                        RenderBudget::MAX_RENDER_OUTPUT
                    ));
                }
            }
            $rebuilt .= substr($result, $cursor);
            if ($rebuilt === $result) {
                break;
            }
            $result = $rebuilt;
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    public function parseAttributes(string $source): array
    {
        $attributes = [];

        if (!preg_match_all('/([\w-]+)=(?:"([^"]*)"|\'([^\']*)\')/', $source, $matches, PREG_SET_ORDER)) {
            return $attributes;
        }

        foreach ($matches as $match) {
            $doubleQuoted = $match[2] ?? '';
            $attributes[$match[1]] = $doubleQuoted !== '' ? $doubleQuoted : ($match[3] ?? '');
        }

        return $attributes;
    }

    private function isSevkDocument(string $markup): bool
    {
        return preg_match('/<(?:email|mail)[\s>]/i', $markup) === 1;
    }

    /**
     * @param array{
     *     title: string,
     *     previewText: string,
     *     styles: string,
     *     fonts: list<array{id: string, name: string, url: string}>,
     *     lang: string,
     *     dir: string
     * } $settings
     * @return array{
     *     title: string,
     *     previewText: string,
     *     styles: string,
     *     fonts: list<array{id: string, name: string, url: string}>,
     *     lang: string,
     *     dir: string
     * }
     */
    private function parseHtmlHead(string $html, array $settings): array
    {
        if (preg_match('/<title[^>]*>([\s\S]*?)<\/title>/i', $html, $match)) {
            $settings['title'] = trim($match[1]);
        }
        if (preg_match_all('/<style[^>]*>([\s\S]*?)<\/style>/i', $html, $matches)) {
            $settings['styles'] = implode("\n", $matches[1]);
        }

        preg_match_all('/<link([^>]*)>/i', $html, $links, PREG_SET_ORDER);

        foreach ($links as $link) {
            if (
                !preg_match('/href=["\']([^"\']*)["\']/i', $link[1], $href)
                || !str_contains(strtolower($href[1]), 'fonts')
            ) {
                continue;
            }
            $index = count($settings['fonts']);
            $family = preg_match('/family=([^:&]+)/', $href[1], $familyMatch) ? str_replace('+', ' ', $familyMatch[1]) : '';
            $settings['fonts'][] = [
                'id' => "font-{$index}",
                'name' => $family !== '' ? $family : 'Font ' . ($index + 1),
                'url' => $href[1],
            ];
        }

        $preview = $this->hiddenPreview($html);

        if ($preview !== null) {
            $settings['previewText'] = trim($preview[1]);
        }

        return $settings;
    }

    public function replaceSelfClosing(string $source, string $tag, bool $selfCloseOnly, callable $processor): string
    {
        $nameLength = strlen($tag) + 1;
        $openPattern = '/<' . preg_quote($tag, '/') . '/i';
        $output = '';
        $lastIndex = 0;
        $position = 0;

        while (preg_match($openPattern, $source, $opening, PREG_OFFSET_CAPTURE, $position) === 1) {
            $open = $opening[0][1];
            $closingBracket = strpos($source, '>', $open + $nameLength);

            if ($closingBracket === false) {
                break;
            }

            $position = $closingBracket + 1;

            if ($selfCloseOnly && $source[$closingBracket - 1] !== '/') {
                continue;
            }

            $attributesEnd = $selfCloseOnly ? $closingBracket - 1 : $closingBracket;
            $output .= substr($source, $lastIndex, $open - $lastIndex)
                . $processor(substr($source, $open + $nameLength, $attributesEnd - $open - $nameLength));
            $lastIndex = $closingBracket + 1;
        }

        return $output . substr($source, $lastIndex);
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function hiddenPreview(string $html): ?array
    {
        $position = 0;

        while (preg_match('/<div/i', $html, $opening, PREG_OFFSET_CAPTURE, $position) === 1) {
            $open = $opening[0][1];
            $close = strpos($html, '>', $open + 4);

            if ($close === false) {
                return null;
            }

            if (preg_match('/style=["\'][^"\']*display:\s*none/i', substr($html, $open, $close - $open + 1)) === 1) {
                if (preg_match('/<\/div>/i', $html, $closing, PREG_OFFSET_CAPTURE, $close + 1) !== 1) {
                    return null;
                }

                $end = $closing[0][1] + strlen($closing[0][0]);

                return [substr($html, $open, $end - $open), substr($html, $close + 1, $closing[0][1] - $close - 1)];
            }

            $position = $close + 1;
        }

        return null;
    }
}
