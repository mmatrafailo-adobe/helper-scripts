1. Have magento-cloud installed and configured
2. Have warden from this repo and branch https://github.com/npuchko/warden-multi-arch/tree/debian


ln -s /path/to/repository/wdi /usr/local/bin/wdi
sudo ln -s /Users/npuchko/www/tools/warden-addon/wdi /usr/local/bin/wdi
sudo ln -s /Users/npuchko/www/tools/warden-addon/wah /usr/local/bin/wah

sudo ln -s /Users/npuchko/www/tools/warden-addon/replace-env /usr/local/bin/replace-env
sudo ln -s /Users/npuchko/www/tools/warden-addon/warden-remove /usr/local/bin/warden-remove


UPDATE core_config_data set value = REPLACE(value, 'www.nvghub.com', 'app.mdva42129.test') WHERE value LIKE '%www.nvghub.com%'