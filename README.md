# Rest Client Sdk Bundle

Symfony bundle for [mapado/rest-client-sdk](https://github.com/mapado/rest-client-sdk)

## Installation
```sh
composer require mapado/rest-client-sdk-bundle
```

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
```yaml
# app/config/config.yml
mapado_rest_client_sdk:
    # debug: %kernel.debug%
    # cache_dir: '%kernel.cache_dir%/mapado/rest_client_sdk_bundle/'
    entity_managers:
        foo:
            server_url: 'http://foo.com:8080'
            mappings:
                prefix: /v1
                dir: '%kernel.root_dir%/../src/Foo/Bar/Entity/'
```

The bundle register a service for each entity manager that you defined (in this case just one for `foo`). 

The name of the service will be: `mapado.rest_client_sdk.<manager_name>`. 

As I named my entity manager `foo`, The service name here will be : `mapado.rest_client_sdk.foo`

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
$cartList = $this->get('mapado.rest_client_sdk.foo')->getClient('carts')->findAll(); // `carts` is the `key` defined in the model

$cart = $this->get('mapado.rest_client_sdk.foo')->getClient('carts')->find(1);
```

For a more complete information on the usage, I recommand you to look at the [component documentation](https://github.com/mapado/rest-client-sdk#usage)
