# Plugin Additions

> These are the additions / changes that are made to the default Magento VCL config.

## vcl_recv

Allow PURGE when plugin header is set as well

```
        if ( !req.http.X-Magento-Tags-Pattern && !req.http.X-Pool && !req.http.JetRails-Purge-Type ) {
            return (synth(400, "X-Magento-Tags-Pattern or X-Pool or JetRails-Purge-Type header required"));
        }
```

Insert plugin purge logic

```
        if ( req.http.JetRails-Purge-Type ) {
            if ( req.http.JetRails-Purge-Type == "url" &&
                 req.http.JetRails-Host &&
                 req.http.JetRails-Url
            ) {
                ban ( "req.http.host == " + req.http.JetRails-Host + " && req.url == " + req.http.JetRails-Url );
                return ( synth ( 200, "Purged" ) );
            }
            elsif ( req.http.JetRails-Purge-Type == "store" &&
                    req.http.JetRails-Host &&
                    req.http.JetRails-Url
            ) {
                ban ( "req.http.host == " + req.http.JetRails-Host + " && req.url ~ " + req.http.JetRails-Url );
                return ( synth ( 200, "Purged" ) );
            }
            elsif ( req.http.JetRails-Purge-Type == "all" ) {
                ban ("req.http.host ~ .*");
                return ( synth ( 200, "Purged" ) );
            }
        }
```

True IP

```
    set req.http.X-Forwarded-For = req.http.CF-Connecting-IP;
```

## vcl_backend_response

Add plugin "no-cache" logic

```
    if ( beresp.http.JetRails-No-Cache-Blame-Route ||
         beresp.http.JetRails-No-Cache-Blame-Url ||
         beresp.http.JetRails-No-Cache-Blame-Module
    ) {
        return ( deliver );
    }
```

## vcl_deliver

Add logic for marking headers for debug mode

```
    if ( resp.http.JetRails-Debug ) {
        if ( obj.hits > 0 ) {
            set resp.http.JetRails-Debug-Cache = "HIT";
        }
        else {
            set resp.http.JetRails-Debug-Cache = "MISS";
        }
        set resp.http.X-Cache-Expires = resp.http.Expires;
    }
    else {
        unset resp.http.JetRails-No-Cache-Blame-Route;
        unset resp.http.JetRails-No-Cache-Blame-Url;
        unset resp.http.JetRails-Debug-Cache;
        unset resp.http.Age;
    }
```

Setting and unsetting response headers

```
    unset resp.http.JetRails-Debug;
    unset resp.http.X-Magento-Cache-Debug;
    unset resp.http.X-Magento-Debug;
    unset resp.http.X-Magento-Tags;
    unset resp.http.X-Powered-By;
    unset resp.http.X-Cache;
    unset resp.http.Server;
    unset resp.http.X-Varnish;
    unset resp.http.Via;
    unset resp.http.Link;
    set resp.http.X-Powered-By = "Magic";
    set resp.http.Server = "JetRails";
```
