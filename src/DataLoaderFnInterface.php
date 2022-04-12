<?php

declare(strict_types=1);

namespace Overblog\DataLoaderBundle;

/**
 * @template K
 * @template T
 */
interface DataLoaderFnInterface
{
    /**
     * @param array<K> $keys
     * @return T
     */
    public function __invoke(array $keys): mixed;
}
