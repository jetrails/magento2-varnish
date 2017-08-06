Magento 2 - Varnish Module
=============================
This module helps manage varnish cache for your Magento 2 store.  It supports a multiple varnish server configuration.  Purge requests can be sent to all these servers in order to purge a specific URL, a whole store view, or simply to purge all the cache that is contained in said varnish server.

## Installation

To install, look into the 'releases' tab and download the version that you want.  Alternatively, the latest version should be packaged in the __dist__ folder of this repository.  Simply place that file in the base install directory of your Magento store and unzip the contents of that folder.  Finally, run `php ./bin/magento setup:upgrade` inside your base Magento install directory.  This will configure the module and everything should be installed after the command finished running.

##  Command Line Interface

Please run `php ./bin/magento list` inside your base install directory of your Magento store.  Once the command runs, look under the __varnish__ category.  There you will find all the possible CLI commands that can be run along with a description of what each one does.

## Things to add in the future

-   Implement varnish hole punching based on block class path
