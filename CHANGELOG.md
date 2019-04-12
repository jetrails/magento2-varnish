## Version 1.0.0
-	Initial Upload

## Version 1.1.0
-	Removed backend server configuration
-	Removed VCL template generation by user

## Version 1.1.1
-	Change caching application from varnish module config page
-	Change status of varnish module when cache application changes
-	Changed headers in conf/default.vcl

## Version 1.1.2
-	Fixed GH-4, issue of passing non-variable to reset function

## Version 1.1.3
-	Fixed GH-7, issue of invalidating config cache too often

## Version 1.1.4
-	Fixed GH-8, issue with not using rewrites for auto-purge

## Version 1.1.5
-	Fixed GH-10, added auto-purge for categories
-	Fixed GH-11, when product is saved, categories are purged as well
-	Fixed GH-12, add 'substring' option for purge url functionality
-	Fixed GH-13, auto purge no works when deleting products/categories

## Version 1.1.6
-	Fixed GH-15, added module version and combines auto-purge messages
-	Fixed GH-18, auto-purge loops through store views on "all store view" scope

## Version 1.1.7
-	Fixed GH-20, Varnish trademark compliance

## Version 1.1.8
-	Fixed GH-21, form key fix for Magento 2.3
