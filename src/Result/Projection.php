<?php

declare(strict_types=1);

namespace Eril\Calendary\Result;

final class Projection
{
    public function __construct(
        private ?array $fields = null
    ) {}

    public function apply(array $data): array
    {
        if ($this->fields === null) {
            return $data;
        }

        return array_intersect_key(
            $data,
            array_flip($this->fields)
        );
    }
}