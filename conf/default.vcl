#
#        __       _      _             _ _
#        \ \     (_)    | |           (_) |
#         \ \     _  ___| |_ _ __ __ _ _| |___
#          > >   | |/ _ \ __| '__/ _` | | / __|
#         / /    | |  __/ |_| | | (_| | | \__ \
#        /_/     | |\___|\__|_|  \__,_|_|_|___/
#               _/ |                             ______
#              |__/                             |______|
#
# This config is deployed and managed through ansible. Do not modify without authorization.
#
# Source: /etc/ansible/roles/global/templates/varnish/default-m2.j2 on Sep 25, 2018 by Jarett
vcl 4.0;

import std;
# The minimal Varnish version is 4.0
# For SSL offloading, pass the following header in your proxy server or load balancer: 'X-Forwarded-Proto: https'

backend default {
    .host = "127.0.0.1";
    .port = "8080";
}

acl purge {
    "127.0.0.1";
    "localhost";
}

sub vcl_recv {
    if (req.method == "PURGE") {
        if (client.ip !~ purge) {
            return (synth(405, "Method not allowed"));
        }
        # To use the X-Pool header for purging varnish during automated deployments, make sure the X-Pool header
        # has been added to the response in your backend server config. This is used, for example, by the
        # capistrano-magento2 gem for purging old content from varnish during it's deploy routine.
        # JetRails_Varnish Start
        if ( !req.http.X-Magento-Tags-Pattern && !req.http.X-Pool && !req.http.JetRails-Purge-Type ) {
            return (synth(400, "X-Magento-Tags-Pattern or X-Pool or JetRails-Purge-Type header required"));
        }
        # JetRails_Varnish End
        if (req.http.X-Magento-Tags-Pattern) {
          ban("obj.http.X-Magento-Tags ~ " + req.http.X-Magento-Tags-Pattern);
        }
        if (req.http.X-Pool) {
          ban("obj.http.X-Pool ~ " + req.http.X-Pool);
        }
        # JetRails_Varnish Start
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
        # JetRails_Varnish End
        return (synth(200, "Purged"));
    }

    if (req.method != "GET" &&
        req.method != "HEAD" &&
        req.method != "PUT" &&
        req.method != "POST" &&
        req.method != "TRACE" &&
        req.method != "OPTIONS" &&
        req.method != "DELETE") {
          /* Non-RFC2616 or CONNECT which is weird. */
          return (pipe);
    }

    # We only deal with GET and HEAD by default
    if (req.method != "GET" && req.method != "HEAD") {
        return (pass);
    }

    # Bypass shopping cart, checkout and search requests
    if (req.url ~ "/checkout" || req.url ~ "/catalogsearch") {
        return (pass);
    }

    # Bypass health check requests
    if (req.url ~ "/pub/health_check.php") {
        return (pass);
    }

    # Set initial grace period usage status
    set req.http.grace = "none";

    # normalize url in case of leading HTTP scheme and domain
    set req.url = regsub(req.url, "^http[s]?://", "");

    # collect all cookies
    std.collect(req.http.Cookie);

    # Compression filter. See https://www.varnish-cache.org/trac/wiki/FAQ/Compression
    if (req.http.Accept-Encoding) {
        if (req.url ~ "\.(jpg|jpeg|png|gif|gz|tgz|bz2|tbz|mp3|ogg|swf|flv)$") {
            # No point in compressing these
            unset req.http.Accept-Encoding;
        } elsif (req.http.Accept-Encoding ~ "gzip") {
            set req.http.Accept-Encoding = "gzip";
        } elsif (req.http.Accept-Encoding ~ "deflate" && req.http.user-agent !~ "MSIE") {
            set req.http.Accept-Encoding = "deflate";
        } else {
            # unkown algorithm
            unset req.http.Accept-Encoding;
        }
    }

    # Remove Google gclid parameters to minimize the cache objects
    set req.url = regsuball(req.url,"\?gclid=[^&]+$",""); # strips when QS = "?gclid=AAA"
    set req.url = regsuball(req.url,"\?gclid=[^&]+&","?"); # strips when QS = "?gclid=AAA&foo=bar"
    set req.url = regsuball(req.url,"&gclid=[^&]+",""); # strips when QS = "?foo=bar&gclid=AAA" or QS = "?foo=bar&gclid=AAA&bar=baz"

    # Static files caching
    if (req.url ~ "^/(pub/)?(media|static)/") {
        # Static files should not be cached by default
        return (pass);

        # But if you use a few locales and don't use CDN you can enable caching static files by commenting previous line (#return (pass);) and uncommenting next 3 lines
        #unset req.http.Https;
        #unset req.http.X-Forwarded-Proto;
        #unset req.http.Cookie;
    }

    return (hash);
}

