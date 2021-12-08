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

use Overblog\DataLoaderBundle\DependencyInjection\OverblogDataLoaderExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class OverblogDataLoaderBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new OverblogDataLoaderExtension;
    }
}
