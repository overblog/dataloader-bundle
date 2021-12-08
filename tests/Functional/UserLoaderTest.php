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

use Overblog\DataLoader\DataLoader;
use Overblog\DataLoaderBundle\Tests\Functional\app\UserDataProvider;
use function React\Promise\all;

class UserLoaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();
        static::createKernel();
    }

    public function testGetUsers()
    {
        /** @var DataLoader $userLoader */
        $userLoader = static::$kernel->getContainer()->get('users_loader');

        $promise = all([
            $userLoader->load(3),
            $userLoader->load(5),
            $userLoader->loadMany([5, 2, 4]),
            $userLoader->loadMany([1, 6, 3]),
        ]);

        $this->assertEquals(
            [
                UserDataProvider::$users[3],
                UserDataProvider::$users[5],
                [
                    UserDataProvider::$users[5],
                    UserDataProvider::$users[2],
                    UserDataProvider::$users[4],
                ],
                [
                    UserDataProvider::$users[1],
                    UserDataProvider::$users[6],
                    UserDataProvider::$users[3],
                ],
            ],
            $userLoader->await($promise)
        );
    }
}
