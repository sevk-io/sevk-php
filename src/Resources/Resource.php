<?php

declare(strict_types=1);

namespace Sevk\Resources;

use Sevk\Transport\Transport;

abstract class Resource
{
    public function __construct(protected readonly Transport $client)
    {
    }
}
