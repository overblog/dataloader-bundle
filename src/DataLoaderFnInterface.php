<?php

declare(strict_types=1);

namespace Overblog\DataLoaderBundle;

use Overblog\PromiseAdapter\PromiseAdapterInterface;

/**
 * @template K
 * @template T
 */
interface DataLoaderFnInterface
{
    /**
     * @param array<K> $keys
     * @return PromiseAdapterInterface<array<T>>
     */
    public function __invoke(array $keys): mixed;
}
