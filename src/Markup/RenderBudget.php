<?php

declare(strict_types=1);

namespace Sevk\Markup;

final class RenderBudget
{
    public const MAX_MARKUP_DEPTH = 64;

    public const MAX_TAG_ITERATIONS = 200;

    public const MAX_RENDER_OUTPUT = 5000000;

    public const MAX_TAG_SCAN_WORK = 30000000;

    public const MAX_BLOCK_LENGTH = 2500;

    public const MAX_TOTAL_LENGTH = 2500;

    public const TOO_COMPLEX = 'Email markup is too complex to render';

    private int $scanWork = 0;

    private int $highlighted = 0;

    public function chargeScanWork(int $work): void
    {
        $this->scanWork += $work;

        if ($this->scanWork > self::MAX_TAG_SCAN_WORK) {
            throw new \InvalidArgumentException(self::TOO_COMPLEX);
        }
    }

    public function admits(int $length): bool
    {
        return $length <= self::MAX_BLOCK_LENGTH && $this->highlighted + $length <= self::MAX_TOTAL_LENGTH;
    }

    public function charge(int $length): void
    {
        $this->highlighted += $length;
    }
}
