<?php

namespace Isholao\Http;

/**
 * @author Ishola O <ishola.tolu@outlook.com>
 */
interface HeadersInterface
{

    public function all(): array;

    public function set(string $key, $value);

    public function get(string $key, $default = null);

    public function add(string $key, $value);

    public function has(string $key): bool;

    public function remove(string $key): void;

    public function normalizeKey(string $key): string;
}
