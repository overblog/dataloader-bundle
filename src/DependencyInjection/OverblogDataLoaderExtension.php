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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use function array_replace;
use function preg_match;
use function sprintf;

final class OverblogDataLoaderExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        foreach ($config['loaders'] as $name => $loaderConfig) {
            $loaderConfig = array_replace($config['defaults'], $loaderConfig);
            $dataLoaderServiceID = $this->generateDataLoaderServiceIDFromName($name, $container);
            $OptionServiceID = $this->generateDataLoaderOptionServiceIDFromName($name, $container);
            $batchLoadFn = $this->buildCallableFromScalar($loaderConfig['batch_load_fn']);

            $container->register($OptionServiceID, 'Overblog\\DataLoader\\Option')
                ->setPublic(false)
                ->setArguments([$this->buildOptionsParams($loaderConfig['options'])]);

            $definition = $container->register($dataLoaderServiceID, 'Overblog\\DataLoader\\DataLoader')
                ->setPublic(true)
                ->addTag('kernel.reset', ['method' => 'clearAll'])
                ->setArguments([
                    $batchLoadFn,
                    new Reference($loaderConfig['promise_adapter']),
                    new Reference($OptionServiceID),
                ]);

            if (isset($loaderConfig['factory'])) {
                $definition->setFactory($this->buildCallableFromScalar($loaderConfig['factory']));
            }

            if (isset($loaderConfig['alias'])) {
                $container->setAlias($loaderConfig['alias'], $dataLoaderServiceID);
                $container->getAlias($loaderConfig['alias'])->setPublic(true);
            }
        }
    }

    public function getAlias(): string
    {
        return 'overblog_dataloader';
    }

    private function generateDataLoaderServiceIDFromName($name, ContainerBuilder $container): string
    {
        return sprintf('%s.%s_loader', $this->getAlias(), $container::underscore($name));
    }

    private function generateDataLoaderOptionServiceIDFromName($name, ContainerBuilder $container): string
    {
        return sprintf('%s_option', $this->generateDataLoaderServiceIDFromName($name, $container));
    }

    private function buildOptionsParams(array $options): array
    {
        $optionsParams = [];

        $optionsParams['batch'] = $options['batch'];
        $optionsParams['cache'] = $options['cache'];
        $optionsParams['maxBatchSize'] = $options['max_batch_size'];
        $optionsParams['cacheMap'] = new Reference($options['cache_map']);
        $optionsParams['cacheKeyFn'] = $this->buildCallableFromScalar($options['cache_key_fn']);

        return $optionsParams;
    }

    private function buildCallableFromScalar($scalar): mixed
    {
        $matches = null;

        if (null === $scalar) {
            return null;
        }

        if (preg_match(Configuration::SERVICE_CALLABLE_NOTATION_REGEX, $scalar, $matches)) {
            $function = new Reference($matches['service_id']);
            if (empty($matches['method'])) {
                return $function;
            }

            return [$function, $matches['method']];
        } elseif (preg_match(Configuration::PHP_CALLABLE_NOTATION_REGEX, $scalar, $matches)) {
            $function = $matches['function'];
            if (empty($matches['method'])) {
                return $function;
            }

            return [$function, $matches['method']];
        }

        return null;
    }
}
