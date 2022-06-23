# Installation

1. Have magento-cloud installed and configured with API TOKEN.
2. Have cloud-teleport installed and configured
3. Have warden from this repo and branch https://github.com/npuchko/warden-multi-arch/tree/debian
4. Create symlinks to bin files:
```shell

sudo ln -s /Users/npuchko/www/tools/warden-addon/bin/wdi /usr/local/bin/wdi
sudo ln -s /Users/npuchko/www/tools/warden-addon/bin/warden-remove /usr/local/bin/warden-remove

```


# Known Issues

1. 503 Backend fetch failed varnish - disable Magento_Csp (it puts lots of headers)
2. Endless redirect to self when open admin url: go to pub/index.php and add 
```php 
$_SERVER['HTTPS'] = 'https';
``` 
to very start of the file