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

use Overblog\DataLoader\DataLoaderInterface;
use Overblog\DataLoaderBundle\Attribute\AsDataLoader;
use Overblog\DataLoaderBundle\DependencyInjection\OverblogDataLoaderExtension;
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

        $container->addCompilerPass(new class implements CompilerPassInterface {

            private function registerDataLoader(
                ContainerBuilder $container,
                string $name,
                array $config,
                string $batchLoadFn
            ): array {
                $id = $this->generateDataLoaderServiceIDFromName($name, $container);
                $OptionServiceID = $this->generateDataLoaderOptionServiceIDFromName($name, $container);
                $container->register($OptionServiceID, 'Overblog\\DataLoader\\Option')
                    ->setPublic(false)
                    ->setArguments([$config]);

                return [
                    $container->register($id, 'Overblog\\DataLoader\\DataLoader')
                        ->setPublic(true)
                        ->addTag('kernel.reset', ['method' => 'clearAll'])
                        ->setArguments([
                            new Reference($batchLoadFn),
                            new Reference('overblog_dataloader.react_promise_adapter'),
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
                return sprintf('overblog_dataloader.%s_loader', $container->underscore($name));
            }

            public function process(ContainerBuilder $container)
            {
                foreach ($container->findTaggedServiceIds('overblog.dataloader.fn') as $id => $tags) {
                    $serviceDefinition = $container->getDefinition($id);
                    $class = $serviceDefinition->getClass();

                    if (!$attribute = (new \ReflectionClass($class))->getAttributes(AsDataLoader::class)) {
                        throw new \LogicException('In order to use ' . DataLoaderFnInterface::class . ' you must define ' . AsDataLoader::class . ' attribute on your class');
                    }
                    $attributeArgs = $attribute[0]->getArguments();
                    $name = $attributeArgs['alias'];

                    unset($attributeArgs['alias']);
                    [, $serviceId] = $this->registerDataLoader($container, $name, $attributeArgs, new Reference($id));
                    $container->registerAliasForArgument($serviceId, DataLoaderInterface::class, $name);
                }
            }
        });
    }
}
