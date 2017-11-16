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
use Symfony\Component\Filesystem\Filesystem;

/**
 * TestCase.
 */
abstract class TestCase extends WebTestCase
{
    /**
     * @var AppKernel[]
     */
    private static $kernels = [];

    protected static function getKernelClass()
    {
        require_once __DIR__.'/app/AppKernel.php';

        return 'Overblog\DataLoaderBundle\Tests\Functional\app\AppKernel';
    }

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = [])
    {
        if (null === static::$class) {
            static::$class = static::getKernelClass();
        }

        $options['test_case'] = isset($options['test_case']) ? $options['test_case'] : null;

        $env = isset($options['environment']) ? $options['environment'] : 'test'.strtolower($options['test_case']);
        $debug = isset($options['debug']) ? $options['debug'] : true;

        return new static::$class($env, $debug, $options['test_case']);
    }

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/OverblogDataLoaderBundle/');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        static::$kernel = null;
    }

    protected static function getContainer()
    {
        return static::$kernel->getContainer();
    }
}
