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

class BootTest extends TestCase
{
    public function testBootAppKernel(): void
    {
        $kernel = $this->createKernel();
        $kernel->boot();

        $this->assertTrue($kernel->isBooted());
    }
}
