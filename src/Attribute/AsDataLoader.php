<?php

declare(strict_types=1);

namespace Overblog\DataLoaderBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsDataLoader
{
    public function __construct(
        public readonly ?string $alias = null,
        public readonly ?int $maxBatchSize = null,
        public readonly ?bool $batch = null,
        public readonly ?bool $cache = null,
        public readonly ?string $cacheKeyFn = null,
    ) {
    }
}
