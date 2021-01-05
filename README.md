# Rest Client Sdk Bundle [![StyleCI](https://styleci.io/repos/44800866/shield)](https://styleci.io/repos/44800866)

Symfony bundle for [mapado/rest-client-sdk](https://github.com/mapado/rest-client-sdk)

## Installation
```sh
composer require mapado/rest-client-sdk-bundle
```

### Symfony flex

Add it to your `config/bundle.php`
```php
return [
    // ...
    Mapado\RestClientSdkBundle\MapadoRestClientSdkBundle::class => ['all' => true],
];
```
### Without flex

Add it to your AppKernel.php
```php
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Mapado\RestClientSdkBundle\MapadoRestClientSdkBundle(),
        )

        // ...
```

## Usage

Add this in your configuration file :

Symfony Flex: `config/packages/mapado_rest_client_sdk.yaml`, not flex: `app/config/config.yml`

```yaml
mapado_rest_client_sdk:
    # debug: %kernel.debug%
    # cache_dir: '%kernel.cache_dir%/mapado/rest_client_sdk_bundle/'
    entity_managers:
        foo:
            server_url: 'http://foo.com:8080'
            request_headers:
                MyHeader: 'MyValue'
            mappings:
                prefix: /v1
                configuration:
                    collectionKey: 'items' # default is "hydra:member"
                dir: '%kernel.root_dir%/../src/Foo/Bar/Entity/'
            cache:
                cache_item_pool: 'psr6_cache_provider' # default is null
                cache_prefix: 'my_prefix' # default is null

```

The bundle registers one service for each entity manager that you defined (in this case just one for `foo`).

The name of the service will be: `mapado.rest_client_sdk.<manager_name>`.

As I named my entity manager `foo`, The service name here will be : `mapado.rest_client_sdk.foo`.

If you use Symfony 3.3+ autowiring feature, you may want to alias something like this: 
```yaml
services:
    # ...
    Mapado\RestClientSdk\SdkClient: '@mapado.rest_client_sdk.foo'
```

If you have multiple entity managers, Symfony documentation explains [how to deal with multiple implementation of the same type](https://symfony.com/doc/current/service_container/autowiring.html#dealing-with-multiple-implementations-of-the-same-type).

Imagine I have the following model, as defined in the [component documentation](https://github.com/mapado/rest-client-sdk#configuration):
```php
/**
 * @Rest\Entity(key="carts", client="Acme\Foo\Bar\CartClient")
 */
class Cart {
    // ...
}
```

I can now do something like this:
```php
$cartList = $this->get('mapado.rest_client_sdk.foo')
    ->getRepository('carts')
    ->findAll(); // `carts` is the `key` defined in the model

$cart = $this->get('mapado.rest_client_sdk.foo')
    ->getRepository('carts')
    ->find(1);
```

For a more complete information on the usage, I recommand you to look at the [component documentation](https://github.com/mapado/rest-client-sdk#usage)

### Using cache
By providing a Psr6 [`Psr\Cache\CacheItemPoolInterface`](http://www.php-fig.org/psr/psr-6/#cacheitempoolinterface) to `cache.cache_item_pool`, each entity and entityList fetched will be stored in cache. 

For example at Mapado, we are using the [Symfony Array cache adapter](http://symfony.com/doc/current/components/cache/cache_pools.html#array-cache-adapter) like this:
```yaml
services:
    cache.rest_client_sdk:
        class: 'Symfony\Component\Cache\Adapter\ArrayAdapter'
        arguments:
            - 0
            - false # avoid serializing entities

mapado_rest_client_sdk:
    entity_managers:
        foo:
            server_url: '%server.url%'
            mappings:
                prefix: /v1
                dir: '%kernel.root_dir%/../src/Mapado/Foo/Model/'
            cache:
                cache_item_pool: 'cache.rest_client_sdk' # the id of the cache service
                cache_prefix: 'mapado_rest_client_'
```

### Overriding default http client

Sometime, you need to override the base HTTP client. At Mapado, we like to add a the current page as a `Referrer`, pass down the current `Accept-Language` header, or send an `Authorization` for our API call.

As the HTTP client is automatically generated, the only way to do that is to decorate your default client :

```yaml
# config/services.yaml or app/config/service.yml

mapado.rest_client_sdk.decorating_ticketing
services:
  # ... 
  mapado.rest_client_sdk.decorating_foo_http_client:
      class:     'App\Rest\Decorator\DecoratingClient'
      decorates: 'mapado.rest_client_sdk.foo_http_client'
      arguments: ['@mapado.rest_client_sdk.decorating_foo_http_client.inner']
      public:    true

```

```php
<?php

namespace App\Rest\Decorator;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class DecoratingClient implements ClientInterface
{
    /**
     * @var Client
     */
    private $decoratedClient;
    
    public function __construct(Client $decoratedClient)
    {
        $this->decoratedClient = $decoratedClient;
    }
    
    /**
     * {@inheritdoc}
     */
    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        return $this->decoratedClient->send($request, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        return $this->decoratedClient->sendAsync($request, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function request($method, $uri, array $options = []): ResponseInterface
    {
        if (!isset($options['headers'])) {
            $options['headers'] = [];
        }

        $options['headers'] = array_merge(
            $options['headers'],
            [
                'Authorization' => 'Bearer my-great-token',
                'Accept-Language' => 'fr',
            ]
        );

        return $this->decoratedClient->request($method, $uri, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function requestAsync($method, $uri, array $options = []): PromiseInterface
    {
        return $this->decoratedClient->requestAsync($method, $uri, $options);
    }

    public function getConfig($option = null)
    {
        return $this->decoratedClient->getConfig($option);
    }
}
```
