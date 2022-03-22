1. Have magento-cloud installed and configured with API TOKEN
2. Have warden from this repo and branch https://github.com/npuchko/warden-multi-arch/tree/debian
3. Create symlinks to bin files:
```shell

sudo ln -s /Users/npuchko/www/tools/warden-addon/bin/wdi /usr/local/bin/wdi
sudo ln -s /Users/npuchko/www/tools/warden-addon/bin/wah /usr/local/bin/wah
sudo ln -s /Users/npuchko/www/tools/warden-addon/bin/replace-env /usr/local/bin/replace-env
sudo ln -s /Users/npuchko/www/tools/warden-addon/bin/warden-remove /usr/local/bin/warden-remove

```