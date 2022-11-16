// ==UserScript==
// @name         Admin Autologin
// @namespace    https://adobe.com
// @version      0.3
// @description  Autologin into admin panel if credentials are
// @author       Nick Puchko <npuchko@adobe.com>
// @match        https://*.test/admin*
// @match        https://*.test/index.php/admin*
// @match        *://*.nip.io/pub/admin*
// @match        *://*.nip.io/pub/index.php/admin*
// @match        https://*.sparta.ceng.magento.com/*/admin*
// @match        https://*.sparta.ceng.magento.com/*/admin*
// @icon         https://www.google.com/s2/favicons?sz=64&domain=adobe.com
// @grant        none
// ==/UserScript==

(function() {
    'use strict';
    const adminLogin = "admin"; // admin username
    const adminPassword = "123123q"; // admin password
    const clickToButton = true; // automatically click to login button


    const docReady = function (fn) {
        // see if DOM is already available
        if (document.readyState === "complete" || document.readyState === "interactive") {
            // call on next available tick
            setTimeout(fn, 1);
        } else {
            document.addEventListener("DOMContentLoaded", fn);
        }
    }

    docReady(function () {
        const username = document.getElementById("username");
        const password = document.getElementById("login");
        const button = document.getElementsByClassName("action-login")[0];
        const captcha = document.getElementById("captcha");

        if (username && password && button) {
            username.value = adminLogin;
            password.value = adminPassword;

            if (!captcha && clickToButton) {
                button.click();
            }
        }
    });
})();

