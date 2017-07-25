vcl 4.0;

import std;

# The minimal Varnish version is 4.0
# For SSL offloading, pass the following header in your proxy server or load balancer: 'X-Forwarded-Proto: https'

backend default {
	.host = "%_BACKEND_HOST_%";
	.port = "%_BACKEND_PORT_%";
}

acl local {
	%_ACL_%
}

sub vcl_recv {

	if ( req.method == "PURGE" ) {
		# Make sure they are allowed to purge
		if ( client.ip !~ local ) {
			return ( synth ( 405, "Method not allowed" ) );
		}

		if ( req.http.JetRails-Purge-Type ) {
			if ( req.http.JetRails-Purge-Type == "url" && req.http.JetRails-Host && req.http.JetRails-Url ) {
				ban ( "req.http.host == " + req.http.JetRails-Host + " && req.url == " + req.http.JetRails-Url );
				return ( synth ( 200, "Purged" ) );
			}
			elsif ( req.http.JetRails-Purge-Type == "store" && req.http.JetRails-Host && req.http.JetRails-Url ) {
				ban ( "req.http.host == " + req.http.JetRails-Host + " && req.url ~ " + req.http.JetRails-Url );
				return ( synth ( 200, "Purged" ) );
			}
			elsif ( req.http.JetRails-Purge-Type == "all" ) {
				ban ("req.http.host ~ .*");
				return ( synth ( 200, "Purged" ) );
			}
		}

		# Otherwise it is a malformed request and requires more information
		return ( synth ( 400, "Bad Request" ) );
	}

	if ( req.method != "GET" &&
		 req.method != "HEAD" &&
		 req.method != "PUT" &&
		 req.method != "POST" &&
		 req.method != "TRACE" &&
		 req.method != "OPTIONS" &&
		 req.method != "DELETE") {
		 return ( pipe );
	}

	# We only deal with GET and HEAD by default
	if ( req.method != "GET" && req.method != "HEAD" ) {
		return ( pass );
	}

	# Bypass shopping cart, checkout and search requests
	if ( req.url ~ "/checkout" || req.url ~ "/catalogsearch" ) {
		return ( pass );
	}

	# normalize url in case of leading HTTP scheme and domain
	set req.url = regsub ( req.url, "^http[s]?://", "" );

	# collect all cookies
	std.collect ( req.http.Cookie );

	# Compression filter. See https://www.varnish-cache.org/trac/wiki/FAQ/Compression
	if ( req.http.Accept-Encoding ) {
		if ( req.url ~ "\.(jpg|jpeg|png|gif|gz|tgz|bz2|tbz|mp3|ogg|swf|flv)$" ) {
			# No point in compressing these
			unset req.http.Accept-Encoding;
		}
		elsif (req.http.Accept-Encoding ~ "gzip") {
			set req.http.Accept-Encoding = "gzip";
		}
		elsif (req.http.Accept-Encoding ~ "deflate" && req.http.user-agent !~ "MSIE") {
			set req.http.Accept-Encoding = "deflate";
		}
		else {
			unset req.http.Accept-Encoding;
		}
	}

	# Remove Google gclid parameters to minimize the cache objects
	set req.url = regsuball(req.url,"\?gclid=[^&]+$",""); # strips when QS = "?gclid=AAA"
	set req.url = regsuball(req.url,"\?gclid=[^&]+&","?"); # strips when QS = "?gclid=AAA&foo=bar"
	set req.url = regsuball(req.url,"&gclid=[^&]+",""); # strips when QS = "?foo=bar&gclid=AAA" or QS = "?foo=bar&gclid=AAA&bar=baz"

	# static files are always cacheable. remove SSL flag and cookie
		if (req.url ~ "^/(pub/)?(media|static)/.*\.(ico|css|js|jpg|jpeg|png|gif|tiff|bmp|mp3|ogg|svg|swf|woff|woff2|eot|ttf|otf)$") {
		unset req.http.Https;
		unset req.http.X-Forwarded-Proto;
		unset req.http.Cookie;
	}

	return (hash);
}

sub vcl_hash {
	if ( req.http.cookie ~ "X-Magento-Vary=") {
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

	if ( beresp.http.JetRails-No-Cache-Blame-Route ||
		 beresp.http.JetRails-No-Cache-Blame-Url ||
		 beresp.http.JetRails-No-Cache-Blame-Module
	) {
		return ( pass );
	}

	if (beresp.http.content-type ~ "text") {
		set beresp.do_esi = true;
	}

	if (bereq.url ~ "\.js$" || beresp.http.content-type ~ "text") {
		set beresp.do_gzip = true;
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

	if (beresp.http.X-Magento-Debug) {
		set beresp.http.X-Magento-Cache-Control = beresp.http.Cache-Control;
	}

	# validate if we need to cache it and prevent from setting cookie
	# images, css and js are cacheable by default so we have to remove cookie also
	if (beresp.ttl > 0s && (bereq.method == "GET" || bereq.method == "HEAD")) {
		unset beresp.http.set-cookie;
		if ( bereq.url !~ "\.(ico|css|js|jpg|jpeg|png|gif|tiff|bmp|gz|tgz|bz2|tbz|mp3|ogg|svg|swf|woff|woff2|eot|ttf|otf)(\?|$)" ) {
			set beresp.http.Pragma = "no-cache";
			set beresp.http.Expires = "-1";
			set beresp.http.Cache-Control = "no-store, no-cache, must-revalidate, max-age=0";
			set beresp.grace = 1m;
		}
	}

    # If page is not cacheable then bypass varnish for 2 minutes as Hit-For-Pass
    if ( beresp.ttl <= 0s ||
		 beresp.http.Surrogate-control ~ "no-store" ||
		 ( !beresp.http.Surrogate-Control && beresp.http.Vary == "*" ) ) {
		# Mark as Hit-For-Pass for the next 2 minutes
		set beresp.ttl = 120s;
		set beresp.uncacheable = true;
	}
	return ( deliver );
}

sub vcl_deliver {
	// Check to see if debug flag is set
	if ( resp.http.JetRails-Debug ) {
		// If it is and there were hits
		if ( obj.hits > 0 ) {
			// Set the debug cache header to hit
			set resp.http.JetRails-Debug-Cache = "HIT";
		}
		// Otherwise it was a miss
		else {
			// Set the debug cache header to miss
			set resp.http.JetRails-Debug-Cache = "MISS";
		}
		set resp.http.X-Cache-Expires = resp.http.Expires;
	}
	// If Debug flag is not set
	else {
		// Remove all instances of JetRails headers
		unset resp.http.JetRails-No-Cache-Blame-Route;
		unset resp.http.JetRails-No-Cache-Blame-Url;
		unset resp.http.JetRails-Debug-Cache;
		unset resp.http.Age;
	}
	// Unset unnecessary response header parameters
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
	// Set some custom header parameters
	set resp.http.X-Powered-By = "JetRails Magic";
	# set resp.http.Server = "<script>alert ('hello');</script>";
	set resp.http.Server = "alert";
}