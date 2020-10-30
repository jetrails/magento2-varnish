## Version 1.0.0
- Initial Upload

## Version 1.1.0
- Removed backend server configuration
- Removed VCL template generation by user

## Version 1.1.1
- Change caching application from varnish module config page
- Change status of varnish module when cache application changes
- Changed headers in conf/default.vcl

## Version 1.1.2
- Fixed #4, issue of passing non-variable to reset function

## Version 1.1.3
- Fixed #7, issue of invalidating config cache too often

## Version 1.1.4
- Fixed #8, issue with not using rewrites for auto-purge

## Version 1.1.5
- Fixed #10, added auto-purge for categories
- Fixed #11, when product is saved, categories are purged as well
- Fixed #12, add 'substring' option for purge url functionality
- Fixed #13, auto purge no works when deleting products/categories

## Version 1.1.6
- Fixed #15, added module version and combines auto-purge messages
- Fixed #18, auto-purge loops through store views on "all store view" scope

## Version 1.1.7
- Fixed #20, Varnish trademark compliance

## Version 1.1.8
- Fixed #21, form key fix for Magento 2.3

## Version 1.1.9
- Fixed #22, auto-purge failed when REST API triggered save

## Version 1.1.10
- Fixed #26, Auto-purge hangs when invalid varnish server is used

## Version 2.0.0
- Fixed #31
- Fixed #32
- Fixed #33
- Fixed #34
- Fixed #35
