<?php

declare(strict_types=1);

namespace Sevk\Markup;

final class Renderer
{
    private readonly MarkupParser $parser;
    private readonly TagProcessor $tags;
    private readonly DocumentRenderer $document;

    public function __construct()
    {
        $this->parser = new MarkupParser();
        $styles = new StyleSerializer();
        $layout = new LayoutTagRenderer($styles);
        $content = new ContentTagRenderer(
            $styles,
            new ButtonRenderer($styles),
            new SyntaxHighlighter($styles),
            new TemplateEngine()
        );
        $this->tags = new TagProcessor($this->parser, $layout, $content);
        $this->document = new DocumentRenderer();
    }

    /**
     * @param array{title?: string, previewText?: string, styles?: string, fonts?: list<array{id: string, name: string, url: string}>, lang?: string, dir?: string} $headSettings
     */
    public function render(string $markup, array $headSettings = []): string
    {
        $head = array_replace($this->parser->parseHead($markup), $headSettings);
        $body = $this->parser->extractBody($markup);
        $processed = $this->tags->process($this->parser->normalize($body));

        return $this->document->render($processed['content'], $head, $processed['gapRules']);
    }
}
