<?php

/*
 * This file is part of the OverblogDataLoaderBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\DataLoaderBundle;

use LogicException;
use Overblog\DataLoader\DataLoader;
use Overblog\DataLoader\DataLoaderInterface;
use Overblog\DataLoader\Option;
use Overblog\DataLoaderBundle\Attribute\AsDataLoader;
use Overblog\DataLoaderBundle\DependencyInjection\OverblogDataLoaderExtension;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use function sprintf;

final class OverblogDataLoaderBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new OverblogDataLoaderExtension();
    }

    public function build(ContainerBuilder $container)
    {
        $container->registerForAutoconfiguration(DataLoaderFnInterface::class)
            ->addTag('overblog.dataloader.fn');

        $container->addCompilerPass(
            new class implements CompilerPassInterface {

                private function registerDataLoader(
                    ContainerBuilder $container,
                    string $name,
                    array $config,
                    string $batchLoadFn
                ): array {
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
                                new Reference($batchLoadFn),
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
                    foreach ($container->findTaggedServiceIds('overblog_dataloader.dataloader.fn') as $id => $tags) {
                        $serviceDefinition = $container->getDefinition($id);
                        $class = $serviceDefinition->getClass();

                        $attribute = (new ReflectionClass($class))->getAttributes(AsDataLoader::class);
                        if (!$attribute) {
                            throw new LogicException(
                                'In order to use ' . DataLoaderFnInterface::class . ' you must define ' . AsDataLoader::class . ' attribute on your class'
                            );
                        }
                        $attributeArgs = $attribute[0]->getArguments();
                        $name = $attributeArgs['alias'];

                        unset($attributeArgs['alias']);
                        [, $serviceId] = $this->registerDataLoader(
                            $container,
                            $name,
                            $attributeArgs,
                            new Reference($id)
                        );
                        $container->registerAliasForArgument($serviceId, DataLoaderInterface::class, $name);
                    }
                }
            }
        );
    }
}
