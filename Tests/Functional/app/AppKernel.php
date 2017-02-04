<?php

/*
 * This file is part of the OverblogDataLoaderBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\DataLoaderBundle\Tests\Functional\app;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * AppKernel.
 */
class AppKernel extends Kernel
{
    private $testCase;

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Overblog\DataLoaderBundle\OverblogDataLoaderBundle(),
        ];
    }

    public function getCacheDir()
    {
        return sys_get_temp_dir().'/OverblogDataLoaderBundle/'.Kernel::VERSION.'/cache/'.$this->environment;
    }

    public function getLogDir()
    {
        return sys_get_temp_dir().'/OverblogDataLoaderBundle/'.Kernel::VERSION.'/logs';
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config.yml');
    }
}
