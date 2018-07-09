# Magento 2 - Varnish
> Magento 2 extension which interfaces with the Varnish caching application in order to manage it through the Magento backend

![MIT License](https://img.shields.io/badge/License-UNLICENSED-lightgrey.svg?style=for-the-badge)
![Version 1.1.2](https://img.shields.io/badge/Version-1.1.2-lightgrey.svg?style=for-the-badge)
![Stability Beta](https://img.shields.io/badge/Stability-Beta-lightgrey.svg?style=for-the-badge)

<p align="center" >
	<img src="docs/images/preview.png" width="100%" />
</p>

## About

This module helps manage varnish cache for your Magento 2 store.  It supports a multiple varnish server configuration.  Purge requests can be sent to all these servers in order to purge a specific URL, a whole store view, or simply to purge all the cache that is contained in said varnish server. Additionally, the purge process can be executed automatically on product or CMS page save. Cache exclusion rules can be set to not cache paths or Magento routes. Finally, there exists a _debug_ mode that will display if Varnish FPC was used in loading the page and which exclusion rules should be blamed if the page is excluded.

## Documentation

A user guide can be found in the [docs](docs) folder. The information there goes over all the features that the extension offers. It also takes you through the installation and configuration process of setting this extension up.

## Build System

All JetRails® modules use __Grunt__ as a build system.  Grunt is a package that can be easily downloaded using __NPM__.  Once this repository is cloned, run `npm install grunt -g` followed by `npm install` to install Grunt and all Grunt modules used within this build system.  Please refer to the following table for a description of some useful grunt build commands. A typical grunt command takes the following form: `grunt task:argument`.

| Task       | Description                                                                                                                                                                                     |
|------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `version`  | Updates the version number in all __php__ and __xml__ files with the one defined in __package.json__.                                                                                           |
| `release`  | This command first runs __init__ and then __resolve__.  It then compresses the source and dependencies and outputs the archive in __dist__.  This command gets the repo ready for a git commit. |
| `deploy`   | Will upload dependencies and source code to a staging server.  Credentials to this server can be configured in the __package.json__ file under the _staging_ attribute.                         |
| `stream`   | Will watch the __lib__ and __src__ folders for any changes. Once a change occurs it will run the __deploy__ task.                                                                               |
|            | The default task is aliased to run the __release__ task.                                                                                                                                        |

## Docker Environment

This project comes with a [docker-compose.yml](docker-compose.yml) file, which can be used to spin up a Magento CE 1.x environment. In order to use docker, please make sure you have **Docker** and **Docker Compose** installed. For information about configuring this docker environment, please refer to it's Github repository which can be found [here](https://github.com/jetrails/docker-magento).
