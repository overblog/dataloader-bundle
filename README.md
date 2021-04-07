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
* **[GuzzleHttp/Promises](https://github.com/guzzle/promises)**: overblog_dataloader.guzzle_http_promise_adapter
* **[Webonyx/GraphQL-PHP](https://github.com/webonyx/graphql-php) Sync Promise**: overblog_dataloader.webonyx_graphql_sync_promise_adapter

## Combine with GraphQLBundle

This bundle can be use with [GraphQLBundle](https://github.com/overblog/GraphQLBundle).
Here an example:

* First create your service. We will use the Webonyx promise adapter

```yaml
#config/services.yaml
services:
    graphql_promise_adapter:
        class: Overblog\DataLoader\Promise\Adapter\Webonyx\GraphQL\SyncPromiseAdapter
        public: true
        
    
    ###
    # Force the overblog promise adapter to use the webonyx adapter
    ###
    Overblog\PromiseAdapter\PromiseAdapterInterface:
        class: Overblog\PromiseAdapter\Adapter\WebonyxGraphQLSyncPromiseAdapter
        arguments:
            - "@graphql_promise_adapter"
    
    ###
    # Add magic configuration to load all your dataLoader
    ###
    App\Loader\:
        resource: "../src/Loader/*"
```

* Now configure the packages

```yaml
#config/packages/graphql.yaml
overblog_graphql:
    #[...]
    services:
        promise_adapter: "graphql_promise_adapter"

#config/packages/dataloader.yaml
overblog_dataloader:
    defaults:
        promise_adapter: "graphql_promise_adapter"
```

* Create an abstract class GenericDataLoader

```php
<?php

namespace App\Loader;

use Overblog\DataLoader\DataLoader;
use Overblog\PromiseAdapter\PromiseAdapterInterface;

abstract class AbstractDataLoader extends DataLoader
{
    /**
     * @param PromiseAdapterInterface $promiseAdapter
     */
    public function __construct(PromiseAdapterInterface $promiseAdapter) {
        parent::__construct(
            function ($ids) use ($promiseAdapter) {
                return $promiseAdapter->createAll($this->find($ids));
            },
            $promiseAdapter
        );
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    abstract protected function find(array $ids): array;
}
```

* Now create a DataLoader for all your entities. For example user

```php
<?php

namespace App\Loader;

use App\Repository\UserRepository;
use Overblog\PromiseAdapter\PromiseAdapterInterface;

class UserDataLoader extends AbstractDataLoader
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @param UserRepository $userRepository
     * @param PromiseAdapterInterface $promiseAdapter
     */
    public function __construct(
        UserRepository $userRepository,
        PromiseAdapterInterface $promiseAdapter
    ) {
        parent::__construct($promiseAdapter);
        $this->userRepository = $userRepository;
    }

    /**
     * @inheritdoc
     */
    protected function find(array $ids): array
    {
        return $this->userRepository->findById($ids);
    }
}
``` 

* Usage in a resolver

```php
<?php

namespace App\Resolver;

use App\Entity\User;
use App\Loader\UserDataLoader;
use App\Repository\UserRepository;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;
use Overblog\GraphQLBundle\Relay\Connection\Paginator;

final class UserResolver implements ResolverInterface, AliasedInterface
{
    /**
     * @var UserDataLoader
     */
    private $userDataLoader;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @param UserDataLoader $userDataLoader
     * @param UserRepository $userRepository
     */
    public function __construct(
        UserDataLoader $userDataLoader,
        UserRepository $userRepository
    ) {
        $this->userDataLoader = $userDataLoader;
        $this->userRepository = $userRepository;
    }

    /**
     * @param int $id
     *
     * @return User
     */
    public function resolveEntity(int $id)
    {
        return $this->userDataLoader->load($id);
    }

    /**
     * @param Argument $args
     *
     * @return object|\Overblog\GraphQLBundle\Relay\Connection\Output\Connection
     * @throws \Exception
     */
    public function resolveList(Argument $args)
    {
        $paginator = new Paginator(function ($offset, $limit) {
            $ids = $this->userRepository->paginatedUsersIds($offset, $limit);

            return $this->userDataLoader->loadMany($ids);
        }, Paginator::MODE_PROMISE);

        return $paginator->auto($args, function() {
            return $this->userRepository->count([]);
        });
    }

    /**
     * {@inheritdoc}
     */
    public static function getAliases(): array
    {
        return [
            'resolveEntity' => 'User',
            'resolveList' => 'Users',
        ];
    }
}
```

This is an example using the sync promise adapter of Webonyx.

## License

Overblog/DataLoaderBundle is released under the [MIT](https://github.com/overblog/dataloader-bundle/blob/master/LICENSE) license.
