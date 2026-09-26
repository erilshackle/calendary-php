<?php

declare(strict_types=1);

namespace Eril\Calendary\Contracts;

use JsonSerializable;

interface Result extends JsonSerializable
{
    public function toArray(): array;

    public function toJson(int $flags = 0): string;
}