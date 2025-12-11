<?php

declare(strict_types=1);

namespace Sevk\Markup;

/**
 * Render Sevk markup to email-compatible HTML
 *
 * @param string $markup The Sevk markup to render
 * @return string The rendered HTML
 */
function render(string $markup): string
{
    $renderer = new Renderer();
    return $renderer->render($markup);
}
