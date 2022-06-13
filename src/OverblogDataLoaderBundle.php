<?php

/*
 * This file is part of the OverblogDataLoaderBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Overblog\DataLoaderBundle;

use LogicException;
use Overblog\DataLoader\DataLoader;
use Overblog\DataLoader\DataLoaderInterface;
use Overblog\DataLoader\Option;
use Overblog\DataLoaderBundle\Attribute\AsDataLoader;
use Overblog\DataLoaderBundle\DependencyInjection\OverblogDataLoaderExtension;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use function lcfirst;
use function sprintf;

final class OverblogDataLoaderBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new OverblogDataLoaderExtension();
    }

    public function build(ContainerBuilder $container)
    {
        $container->registerAttributeForAutoconfiguration(
            AsDataLoader::class,
            static function (ChildDefinition $definition, AsDataLoader $attribute, \ReflectionClass $reflector): void {
                if (!$reflector->implementsInterface(DataLoaderFnInterface::class)) {
                    throw new LogicException(sprintf('Please implement %s', DataLoaderFnInterface::class));
                }

                $definition->addTag('overblog.dataloader', [
                        'alias' => $attribute->alias ?? lcfirst($reflector->getShortName()),
                        'maxBatchSize' => $attribute->maxBatchSize,
                        'batch' => $attribute->batch,
                        'cache' => $attribute->cache,
                        'cacheMap' => $attribute->cacheMap,
                        'cacheKeyFn' => $attribute->cacheKeyFn,
                    ]
                );
            }
        );

        $container->addCompilerPass(
            new class implements CompilerPassInterface {
                private function registerDataLoader(
                    ContainerBuilder $container,
                    string $name,
                    array $config,
                    string $batchLoadFn
                ): array {
                    $dataLoaderRef = new Reference($batchLoadFn);

                    if (isset($config['cacheMap'])) {
                        $config['cacheMap'] = [$dataLoaderRef, $config['cacheMap']];
                    }
                    if (isset($config['cacheKeyFn'])) {
                        $config['cacheKeyFn'] = [$dataLoaderRef, $config['cacheKeyFn']];
                    }

                    $id = $this->generateDataLoaderServiceIDFromName($name, $container);
                    $OptionServiceID = $this->generateDataLoaderOptionServiceIDFromName($name, $container);
                    $container->register($OptionServiceID, Option::class)
                        ->setPublic(false)
                        ->setArguments([$config]);

                    return [
                        $container->register($id, DataLoader::class)
                            ->setPublic(true)
                            ->addTag('kernel.reset', ['method' => 'clearAll'])
                            ->setArguments([
                                $dataLoaderRef,
                                new Reference('overblog_dataloader.webonyx_graphql_sync_promise_adapter'),
                                new Reference($OptionServiceID),
                            ]),
                        $id,
                    ];
                }

                private function generateDataLoaderOptionServiceIDFromName($name, ContainerBuilder $container): string
                {
                    return sprintf('%s_option', $this->generateDataLoaderServiceIDFromName($name, $container));
                }

                private function generateDataLoaderServiceIDFromName($name, ContainerBuilder $container): string
                {
                    return sprintf('overblog_dataloader.%s_loader', $container::underscore($name));
                }

                public function process(ContainerBuilder $container)
                {
                    foreach ($container->findTaggedServiceIds('overblog.dataloader') as $id => $tags) {
                        foreach ($tags as $attrs) {
                            $name = $attrs['alias'];
                            unset($attrs['alias']);

                            [, $serviceId] = $this->registerDataLoader(
                                $container,
                                $name,
                                $attrs,
                                $id
                            );
                            $container->registerAliasForArgument($serviceId, DataLoaderInterface::class, $name);
                        }
                    }
                }
            }
        );
    }
}
