<?php

declare(strict_types=1);

namespace Sevk\Transport;

interface Transport
{
    public function get(string $path, ?array $query = null): array;

    public function post(string $path, ?array $data = null, ?array $headers = null): array;

    public function put(string $path, ?array $data = null): array;

    public function patch(string $path, ?array $data = null): array;

    public function delete(string $path): array;
}
