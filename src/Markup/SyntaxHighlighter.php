<?php

declare(strict_types=1);

namespace Sevk\Markup;

use Highlight\Highlighter;

final class SyntaxHighlighter
{
    private const LANGUAGES = [
        'plain', 'extend', 'plaintext', 'text', 'txt', 'markup', 'html', 'mathml', 'svg', 'xml', 'ssml', 'atom', 'rss',
        'css', 'clike', 'javascript', 'js', 'typescript', 'ts', 'jsx', 'tsx', 'python', 'py', 'java', 'c', 'cpp',
        'csharp', 'cs', 'dotnet', 'php', 'ruby', 'rb', 'go', 'rust', 'sql', 'bash', 'sh', 'shell', 'json',
        'webmanifest', 'yaml', 'yml', 'markdown', 'md',
    ];

    private const ALIASES = ['jsx' => 'javascript', 'tsx' => 'typescript'];

    private const GRAMMARS = [
        'markup' => 'xml', 'html' => 'xml', 'mathml' => 'xml', 'svg' => 'xml', 'ssml' => 'xml', 'atom' => 'xml',
        'rss' => 'xml', 'dotnet' => 'cs', 'webmanifest' => 'json', 'shell' => 'bash',
    ];

    private const PLAIN_LANGUAGES = ['plain', 'plaintext', 'text', 'txt', 'clike'];

    private const TOKENS = [
        'keyword' => 'keyword', 'built_in' => 'builtin', 'type' => 'class-name', 'literal' => 'boolean',
        'number' => 'number', 'string' => 'string', 'subst' => 'variable', 'symbol' => 'symbol',
        'class' => 'class-name', 'function' => 'function', 'title' => 'function', 'params' => 'variable',
        'comment' => 'comment', 'doctag' => 'comment', 'meta' => 'keyword', 'meta-keyword' => 'keyword',
        'meta-string' => 'string', 'section' => 'tag', 'tag' => 'tag', 'name' => 'tag', 'attr' => 'attr-name',
        'attribute' => 'attr-name', 'variable' => 'variable', 'template-variable' => 'variable',
        'template-tag' => 'tag', 'regexp' => 'regex', 'link' => 'url', 'selector-tag' => 'selector',
        'selector-id' => 'selector', 'selector-class' => 'selector', 'selector-attr' => 'selector',
        'selector-pseudo' => 'selector', 'property' => 'property', 'punctuation' => 'punctuation',
        'operator' => 'operator', 'addition' => 'inserted', 'deletion' => 'deleted', 'formula' => 'number',
        'quote' => 'comment', 'emphasis' => 'italic', 'strong' => 'bold', 'bullet' => 'punctuation',
        'code' => 'string',
    ];

    private const SPACE = "\u{00A0}\u{200D}\u{200B}";

    public function __construct(private readonly StyleSerializer $styles)
    {
    }

    /**
     * @param array<string, string> $attributes
     */
    public function render(array $attributes, string $code, RenderBudget $budget): string
    {
        $requested = strtolower(($attributes['language'] ?? '') !== '' ? $attributes['language'] : 'javascript');
        $language = self::ALIASES[$requested] ?? $requested;
        $theme = CodeBlockThemes::resolve(($attributes['theme'] ?? '') !== '' ? $attributes['theme'] : 'oneDark');
        $customStyle = $this->styles->extract($attributes);

        if ($code === '') {
            return '<pre><code></code></pre>';
        }

        $length = $this->utf16Length($code);

        if (!in_array($language, self::LANGUAGES, true) || !$budget->admits($length)) {
            return sprintf(
                '<pre style="%s"><code>%s</code></pre>',
                $this->preStyle(CodeBlockThemes::base($theme), ['width' => '100%'], $customStyle),
                str_replace(['<', '>'], ['&lt;', '&gt;'], $code)
            );
        }
        $budget->charge($length);

        $inherited = ($attributes['font-family'] ?? '') !== '' ? ['font-family' => $attributes['font-family']] : [];
        $lines = $this->highlightLines($code, $language, $theme, $inherited);
        $numbered = ($attributes['line-numbers'] ?? '') === 'true';
        $html = '';

        foreach ($lines as $index => $line) {
            $number = $numbered
                ? sprintf('<span style="width:2em;display:inline-block">%d</span>', $index + 1)
                : '';
            $html .= sprintf('<p style="margin:0;min-height:1em">%s%s</p>', $number, $line);
        }

        return sprintf(
            '<pre style="%s"><code>%s</code></pre>',
            $this->preStyle(CodeBlockThemes::base($theme), ['width' => '100%', 'box-sizing' => 'border-box'], $customStyle),
            $html
        );
    }

