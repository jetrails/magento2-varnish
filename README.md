# varnish
Magento 2 - Manage Varnish Cache

sudo varnishd -a 127.0.0.1:80 -T 127.0.0.1:6082 -f /usr/local/etc/varnish/default.vcl -s file,/tmp,500M
sudo apachectl restart
sudo pkill varnishd

varnishadm "vcl.load default /usr/local/etc/varnish/default.vcl"
varnishadm "vcl.use default"




app code JetRails
app design JetRails


MODEL -> MAKE Model->Adminhtml