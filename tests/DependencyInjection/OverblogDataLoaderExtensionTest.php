<?php

/*
 * This file is part of the OverblogDataLoaderBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\DataLoaderBundle\Tests\DependencyInjection;

use Overblog\DataLoaderBundle\DependencyInjection\Configuration;
use Overblog\DataLoaderBundle\DependencyInjection\OverblogDataLoaderExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OverblogDataLoaderExtensionTest extends TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;
    /**
     * @var OverblogDataLoaderExtension
     */
    private $extension;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.bundles', []);
        $this->container->setParameter('kernel.debug', false);
        $this->extension = new OverblogDataLoaderExtension();
    }

    protected function tearDown(): void
    {
        unset($this->container, $this->extension);
    }

    public function testValidServiceCallableNodeValue()
    {
        $validValues = ['@app.user:getUsers', '@App\\Loader\\User:all'];
        foreach ($validValues as $validValue) {
            $this->assertMatchesRegularExpression(Configuration::SERVICE_CALLABLE_NOTATION_REGEX, $validValue);
        }
    }

    public function testValidPhpCallableNodeValue()
    {
        $validValues = ['Image\\Loader::get', 'Post::getPosts'];
        foreach ($validValues as $validValue) {
            $this->assertMatchesRegularExpression(Configuration::PHP_CALLABLE_NOTATION_REGEX, $validValue);
        }
    }

    public function testUsersDataLoaderConfig()
    {
        $this->loadValidConfig();

        $serviceDataLoaderID = 'overblog_dataloader.users_loader';
        $serviceDataLoaderOptionID = $serviceDataLoaderID.'_option';

        $this->assertDataLoaderService(
            $serviceDataLoaderID,
            [
                [new Reference('app.user'), 'getUsers'],
                new Reference('overblog_dataloader.react_promise_adapter'),
                new Reference($serviceDataLoaderOptionID),
            ],
            'users_loader'
        );

        $this->assertOptionService(
            $serviceDataLoaderOptionID,
            [
                'batch' => true,
                'cache' => true,
                'maxBatchSize' => null,
                'cacheMap' => new Reference('overblog_dataloader.cache_map'),
                'cacheKeyFn' => null,
            ]
        );
    }

    public function testPostsDataLoaderConfig()
    {
        $this->loadValidConfig();

        $serviceDataLoaderID = 'overblog_dataloader.posts_loader';
        $serviceDataLoaderOptionID = $serviceDataLoaderID.'_option';

        $this->assertDataLoaderService(
            $serviceDataLoaderID,
            [
                ['Post', 'getPosts'],
                new Reference('overblog_dataloader.react_promise_adapter'),
                new Reference($serviceDataLoaderOptionID),
            ]
        );

        $this->assertOptionService(
            $serviceDataLoaderOptionID,
            [
                'batch' => false,
                'cache' => false,
                'maxBatchSize' => 15,
                'cacheMap' => new Reference('app.cache.map'),
                'cacheKeyFn' => new Reference('app.cache'),
            ]
        );
    }

    public function testImagesDataLoaderConfig()
    {
        $this->loadValidConfig();

        $serviceDataLoaderID = 'overblog_dataloader.images_loader';
        $serviceDataLoaderOptionID = $serviceDataLoaderID.'_option';

        $this->assertDataLoaderService(
            $serviceDataLoaderID,
            [
                ['Image\Loader', 'get'],
                new Reference('overblog_dataloader.react_promise_adapter'),
                new Reference($serviceDataLoaderOptionID),
            ]
        );

        $this->assertOptionService(
            $serviceDataLoaderOptionID,
            [
                'batch' => true,
                'cache' => true,
                'maxBatchSize' => null,
                'cacheMap' => new Reference('overblog_dataloader.cache_map'),
                'cacheKeyFn' => null,
            ]
        );

        $this->assertEquals(
            'dataloader_factory',
            $this->container->getDefinition($serviceDataLoaderID)->getFactory()
        );
    }

    public function testBatchLoadFnNotCallable()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage("\"NOT CALLABLE\" doesn't seem to be a valid callable.");

        $this->extension->load(
            [
                [
                    'defaults' => [
                        'promise_adapter' => 'overblog_dataloader.react_promise_adapter',
                    ],
                    'loaders' => [
                        'users' => [
                            'batch_load_fn' => 'NOT CALLABLE',
                        ],
                    ],
                ],
            ],
            $this->container
        );
    }

    private function loadValidConfig()
    {
        $this->extension->load(
            [
                [
                    'defaults' => [
                        'promise_adapter' => 'overblog_dataloader.react_promise_adapter',
                    ],
                    'loaders' => [
                        'users' => [
                            'alias' => 'users_loader',
                            'batch_load_fn' => '@app.user:getUsers',
                        ],
                        'posts' => [
                            'batch_load_fn' => 'Post::getPosts',
                            'options' => [
                                'max_batch_size' => 15,
                                'batch' => false,
                                'cache' => false,
                                'cache_map' => 'app.cache.map',
                                'cache_key_fn' => '@app.cache',
                            ],
                        ],
                        'images' => [
                            'factory' => 'dataloader_factory',
                            'batch_load_fn' => 'Image\Loader::get',
                        ],
                    ],
                ],
            ],
            $this->container
        );
    }

    private function assertDataLoaderService($serviceID, array $expectedArguments, $alias = null)
    {
        $testDataLoaderDefinition = $this->container->getDefinition($serviceID);
        $this->assertEquals($expectedArguments, $testDataLoaderDefinition->getArguments());

        if ($alias) {
            $this->assertEquals(new Alias($serviceID, true), $this->container->getAlias($alias));
        }
    }

    private function assertOptionService($serviceID, array $expectedParameters)
    {
        $testOptionDefinition = $this->container->getDefinition($serviceID);

        $this->assertEquals([$expectedParameters], $testOptionDefinition->getArguments());
    }
}
