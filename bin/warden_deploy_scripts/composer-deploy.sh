echo "configuring composer"
composer config --no-plugins allow-plugins.laminas/laminas-dependency-plugin true
composer config --no-plugins allow-plugins.magento/magento-composer-installer true
composer config --no-plugins allow-plugins.magento/inventory-composer-installer true
composer config --no-plugins allow-plugins.magento/composer-root-update-plugin true


echo "Run dump autoload"
composer dump-autoload