<?php

declare(strict_types=1);

namespace Sevk\Markup;

/**
 * @param array{title?: string, previewText?: string, styles?: string, fonts?: list<array{id: string, name: string, url: string}>, lang?: string, dir?: string} $headSettings
 */
function render(string $markup, array $headSettings = []): string
{
    $renderer = new Renderer();

    return $renderer->render($markup, $headSettings);
}
