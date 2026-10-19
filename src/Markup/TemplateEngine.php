<?php

declare(strict_types=1);

namespace Sevk\Markup;

final class TemplateEngine
{
    private const MAX_IF_PASSES = 2000;

    private const MAX_EACH_ITEMS = 1000;

    private const MAX_EACH_OUTPUT = 2000000;

    private const MAX_TEMPLATE_WORK = 20000000;

    private const OUTPUT_EXCEEDED = 'Email block exceeds the maximum template output size';

    private int $rendered = 0;

    /**
     * @param array<string, mixed> $attributes
     */
    public function renderBlock(array $attributes, string $inner = ''): string
    {
        $template = trim($inner);

        if ($template === '') {
            $template = $attributes['template'] ?? '';
        }

        if (!is_string($template) || $template === '') {
            return '';
        }

        $encodedConfig = $attributes['config'] ?? '{}';

        if (!is_string($encodedConfig)) {
            $encodedConfig = '{}';
        }
        $encodedConfig = preg_replace_callback(
            '/&(?:amp|quot|apos|lt|gt|#39|#x27|#34|#x22);/i',
            static fn (array $match): string => match (strtolower($match[0])) {
                '&amp;' => '&',
                '&quot;', '&#34;', '&#x22;' => '"',
                '&apos;', '&#39;', '&#x27;' => "'",
                '&lt;' => '<',
                '&gt;' => '>',
                default => $match[0],
            },
            $encodedConfig
        ) ?? $encodedConfig;
        $config = json_decode($encodedConfig);
        if (!$config instanceof \stdClass) {
            $config = json_decode(str_replace("'", '"', $encodedConfig));
        }

        return $this->render($template, $config instanceof \stdClass ? get_object_vars($config) : []);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function render(string $template, array $config): string
    {
        $this->rendered = 0;
        $result = $this->renderConditionals($template, $config);
        $result = $this->renderLoops($result, $config);

        $result = preg_replace_callback(
            '/\{%(\w+)\s*\?\?([^%]+)%\}/',
            function (array $match) use ($config): string {
                $value = $config[$match[1]] ?? null;

                if ($value !== null && $value !== '') {
                    return $this->charge($this->stringify($value));
                }

                return $this->charge(trim($match[2]));
            },
            $result
        ) ?? $result;

        return preg_replace_callback(
            '/\{%(\w+)%\}/',
            fn (array $match): string => $this->charge($this->stringify($config[$match[1]] ?? null)),
            $result
        ) ?? $result;
    }

    private function charge(string $value): string
    {
        if ($this->rendered + strlen($value) > self::MAX_EACH_OUTPUT) {
            throw new \InvalidArgumentException(self::OUTPUT_EXCEEDED);
        }
        $this->rendered += strlen($value);

        return $value;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function renderConditionals(string $template, array $config): string
    {
        $result = $template;
        $pattern = '/\{%#if\s+([^%]+)%\}((?:(?!\{%#if\s)[\s\S])*?)\{%\/if%\}/';
        $work = 0;

        for ($pass = 0; $pass < self::MAX_IF_PASSES; $pass++) {
            $work += strlen($result);

            if ($work > self::MAX_TEMPLATE_WORK) {
                throw new \InvalidArgumentException(RenderBudget::TOO_COMPLEX);
            }
            $replaced = preg_replace_callback(
                $pattern,
                function (array $match) use ($config): string {
                    $elseIndex = strpos($match[2], '{%else%}');

                    if ($elseIndex === false) {
                        return $this->evaluateCondition($match[1], $config) ? $match[2] : '';
                    }

                    return $this->evaluateCondition($match[1], $config)
                        ? substr($match[2], 0, $elseIndex)
                        : substr($match[2], $elseIndex + 8);
                },
                $result,
                1
            ) ?? $result;

            if ($replaced === $result) {
                break;
            }
            $result = $replaced;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function renderLoops(string $template, array $config): string
    {
        if (!str_contains($template, '{%/each%}')) {
            return $template;
        }

        $output = '';
        $from = 0;
        $length = strlen($template);
        $work = 0;

        while ($from <= $length) {
            if (!preg_match('/\{%#each\s+(\w+)(?:\s+as\s+(\w+))?%\}/', $template, $open, PREG_OFFSET_CAPTURE, $from)) {
                $output .= substr($template, $from);
                break;
            }

            $bodyStart = $open[0][1] + strlen($open[0][0]);
            $closeIndex = strpos($template, '{%/each%}', $bodyStart);

            if ($closeIndex === false) {
                $output .= substr($template, $from);
                break;
            }

            $output .= substr($template, $from, $open[0][1] - $from);
            $items = $config[$open[1][0]] ?? null;

            if (is_array($items) && $items !== []) {
                if (count($items) > self::MAX_EACH_ITEMS) {
                    throw new \InvalidArgumentException(sprintf('Email block exceeds the maximum of %d loop items', self::MAX_EACH_ITEMS));
                }

                $alias = preg_match('/\sas\s+(\w+)%\}$/', $open[0][0], $aliasMatch) ? $aliasMatch[1] : 'this';
                $body = substr($template, $bodyStart, $closeIndex - $bodyStart);
                $propertyPattern = '/\{%' . preg_quote($alias, '/') . '\.(\w+)%\}/';

                foreach ($items as $item) {
                    $work += strlen($body) + 16;

                    if ($work > self::MAX_TEMPLATE_WORK) {
                        throw new \InvalidArgumentException(RenderBudget::TOO_COMPLEX);
                    }
                    $output .= $this->renderItem($body, $item, $config, $propertyPattern);
                }
            }

            $from = $closeIndex + 9;
        }

        return $output;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function renderItem(string $body, mixed $item, array $config, string $propertyPattern): string
    {
        $itemOut = 0;
        $charge = function (string $value) use (&$itemOut): string {
            if ($this->rendered + $itemOut + strlen($value) > self::MAX_EACH_OUTPUT) {
                throw new \InvalidArgumentException(self::OUTPUT_EXCEEDED);
            }
            $itemOut += strlen($value);

            return $value;
        };

        $rendered = preg_replace_callback(
            $propertyPattern,
            fn (array $match): string => $charge($this->stringify($this->itemProperty($item, $match[1]))),
            $body
        ) ?? $body;
        $rendered = preg_replace_callback(
            '/\{%(\w+)%\}/',
            fn (array $match): string => $charge($this->stringify($config[$match[1]] ?? null)),
            $rendered
        ) ?? $rendered;

        $this->rendered += strlen($rendered);

        if ($this->rendered > self::MAX_EACH_OUTPUT) {
            throw new \InvalidArgumentException(self::OUTPUT_EXCEEDED);
        }

        return $rendered;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function evaluateCondition(string $expression, array $config): bool
    {
        $expression = trim($expression);

        if (str_contains($expression, '||')) {
            foreach (explode('||', $expression) as $part) {
                if ($this->evaluateCondition($part, $config)) {
                    return true;
                }
            }

            return false;
        }

        if (str_contains($expression, '&&')) {
            foreach (explode('&&', $expression) as $part) {
                if (!$this->evaluateCondition($part, $config)) {
                    return false;
                }
            }

            return true;
        }

        if (preg_match('/^(\w+)\s*==\s*"([^"]*)"$/', $expression, $match)) {
            return $this->stringify($config[$match[1]] ?? null) === $match[2];
        }
        if (preg_match('/^(\w+)\s*!=\s*"([^"]*)"$/', $expression, $match)) {
            return $this->stringify($config[$match[1]] ?? null) !== $match[2];
        }

        return $this->isTruthy($config[$expression] ?? null);
    }

    private function itemProperty(mixed $item, string $name): mixed
    {
        if ($item instanceof \stdClass) {
            return get_object_vars($item)[$name] ?? null;
        }
        if ($name !== 'length') {
            return null;
        }
        if (is_string($item)) {
            return preg_match_all('/./su', $item) + preg_match_all('/[\x{10000}-\x{10FFFF}]/u', $item);
        }

        return is_array($item) ? count($item) : null;
    }


    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_int($value) || is_float($value)) {
            return NumberFormatter::format((float) $value);
        }
        if ($value instanceof \stdClass) {
            return '[object Object]';
        }
        if (is_array($value)) {
            return implode(',', array_map(
                fn (mixed $item): string => $item instanceof \stdClass ? '[object Object]' : $this->stringify($item),
                $value
            ));
        }

        return (string) $value;
    }

    private function isTruthy(mixed $value): bool
    {
        if ($value === null || $value === '' || $value === false || $value === 0 || $value === 0.0) {
            return false;
        }

        return !is_array($value) || $value !== [];
    }
}