sub vcl_hash {
    if (req.http.cookie ~ "X-Magento-Vary=") {
        hash_data(regsub(req.http.cookie, "^.*?X-Magento-Vary=([^;]+);*.*$", "\1"));
    }

    # For multi site configurations to not cache each other's content
    if (req.http.host) {
        hash_data(req.http.host);
    } else {
        hash_data(server.ip);
    }

    # To make sure http users don't see ssl warning
    if (req.http.X-Forwarded-Proto) {
        hash_data(req.http.X-Forwarded-Proto);
    }

}

sub vcl_backend_response {
    # JetRails_Varnish Start
    if ( beresp.http.JetRails-No-Cache-Blame-Route ||
         beresp.http.JetRails-No-Cache-Blame-Url ||
         beresp.http.JetRails-No-Cache-Blame-Module
    ) {
        return ( deliver );
    }
    # JetRails_Varnish End

    set beresp.grace = 3d;

    if (beresp.http.content-type ~ "text") {
        set beresp.do_esi = true;
    }

    if (bereq.url ~ "\.js$" || beresp.http.content-type ~ "text") {
        set beresp.do_gzip = true;
    }

    if (beresp.http.X-Magento-Debug) {
        set beresp.http.X-Magento-Cache-Control = beresp.http.Cache-Control;
    }

    # cache only successfully responses and 404s
    if (beresp.status != 200 && beresp.status != 404) {
        set beresp.ttl = 0s;
        set beresp.uncacheable = true;
        return (deliver);
    } elsif (beresp.http.Cache-Control ~ "private") {
        set beresp.uncacheable = true;
        set beresp.ttl = 86400s;
        return (deliver);
    }

    # validate if we need to cache it and prevent from setting cookie
    # images, css and js are cacheable by default so we have to remove cookie also
    if (beresp.ttl > 0s && (bereq.method == "GET" || bereq.method == "HEAD")) {
        unset beresp.http.set-cookie;
    }

   # If page is not cacheable then bypass varnish for 2 minutes as Hit-For-Pass
   if (beresp.ttl <= 0s ||
       beresp.http.Surrogate-control ~ "no-store" ||
       (!beresp.http.Surrogate-Control &&
       beresp.http.Cache-Control ~ "no-cache|no-store") ||
       beresp.http.Vary == "*") {
       # Mark as Hit-For-Pass for the next 2 minutes
        set beresp.ttl = 120s;
        set beresp.uncacheable = true;
    }

    return (deliver);
}

sub vcl_deliver {
    if (resp.http.X-Magento-Debug) {
        if (resp.http.x-varnish ~ " ") {
            set resp.http.X-Magento-Cache-Debug = "HIT";
            set resp.http.Grace = req.http.grace;
        } else {
            set resp.http.X-Magento-Cache-Debug = "MISS";
        }
    } else {
        unset resp.http.Age;
    }

    # JetRails_Varnish Start
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
    # JetRails_Varnish End

    # Not letting browser to cache non-static files.
    if (resp.http.Cache-Control !~ "private" && req.url !~ "^/(pub/)?(media|static)/") {
        set resp.http.Pragma = "no-cache";
        set resp.http.Expires = "-1";
        set resp.http.Cache-Control = "no-store, no-cache, must-revalidate, max-age=0";
    }

    unset resp.http.X-Magento-Debug;
    unset resp.http.X-Magento-Tags;
    unset resp.http.X-Powered-By;
    unset resp.http.Server;
    unset resp.http.X-Varnish;
    unset resp.http.Via;
    unset resp.http.Link;

    # JetRails_Varnish Start
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
    # JetRails_Varnish End
}

sub vcl_hit {
    if (obj.ttl >= 0s) {
        # Hit within TTL period
        return (deliver);
    }
    if (std.healthy(req.backend_hint)) {
        if (obj.ttl + 300s > 0s) {
            # Hit after TTL expiration, but within grace period
            set req.http.grace = "normal (healthy server)";
            return (deliver);
        } else {
            # Hit after TTL and grace expiration
            return (fetch);
        }
    } else {
        # server is not healthy, retrieve from cache
        set req.http.grace = "unlimited (unhealthy server)";
        return (deliver);
    }
}
