<?php

declare(strict_types=1);

namespace Eril\Calendary\Contracts;

use JsonSerializable;

/**
 * Represents a serializable Calendary result.
 *
 * Result objects provide a structured array representation and can be
 * serialized directly to JSON.
 */
interface Result extends JsonSerializable
{
    /**
     * Convert the result to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Convert the result to JSON.
     *
     * @param int $flags JSON encoding flags.
     *
     * @return string
     *
     * @throws \JsonException If the result cannot be encoded.
     */
    public function toJson(int $flags = 0): string;
}