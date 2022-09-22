php bin/magento deploy:mode:set developer
php bin/magento a:c:i
php bin/magento ca:f
php bin/magento admin:user:create --admin-user admin --admin-password 123123q --admin-email admin@example.com --admin-firstname admf --admin-lastname adml
php bin/magento mo:di Magento_TwoFactorAuth