    /**
     * @param array<string, string> ...$layers
     */
    private function preStyle(array ...$layers): string
    {
        $style = [];

        foreach ($layers as $layer) {
            foreach ($layer as $name => $value) {
                $style[$name] = $value;
            }
        }
        $parts = [];

        foreach ($style as $name => $value) {
            $property = strtolower(preg_replace('/[A-Z]/', '-$0', (string) $name) ?? (string) $name);
            $parts[] = (str_starts_with($property, 'ms-') ? '-' : '') . "{$property}:{$value}";
        }

        return implode(';', $parts);
    }

    /**
     * @param array<string, string> $inherited
     * @return list<string>
     */
    private function highlightLines(string $code, string $language, string $theme, array $inherited): array
    {
        $segments = $this->tokenize($code, $language);
        $lines = [''];
        $open = [];
        $inheritedStyle = $this->styles->serialize($inherited);

        foreach ($segments as [$kind, $value]) {
            if ($kind === 'open') {
                $style = $this->tokenStyle($theme, $value, $inherited);
                $tag = $style === null ? '' : sprintf('<span style="%s">', $style);
                $open[] = $tag;
                $lines[array_key_last($lines)] .= $tag;
                continue;
            }
            if ($kind === 'close') {
                $tag = array_pop($open);
                $lines[array_key_last($lines)] .= $tag === '' ? '' : '</span>';
                continue;
            }

            $wrap = $inheritedStyle !== '' && !in_array(true, array_map(static fn (string $tag): bool => $tag !== '', $open), true);

            foreach (explode("\n", $value) as $index => $part) {
                if ($index > 0) {
                    $lines[array_key_last($lines)] .= str_repeat('</span>', count(array_filter($open)));
                    $lines[] = implode('', $open);
                }
                if ($part === '') {
                    continue;
                }
                $text = str_replace(['"', "'", ' '], ['&quot;', '&#39;', self::SPACE], $part);
                $lines[array_key_last($lines)] .= $wrap ? sprintf('<span style="%s">%s</span>', $inheritedStyle, $text) : $text;
            }
        }

        return $lines;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function tokenize(string $code, string $language): array
    {
        $escaped = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $code);

        if (in_array($language, self::PLAIN_LANGUAGES, true)) {
            return [['text', $escaped]];
        }

        try {
            $highlighted = (new Highlighter())->highlight(self::GRAMMARS[$language] ?? $language, $code)->value;
        } catch (\Throwable) {
            return [['text', $escaped]];
        }

        $segments = [];
        $pieces = preg_split('/(<span class="[^"]*">|<\/span>)/', $highlighted, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($pieces as $piece) {
            if ($piece === '</span>') {
                $segments[] = ['close', ''];
            } elseif (preg_match('/^<span class="([^"]*)">$/', $piece, $match)) {
                $segments[] = ['open', $match[1]];
            } else {
                $segments[] = ['text', $piece];
            }
        }

        return $segments;
    }

    /**
     * @param array<string, string> $inherited
     */
    private function tokenStyle(string $theme, string $classes, array $inherited): ?string
    {
        $style = $inherited;
        $matched = false;

        foreach (preg_split('/\s+/', trim($classes)) ?: [] as $class) {
            $token = self::TOKENS[str_starts_with($class, 'hljs-') ? substr($class, 5) : $class] ?? null;

            if ($token === null) {
                continue;
            }
            $matched = true;
            $style = array_merge($style, CodeBlockThemes::token($theme, $token));
        }

        return $matched ? $this->styles->serialize($style) : null;
    }

    private function utf16Length(string $code): int
    {
        return mb_strlen($code, 'UTF-8') + (int) preg_match_all('/[\x{10000}-\x{10FFFF}]/u', $code);
    }
}
