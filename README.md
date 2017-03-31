# DataLoaderBundle

This bundle allows to easy use  [DataLoaderPHP](https://github.com/overblog/dataloader-php)
in your Symfony 2 / 3 project by  configuring it through configuration.

[![Build Status](https://travis-ci.org/overblog/dataloader-bundle.svg?branch=master)](https://travis-ci.org/overblog/dataloader-bundle)
[![Coverage Status](https://coveralls.io/repos/github/overblog/dataloader-bundle/badge.svg?branch=master)](https://coveralls.io/github/overblog/dataloader-bundle?branch=master)
[![Latest Stable Version](https://poser.pugx.org/overblog/dataloader-bundle/version)](https://packagist.org/packages/overblog/dataloader-bundle)
[![License](https://poser.pugx.org/overblog/dataloader-bundle/license)](https://packagist.org/packages/overblog/dataloader-bundle)

## Requirements

This library requires PHP >= 5.5 to work.

## Installation

### Download the Bundle

```
composer require overblog/dataloader-bundle
```

### Enable the Bundle

```php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...

            new Overblog\DataLoaderBundle\OverblogDataLoaderBundle(),
        ];

        // ...
    }

    // ...
}
```

## Getting Started

Here a fast example of how you can use the bundle

```yaml
overblog_dataloader:
    defaults:
        # required
        promise_adapter: "overblog_dataloader.react_promise_adapter"
        # optional
        factory: ~
        options:
            batch: true
            cache: true
            max_batch_size: ~
            cache_map: "overblog_dataloader.cache_map"
            cache_key_fn: ~
    loaders:
        users:
            alias: "users_dataloader"
            batch_load_fn: "@app.user:getUsers"
        posts: 
            batch_load_fn: "Post::getPosts"
            options:
                max_batch_size: 15
                batch: false
                cache: false
                cache_map: "app.cache.map"
                cache_key_fn: "@app.cache"
        images:
            factory: my_factory
            batch_load_fn: "Image\\Loader::get"
```

This example will create 3 loaders as services:
- "@overblog_dataloader.users_loader" with alias "@users_dataloader"
- "@overblog_dataloader.posts_loader"
- "@overblog_dataloader.images_loader" create using custom factory function "my_factory"

Here the list of existing promise adapters:

* **[ReactPhp/Promise](https://github.com/reactphp/promise)**: overblog_dataloader.react_promise_adapter
* **[GuzzleHttp/Promises](https://github.com/guzzle/promises)**: overblog_dataloader.guzzle_promise_adapter
* **[Webonyx/GraphQL-PHP](https://github.com/webonyx/graphql-php) Sync Promise**: overblog_dataloader.webonyx_graphql_sync_promise_adapter

## Combine with GraphQLBundle

This bundle can be use with [GraphQLBundle](https://github.com/overblog/GraphQLBundle).
Here an example:

* Bundle config

```yaml
#graphql
overblog_graphql:
    definitions:
        schema:
            query: Query
    services:
        promise_adapter: "webonyx_graphql.sync_promise_adapter"

#dataloader
overblog_dataloader:
    defaults:
        promise_adapter: "overblog_dataloader.webonyx_graphql_sync_promise_adapter"
    loaders:
        ships:
            alias: "ships_loader"
            batch_load_fn: "@app.graph.ships_loader:all"
```

* Batch loader function

```yaml
services:
    app.graph.ship_repository:
        class: AppBundle\Entity\Repository\ShipRepository
        factory: ["@doctrine.orm.entity_manager", getRepository]
        arguments:
            - AppBundle\Entity\Ship

    app.graph.ships_loader:
        class: AppBundle\GraphQL\Loader\ShipLoader
        arguments:
            - "@overblog_graphql.promise_adapter"
            - "@app.graph.ship_repository"
```

```php
<?php

namespace AppBundle\GraphQL\Loader;

use AppBundle\Entity\Repository\ShipRepository;
use GraphQL\Executor\Promise\PromiseAdapter;

class ShipLoader
{
    private $promiseAdapter;

    private $repository;

    public function __construct(PromiseAdapter $promiseAdapter, ShipRepository $repository)
    {
        $this->promiseAdapter = $promiseAdapter;
        $this->repository = $repository;
    }

    public function all(array $shipsIDs)
    {
        $qb = $this->repository->createQueryBuilder('s');
        $qb->add('where', $qb->expr()->in('s.id', ':ids'));
        $qb->setParameter('ids', $shipsIDs);
        $ships = $qb->getQuery()->getResult();

        return $this->promiseAdapter->all($ships);
    }
}
```

* Usage in a resolver

```php
    public function resolveShip($shipsID)
    {
        $promise = $this->container->get('ships_loader')->load($shipsID);

        return $promise;
    }
```

This is an example using the sync promise adapter of Webonyx.

## License

Overblog/DataLoaderBundle is released under the [MIT](https://github.com/overblog/dataloader-bundle/blob/master/LICENSE) license.
