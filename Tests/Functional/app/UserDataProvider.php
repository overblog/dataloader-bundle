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

class UserDataProvider
{
    public static $users = [
        1 => [
            'id' => 1,
            'name' => 'Damien',
        ],
        2 => [
            'id' => 2,
            'name' => 'Nicolas',
        ],
        3 => [
            'id' => 3,
            'name' => 'Jeremiah',
        ],
        4 => [
            'id' => 4,
            'name' => 'Florent',
        ],
        5 => [
            'id' => 5,
            'name' => 'Sebastien',
        ],
        6 => [
            'id' => 6,
            'name' => 'Cedric',
        ],
    ];

    public static function getUsersPromise(array $ids)
    {
        $users = [];
        foreach ($ids as $id) {
            $users[] = isset(self::$users[$id]) ? self::$users[$id] : null;
        }

        return \React\Promise\resolve($users);
    }
}
