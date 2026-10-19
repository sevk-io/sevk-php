<?php

declare(strict_types=1);

namespace Sevk\Tests;

use PHPUnit\Framework\TestCase;

use function Sevk\Markup\render;

final class RendererTest extends TestCase
{
    public function testRendersMarkupIntoAnHtmlEmailDocument(): void
    {
        $html = render('<section><paragraph>Hello world</paragraph></section>');

        self::assertStringStartsWith('<!DOCTYPE html PUBLIC', $html);
        self::assertStringContainsString('Hello world', $html);
        self::assertStringNotContainsString('<paragraph>', $html);
    }
}
