<?php

declare(strict_types=1);

namespace Sevk\Markup;

final class ContentTagRenderer
{
    public function __construct(
        private readonly StyleSerializer $styles,
        private readonly ButtonRenderer $buttons,
        private readonly SyntaxHighlighter $syntax,
        private readonly TemplateEngine $templates
    ) {
    }

    /**
     * @param array<string, string> $attributes
     */
    public function block(array $attributes, string $inner = ''): string
    {
        return $this->templates->renderBlock($attributes, $inner);
    }

    /**
     * @param array<string, string> $attributes
     */
    public function heading(array $attributes, string $inner): string
    {
        $level = ($attributes['level'] ?? '') !== '' ? $attributes['level'] : '1';
        $style = $this->styles->extract($attributes);
        $style['margin'] ??= '0';

        return sprintf(
            '<h%s style="%s">%s</h%s>',
            $level,
            $this->styles->serialize($style),
            $inner,
            $level
        );
    }

    /**
     * @param array<string, string> $attributes
     */
    public function paragraph(array $attributes, string $inner): string
    {
        $style = $this->styles->extract($attributes);
        $style['margin'] ??= '0';

        return sprintf('<p style="%s">%s</p>', $this->styles->serialize($style), $inner);
    }

    /**
     * @param array<string, string> $attributes
     */
    public function text(array $attributes, string $inner): string
    {
        return sprintf(
            '<span style="%s">%s</span>',
            $this->styles->serialize($this->styles->extract($attributes)),
            $inner
        );
    }

    /**
     * @param array<string, string> $attributes
     */
    public function button(array $attributes, string $inner): string
    {
        return $this->buttons->render($attributes, $inner);
    }

    /**
     * @param array<string, string> $attributes
     */
    public function image(array $attributes): string
    {
        $source = $attributes['src'] ?? '';
        $alt = $attributes['alt'] ?? '';
        $width = $attributes['width'] ?? null;
        $height = $attributes['height'] ?? null;
        $style = $this->styles->extract($attributes);
        $style['vertical-align'] ??= 'middle';
        $style['max-width'] ??= '100%';

        $widthValue = $width !== null ? (preg_replace('/px/', '', $width, 1) ?? $width) : '';
        $heightValue = $height !== null ? (preg_replace('/px/', '', $height, 1) ?? $height) : '';
        $widthAttribute = $widthValue !== '' ? sprintf(' width="%s"', $widthValue) : '';
        $heightAttribute = $heightValue !== '' ? sprintf(' height="%s"', $heightValue) : '';

        return sprintf(
            '<img src="%s" alt="%s"%s%s style="%s" />',
            $source,
            $alt,
            $widthAttribute,
            $heightAttribute,
            $this->styles->serialize($style)
        );
    }

    /**
     * @param array<string, string> $attributes
     */
    public function divider(array $attributes): string
    {
        $classAttribute = $this->classAttribute($attributes);

        return sprintf(
            '<hr style="%s"%s />',
            $this->styles->serialize($this->styles->extract($attributes)),
            $classAttribute
        );
    }

    /**
     * @param array<string, string> $attributes
     */
    public function link(array $attributes, string $inner): string
    {
        return sprintf(
            '<a href="%s" target="%s" style="%s">%s</a>',
            ($attributes['href'] ?? '') !== '' ? $attributes['href'] : '#',
            ($attributes['target'] ?? '') !== '' ? $attributes['target'] : '_blank',
            $this->styles->serialize($this->styles->extract($attributes)),
            $inner
        );
    }

    /**
     * @param array<string, string> $attributes
     */
    public function list(array $attributes, string $inner): string
    {
        $tag = ($attributes['type'] ?? 'unordered') === 'ordered' ? 'ol' : 'ul';
        $style = $this->styles->extract($attributes);
        $style['margin'] ??= '0';
        if (isset($attributes['list-style-type'])) {
            $style['list-style-type'] = $attributes['list-style-type'];
        }
        $classAttribute = $this->classAttribute($attributes);

        return sprintf(
            '<%s style="%s"%s>%s</%s>',
            $tag,
            $this->styles->serialize($style),
            $classAttribute,
            $inner,
            $tag
        );
    }

    /**
     * @param array<string, string> $attributes
     */
    public function listItem(array $attributes, string $inner): string
    {
        $classAttribute = $this->classAttribute($attributes);

        return sprintf(
            '<li style="%s"%s>%s</li>',
            $this->styles->serialize($this->styles->extract($attributes)),
            $classAttribute,
            $inner
        );
    }

    /**
     * @param array<string, string> $attributes
     */
    public function codeBlock(array $attributes, string $inner, RenderBudget $budget): string
    {
        return $this->syntax->render($attributes, $inner, $budget);
    }

    /**
     * @param array<string, string> $attributes
     */
    private function classAttribute(array $attributes): string
    {
        $class = ($attributes['class'] ?? '') !== '' ? $attributes['class'] : ($attributes['className'] ?? '');

        return $class !== '' ? sprintf(' class="%s"', $class) : '';
    }
}
