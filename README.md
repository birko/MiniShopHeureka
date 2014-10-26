MiniShopHeureka
===============

Heureka Support for minishop

## Installation

in project composer.json

``` json
    "require": {
    ...
        "birko/minishop-heureka": "@dev",
        "heureka/overenozakazniky": "dev-master"
```

register in AppKernel

``` php
    class AppKernel extends Kernel
    {
        public function registerBundles()
        {
            $bundles = array(
                ...
                new Core\HeurekaBundle\CoreHeurekaBundle(),
                ...
```    

in routing.yml

``` yaml  
    core_heureka:
        resource: "@CoreHeurekaBundle/Resources/config/routing.yml"
        prefix:   /
```

in config

``` yaml  
    core_heureka:
    prices:
        - 'normal'
    key: ~
```
