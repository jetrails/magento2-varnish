# Magento 2 - Varnish
> Magento 2 extension which interfaces with the Varnish® caching application in order to manage it through the Magento backend.

![](https://img.shields.io/badge/License-JetRails_License-lightgrey.svg?style=for-the-badge)
![](https://img.shields.io/badge/Version-1.1.11-lightgrey.svg?style=for-the-badge)
![](https://img.shields.io/badge/Stability-Stable-lightgrey.svg?style=for-the-badge)

<p align="center" >
	<img src="docs/images/preview.png" width="100%" />
</p>

## About

This module helps manage varnish Cache™ for your Magento 2 store.  It supports a multiple varnish server configuration.  Purge requests can be sent to all these servers in order to purge a specific URL, a whole store view, or simply to purge all the cache that is contained in said varnish server. Additionally, the purge process can be executed automatically on product or CMS page save. Cache exclusion rules can be set to not cache paths or Magento routes. Finally, there exists a _debug_ mode that will display if Varnish FPC was used in loading the page and which exclusion rules should be blamed if the page is excluded.

## Documentation

The user manual can be found [here](https://learn.jetrails.com/article/magento-2-varnish-extension). The information there goes over all the features that the extension offers. It also takes you through the installation and configuration process of setting this extension up.

## Build System

A simple [Makefile](./Makefile) is used for this purpose. It is very easy to use and to get a full list of commands and their descriptions, then run the following command:

```shell
$ make help
```

Here are some of the more useful use-cases:

```shell
# Replace version number with latest git tag value
$ make bump
# Replace version with specified value
$ make VERSION=1.0.0 bump
# Package with version being latest git tag value
$ make package
# Package with version being manually specified
$ make VERSION=1.0.0 package
```

## Docker Environment

This project comes with a [docker-compose.yml](docker-compose.yml) file as well as a [docker-sync.yml](docker-sync.yml) file, which can be used to spin up a Magento 2 environment. In order to use docker, please make sure you have **Docker**, **Docker Compose**, and **Docker Sync** installed. For information about configuring this docker environment, please refer to it's Github repository which can be found [here](https://github.com/jetrails/docker-magento-alpine).

## Legal Disclaimer

Varnish is a registered trademark of Varnish Software AB and its affiliates.
