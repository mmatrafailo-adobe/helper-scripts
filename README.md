1. Have magento-cloud installed and configured with API TOKEN.
2. Have cloud-teleport installed and configured
3. Have warden from this repo and branch https://github.com/npuchko/warden-multi-arch/tree/debian
4. Create symlinks to bin files:
```shell

sudo ln -s /Users/npuchko/www/tools/warden-addon/bin/wdi /usr/local/bin/wdi
sudo ln -s /Users/npuchko/www/tools/warden-addon/bin/wah /usr/local/bin/wah
sudo ln -s /Users/npuchko/www/tools/warden-addon/bin/warden-remove /usr/local/bin/warden-remove

```

5. Add ssh tunnel connection https://docs.warden.dev/configuration/database.html, 
6. Find file /Users/{Username}/Library/Application Support/JetBrains/PhpStorm{Version}/options/sshConfigs.xml
```xml
<application>
  <component name="SshConfigs">
    <configs>
      <sshConfig authType="OPEN_SSH" host="tunnel.warden.test" id="2a7205b3-f1f8-4185-9dd7-5e27e48f11f1" port="2222" nameFormat="DESCRIPTIVE" username="user" useOpenSSHConfig="true" />
    </configs>
  </component>
</application>
```
7. Copy that id to bin/wdi, replacing this value:
```xml
   <ssh-config-id>2a7205b3-f1f8-4185-9dd7-5e27e48f11f1</ssh-config-id>
```
You need to copy that id and paste into bin/wdi to have automatically configured db on new instances


=== Known Issues ===

1. 503 Backend fetch failed varnish - disable Magento_Csp (it puts lots of headers)