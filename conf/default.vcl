vcl 4.0;

import std;

backend default {
    .host = "127.0.0.1";
    .port = "8080";
}

acl local {
    "127.0.0.1";
    "localhost";
}

sub vcl_recv {
	if ( req.method == "PURGE" ) {
		if ( client.ip !~ local ) {
			return ( synth ( 405, "Method not allowed" ) );
		}
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

	if ( req.method != "GET" && req.method != "HEAD" ) {
		return ( pass );
	}

	if ( req.url ~ "/checkout" || req.url ~ "/catalogsearch" ) {
		return ( pass );
	}
	set req.url = regsub ( req.url, "^http[s]?://", "" );
	std.collect ( req.http.Cookie );

	if ( req.http.Accept-Encoding ) {
		if ( req.url ~ "\.(jpg|jpeg|png|gif|gz|tgz|bz2|tbz|mp3|ogg|swf|flv)$" ) {
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

	set req.url = regsuball(req.url,"\?gclid=[^&]+$","");
	set req.url = regsuball(req.url,"\?gclid=[^&]+&","?");
	set req.url = regsuball(req.url,"&gclid=[^&]+","");

	if ( req.url ~ "^/(pub/)?(media|static)/.*\.(ico|css|js|jpg|jpeg|png|gif|tiff|bmp|mp3|ogg|svg|swf|woff|woff2|eot|ttf|otf)$" ) {
		unset req.http.Https;
		unset req.http.X-Forwarded-Proto;
		unset req.http.Cookie;
	}

	return (hash);
}

sub vcl_hash {
	if ( req.http.cookie ~ "X-Magento-Vary=" ) {
		hash_data(regsub(req.http.cookie, "^.*?X-Magento-Vary=([^;]+);*.*$", "\1"));
	}
	if (req.http.host) {
		hash_data(req.http.host);
	}
	else {
		hash_data(server.ip);
	}
	if (req.http.X-Forwarded-Proto) {
		hash_data(req.http.X-Forwarded-Proto);
	}
}

sub vcl_backend_response {

	if ( beresp.http.JetRails-No-Cache-Blame-Route ||
		 beresp.http.JetRails-No-Cache-Blame-Url ||
		 beresp.http.JetRails-No-Cache-Blame-Module
	) {
		return ( deliver );
	}

	if (beresp.http.content-type ~ "text") {
		set beresp.do_esi = true;
	}

	if (bereq.url ~ "\.js$" || beresp.http.content-type ~ "text") {
		set beresp.do_gzip = true;
	}
	if ( beresp.status != 200 && beresp.status != 404 ) {
		set beresp.ttl = 0s;
		set beresp.uncacheable = true;
		return (deliver);
	}
	elsif (beresp.http.Cache-Control ~ "private") {
		set beresp.uncacheable = true;
		set beresp.ttl = 86400s;
		return (deliver);
	}

	if ( beresp.http.X-Magento-Debug ) {
		set beresp.http.X-Magento-Cache-Control = beresp.http.Cache-Control;
	}

	if ( beresp.ttl > 0s && ( bereq.method == "GET" || bereq.method == "HEAD" ) ) {
		unset beresp.http.set-cookie;
		if ( bereq.url !~ "\.(ico|css|js|jpg|jpeg|png|gif|tiff|bmp|gz|tgz|bz2|tbz|mp3|ogg|svg|swf|woff|woff2|eot|ttf|otf)(\?|$)" ) {
			set beresp.http.Pragma = "no-cache";
			set beresp.http.Expires = "-1";
			set beresp.http.Cache-Control = "no-store, no-cache, must-revalidate, max-age=0";
			set beresp.grace = 1m;
		}
	}

	if ( beresp.ttl <= 0s ||
		 beresp.http.Surrogate-control ~ "no-store" ||
		 ( !beresp.http.Surrogate-Control && beresp.http.Vary == "*" )
	) {
		set beresp.ttl = 120s;
		set beresp.uncacheable = true;
	}
	return ( deliver );
}

sub vcl_deliver {
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
}
