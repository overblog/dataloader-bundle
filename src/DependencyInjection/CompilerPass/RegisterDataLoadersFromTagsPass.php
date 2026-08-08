<?php

/*
 * This file is part of the OverblogDataLoaderBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\DataLoaderBundle\DependencyInjection\CompilerPass;

use Overblog\DataLoader\DataLoader;
use Overblog\DataLoader\Option;
use Overblog\DataLoaderBundle\DependencyInjection\Support;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterDataLoadersFromTagsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('overblog.dataloader') as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                $batchLoadFn = isset($attributes['method'])
                    ? [new Reference($serviceId), $attributes['method']]
                    : Support::buildCallableFromScalar($attributes['batch_load_fn']);

                $this->registerDataLoader($container, $attributes, $batchLoadFn);
            }
        }
    }

    private function registerDataLoader(ContainerBuilder $container, array $loaderConfig, mixed $batchLoadFn): void
    {
        $dataLoaderServiceID = Support::generateDataLoaderServiceIDFromName($loaderConfig['name'], $container);
        $optionServiceID = Support::generateDataLoaderOptionServiceIDFromName($loaderConfig['name'], $container);

        $container->register($optionServiceID, Option::class)
            ->setPublic(false)
            ->setArguments([Support::buildOptionsParams($loaderConfig['options'])]);

        $definition = $container->register($dataLoaderServiceID, DataLoader::class)
            ->setPublic(true)
            ->addTag('kernel.reset', ['method' => 'clearAll'])
            ->setArguments([
                $batchLoadFn,
                new Reference($loaderConfig['promise_adapter']),
                new Reference($optionServiceID),
            ]);

        if (isset($loaderConfig['factory'])) {
            $definition->setFactory(Support::buildCallableFromScalar($loaderConfig['factory']));
        }

        if (isset($loaderConfig['alias'])) {
            $container
                ->setAlias($loaderConfig['alias'], $dataLoaderServiceID)
                ->setPublic(true);
        }
    }
}
