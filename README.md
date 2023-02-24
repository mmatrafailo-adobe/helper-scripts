# Installation

1. Have magento-cloud installed and configured with API TOKEN.
2. Have cloud-teleport installed and configured
3. Have warden from this repo and branch https://github.com/npuchko/warden-multi-arch/tree/warden_for_mac_m1
4. Create symlinks to bin files:
```shell
sudo ln -s /Users/npuchko/www/tools/warden-addon/bin/wdi /usr/local/bin/wdi
sudo ln -s /Users/npuchko/www/tools/warden-addon/bin/warden-remove /usr/local/bin/warden-remove

```
5. Install tampermonkey scripts here https://github.com/npuchko/helper-scripts/tree/master/tampermonkey


# Usage
## To install
1. Create directory for all the client dumps (for example /Users/<user>/www/clients)
2. Make sure that docker is running and warden containers too (run ```warden svc up``` to run warden containers)
3. Go to that directory
4. Run command 
```shell
wdi ABCDE-12345 abcdefjhqkf staging
```
- where ABCDE-12345 is number of the ticket,
- abcdefjhqkf is the magento cloud project ID
- staging is the branch name (environment id)
5. Wait for the installation.
6. Type your sudo password to add local domain to /etc/hosts file

## To remove
1. Go to your "clients" dir (for example /Users/<user>/www/clients)
2. Run ```warden-remove abcde12345``` where abcde12345 is the name of folder with the dumps.
3. This command will completely remove all docker volume data, images, networks and directory with files.

# Known Issues

1. 503 Backend fetch failed varnish - disable Magento_Csp (it puts lots of headers)
2. Endless redirect to self when open admin url: go to pub/index.php and add 
```php 
$_SERVER['HTTPS'] = 'https';
``` 
to very start of the file