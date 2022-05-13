1. Install tampermonkey
2. add scripts into it.

admin-autologin.js - automatically logged in when you open magento admin login page.
It uses admin as login and 123123q as password.
It works for domains matched by this rules:
```javascript
// @match        https://*.test/admin*
// @match        https://*.sparta.ceng.magento.com/*/admin*
```

The settings are:
```javascript
    const adminLogin = "admin"; // admin username
    const adminPassword = "123123q"; // admin password
    const clickToButton = true; // automatically click to login button
```

jira-helper.js - add magento cli commands into JIRA under project URL field if this field contains correct project URL like https://ap-3.magento.cloud/projects/ob77kg6julmeu/environments/integration2

If you don't see the new fields - feel free to reload JIRA page
