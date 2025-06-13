vcl 4.1;

{{if enable_xkey}}
import xkey;
{{/if}}

backend default {
    .host = "magento";
    .port = "8080";
}

sub vcl_recv {
    {{if enable_xkey}}
    # Full Page Cache flush
    if (req.http.X-Magento-Tags-Pattern == ".*") {
        ban("obj.http.X-Magento-Tags ~ " + req.http.X-Magento-Tags-Pattern);
    } elseif (req.http.X-Magento-Tags-Pattern) {
        # replace "((^|,)cat_c(,|$))|((^|,)cat_p(,|$))" to be "cat_c cat_p" 
        set req.http.X-Magento-Tags-Pattern = regsuball(req.http.X-Magento-Tags-Pattern, "[^a-zA-Z0-9_-]+" ," ");
        # trim spaces
        set req.http.X-Magento-Tags-Pattern = regsuball(req.http.X-Magento-Tags-Pattern, "(^\s*)|(\s*$)" ,"");
        set req.http.n-gone = xkey.softpurge(req.http.X-Magento-Tags-Pattern);
        return (synth(200, "Invalidated " + req.http.n-gone + " objects"));
    }
    {{/if}}
}

sub vcl_backend_response {
    # Period to allow stale cache to be served
    set beresp.grace = 3h;

    {{if enable_xkey}}
    # using xkey
    if (beresp.http.X-Magento-Tags) {
        set beresp.http.Grace = beresp.grace;
        # set space separated xkey
        set beresp.http.xkey = regsuball(beresp.http.X-Magento-Tags, ",", " ");
        # reset beresp.http.X-Magento-Tags with some common general value
        set beresp.http.X-Magento-Tags = "fpc";
    }
    {{/if}}
}
