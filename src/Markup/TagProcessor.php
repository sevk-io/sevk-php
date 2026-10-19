<?php

declare(strict_types=1);

namespace Sevk\Markup;

final class TagProcessor
{
    public function __construct(
        private readonly MarkupParser $parser,
        private readonly LayoutTagRenderer $layout,
        private readonly ContentTagRenderer $content
    ) {
    }

    /**
     * @return array{content: string, gapRules: list<string>}
     */
    public function process(string $markup): array
    {
        $this->layout->reset();
        $this->parser->assertDepth($markup);
        $budget = new RenderBudget();

        $result = $this->tag($markup, 'block', $this->content->block(...), $budget);
        $result = $this->parser->replaceSelfClosing(
            $result,
            'block',
            true,
            fn (string $attributes): string => $this->content->block($this->parser->parseAttributes($attributes))
        );
        $this->parser->assertDepth($result);

        $result = preg_replace('/<link(\s+(?:[\w-]+=(?:"[^"]*"|\'[^\']*\')\s+)*)href=/i', '<sevk-link${1}href=', $result) ?? $result;
        $result = str_ireplace('</link>', '</sevk-link>', $result);

        $result = $this->tag($result, 'section', $this->layout->section(...), $budget);
        $result = $this->tag($result, 'column', $this->layout->column(...), $budget);
        $result = $this->tag($result, 'row', $this->layout->row(...), $budget);
        $result = $this->tag($result, 'container', $this->layout->container(...), $budget);
        $result = $this->tag($result, 'heading', $this->content->heading(...), $budget);
        $result = $this->tag($result, 'paragraph', $this->content->paragraph(...), $budget);
        $result = $this->tag($result, 'text', $this->content->text(...), $budget);
        $result = $this->tag($result, 'button', $this->content->button(...), $budget);

        $result = preg_replace_callback(
            '/<image([^>]*)\/?>/i',
            fn (array $match): string => $this->content->image($this->parser->parseAttributes($match[1])),
            $result
        ) ?? $result;
        $result = preg_replace_callback(
            '/<divider([^>]*)\/?>/i',
            fn (array $match): string => $this->content->divider($this->parser->parseAttributes($match[1])),
            $result
        ) ?? $result;
        $result = preg_replace(['/<\/divider>/i', '/<\/image>/i'], '', $result) ?? $result;

        $result = $this->tag($result, 'sevk-link', $this->content->link(...), $budget);
        $result = $this->tag($result, 'list', $this->content->list(...), $budget);
        $result = $this->tag($result, 'li', $this->content->listItem(...), $budget);
        $result = $this->tag(
            $result,
            'codeblock',
            fn (array $attributes, string $inner): string => $this->content->codeBlock($attributes, $inner, $budget),
            $budget
        );

        $result = str_ireplace([
            '</container>', '</section>', '</row>', '</column>',
            '</heading>', '</paragraph>', '</text>', '</button>', '</sevk-link>',
        ], '', $result);
        $result = preg_replace([
            '/<sevk-email[^>]*>/i', '/<\/sevk-email>/i',
            '/<sevk-body[^>]*>/i', '/<\/sevk-body>/i',
            '/<email[^>]*>/i', '/<\/email>/i',
            '/<mail[^>]*>/i', '/<\/mail>/i',
            '/<body[^>]*>/i', '/<\/body>/i',
        ], '', $result) ?? $result;

        return [
            'content' => trim($result),
            'gapRules' => $this->layout->gapRules(),
        ];
    }

    /**
     * @param callable(array<string, string>, string): string $renderer
     */
    private function tag(string $markup, string $name, callable $renderer, RenderBudget $budget): string
    {
        return $this->parser->processTag($markup, $name, $renderer, $budget);
    }
}
