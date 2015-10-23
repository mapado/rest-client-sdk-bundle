# Rest Client Sdk Bundle

Symfony bundle for [mapado/rest-client-sdk](https://github.com/mapado/rest-client-sdk)

## Installation
```sh
composer require mapado/rest-client-sdk-bundle
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
