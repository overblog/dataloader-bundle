<?php

/*
 * This file is part of the OverblogDataLoaderBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\DataLoaderBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/** @internal */
class Support
{
    private static string $alias = 'overblog_dataloader';

    public static function getAlias(): string
    {
        return self::$alias;
    }

    public static function generateDataLoaderServiceIDFromName(string $name, ContainerBuilder $container): string
    {
        return \sprintf('%s.%s_loader', static::$alias, $container::underscore($name));
    }

    public static function generateDataLoaderOptionServiceIDFromName(string $name, ContainerBuilder $container): string
    {
        return \sprintf('%s_option', static::generateDataLoaderServiceIDFromName($name, $container));
    }

    public static function buildCallableFromScalar($scalar): mixed
    {
        $matches = null;

        if (null === $scalar) {
            return null;
        }

        if (preg_match(Configuration::SERVICE_CALLABLE_NOTATION_REGEX, $scalar, $matches)) {
            $function = new Reference($matches['service_id']);
            if (empty($matches['method'])) {
                return $function;
            } else {
                return [$function, $matches['method']];
            }
        } elseif (preg_match(Configuration::PHP_CALLABLE_NOTATION_REGEX, $scalar, $matches)) {
            $function = $matches['function'];
            if (empty($matches['method'])) {
                return $function;
            } else {
                return [$function, $matches['method']];
            }
        }

        return null;
    }

    public static function buildOptionsParams(array $options): array
    {
        $optionsParams = [];

        $optionsParams['batch'] = $options['batch'];
        $optionsParams['cache'] = $options['cache'];
        $optionsParams['maxBatchSize'] = $options['max_batch_size'];
        $optionsParams['cacheMap'] = new Reference($options['cache_map']);
        $optionsParams['cacheKeyFn'] = self::buildCallableFromScalar($options['cache_key_fn']);

        return $optionsParams;
    }
}
