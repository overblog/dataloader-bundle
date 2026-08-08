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

use Overblog\DataLoader\DataLoader;
use Overblog\DataLoaderBundle\Attribute\AsDataLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class OverblogDataLoaderExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->registerAttributeForAutoconfiguration(AsDataLoader::class, function (ChildDefinition $definition, AsDataLoader $attribute, \ReflectionClass|\ReflectionMethod $reflector) use ($config) {
            if ($reflector instanceof \ReflectionMethod && null !== $attribute->method) {
                throw new \LogicException(\sprintf('Parameter "method" for attribute "%s" must be NULL when applied on a method.', AsDataLoader::class));
            }

            $definition->addTag('overblog.dataloader', array_merge($config['defaults'], [
                'name' => $attribute->name,
                'alias' => $attribute->alias,
                'method' => $reflector instanceof \ReflectionMethod ? $reflector->getName() : ($attribute->method ?? '__invoke'),
                'options' => array_merge($config['defaults']['options'], $attribute->options ?? []),
            ]));
        });

        foreach ($config['loaders'] as $name => $loaderConfig) {
            $container->register(Support::generateDataLoaderServiceIDFromName($name, $container), DataLoader::class)
                ->addTag('overblog.dataloader', array_merge($config['defaults'], [
                    'name' => $name,
                    'alias' => $loaderConfig['alias'] ?? null,
                    'batch_load_fn' => $loaderConfig['batch_load_fn'],
                    'options' => array_merge($config['defaults']['options'], $loaderConfig['options'] ?? []),
                ]));
        }
    }

    public function getAlias(): string
    {
        return Support::getAlias();
    }
}
