<?php

declare(strict_types=1);

namespace Overblog\DataLoaderBundle;

/**
 * @template K
 * @template T
 * @template P
 */
interface DataLoaderFnInterface
{
    /**
     * @param array<K> $keys
     * @return P<array<T>>
     */
    public function __invoke(array $keys): mixed;
}
