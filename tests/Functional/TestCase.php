<?php

/*
 * This file is part of the OverblogDataLoaderBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\DataLoaderBundle\Tests\Functional;

use Overblog\DataLoaderBundle\Tests\Functional\app\AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * TestCase.
 */
abstract class TestCase extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        require_once __DIR__.'/app/AppKernel.php';

        return AppKernel::class;
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        if (null === static::$class) {
            static::$class = static::getKernelClass();
        }

        $options['test_case'] = isset($options['test_case']) ? $options['test_case'] : null;

        $env = isset($options['environment']) ? $options['environment'] : 'test'.strtolower($options['test_case'] ?? '');
        $debug = isset($options['debug']) ? $options['debug'] : true;

        return new static::$class($env, $debug, $options['test_case']);
    }

    public static function setUpBeforeClass(): void
    {
        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/OverblogDataLoaderBundle/');
    }

    protected static function getContainer(): ContainerInterface
    {
        return static::$kernel->getContainer();
    }
}
