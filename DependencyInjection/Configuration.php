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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const SERVICE_CALLABLE_NOTATION_REGEX = '/^@(?<service_id>[a-z0-9\._]+)(?:\:(?<method>[a-zA-Z_\x7f-\xff][a-z0-9_\x7f-\xff]*))?$/i';
    const PHP_CALLABLE_NOTATION_REGEX = '/^(?<function>(?:\\\\?[a-z_\x7f-\xff][a-z0-9_\x7f-\xff]*)+)(?:\:\:(?<method>[a-z_\x7f-\xff][a-z0-9_\x7f-\xff]*))?$/i';

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('overblog_dataloader');
        $rootNode
            ->children()
                ->arrayNode('defaults')
                    ->isRequired()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('promise_adapter')->isRequired()->end()
                        ->append($this->addCallableSection('factory'))
                        ->append($this->addOptionsSection()->addDefaultsIfNotSet())
                    ->end()
                ->end()
                ->arrayNode('loaders')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('factory')->end()
                            ->scalarNode('alias')
                                ->validate()
                                    ->ifTrue(function ($alias) {
                                        return !preg_match('/[a-z0-9_\.]+/i', $alias);
                                    })
                                        ->thenInvalid('%s is not a valid service alias.')
                                    ->end()
                                ->end()
                            ->end()
                            ->append($this->addCallableSection('batch_load_fn')->isRequired())
                            ->append($this->addOptionsSection())
                        ->end()
                    ->end()
                ->end()
            ->end()
            ;

        return $treeBuilder;
    }

    private function addOptionsSection()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('options');
        $node
            ->children()
                ->booleanNode('batch')->defaultTrue()->end()
                ->integerNode('max_batch_size')->defaultNull()->min(0)->end()
                ->booleanNode('cache')->defaultTrue()->end()
                ->append($this->addCallableSection('cache_key_fn')->defaultNull())
                ->scalarNode('cache_map')->defaultValue('overblog_dataloader.cache_map')->end()
            ->end();

        return $node;
    }

    private function addCallableSection($name)
    {
        $builder = new TreeBuilder();
        $node = $builder->root($name, 'scalar');

        $node
            ->validate()
                ->ifTrue(function ($batchLoadFn) {
                    if (preg_match(self::SERVICE_CALLABLE_NOTATION_REGEX, $batchLoadFn)) {
                        return false;
                    }

                    if (preg_match(self::PHP_CALLABLE_NOTATION_REGEX, $batchLoadFn)) {
                        return false;
                    }

                    return true;
                })
                    ->thenInvalid('%s doesn\'t seem to be a valid callable.')
                ->end()
            ->end();

        return $node;
    }
}
