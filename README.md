# varnish
Magento 2 - Manage Varnish Cache

sudo varnishd -a 127.0.0.1:80 -T 127.0.0.1:6082 -f /usr/local/etc/varnish/default.vcl -s file,/tmp,500M
sudo apachectl restart
sudo pkill varnishd

varnishadm "vcl.load default /usr/local/etc/varnish/default.vcl"
varnishadm "vcl.use default"


varnish:config
varnish:status
varnish:status:set <enable / disable>
varnish:purge:url <url>
varnish:purge:store <store_view_id>
varnish:purge:all

primer:config
primer:status
primer:status:set <enable / disable>
primer:queue
primer:queue:show


