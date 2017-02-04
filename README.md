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
composer require ovenblog/dataloader-bundle
```

### Enable the Bundle

```php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...

            new Overblog\DataLoaderBundle\DataLoaderBundle(),
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
        # optionnal
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
            batch_load_fn: "Image\Loader::get"
```

This example will create 3 loaders as services:
- "@overblog_dataloader.users_loader" with alias "@users_dataloader"
- "@overblog_dataloader.posts_loader"
- "@overblog_dataloader.images_loader" create using custom factory function "my_factory"

## License

Overblog/DataLoaderBundle is released under the [MIT](https://github.com/overblog/dataloader-bundle/blob/master/LICENSE) license.
