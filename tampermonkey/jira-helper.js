// ==UserScript==
// @name         JIRA Commands Display
// @namespace    https://adobe.com
// @version      0.2.1
// @description  Add magento cloud cli commands into jira under project url
// @author       You
// @match        https://jira.corp.magento.com/browse/*
// @match        https://jira.corp.adobe.com/browse/*
// @icon         https://www.google.com/s2/favicons?sz=64&domain=magento.com
// @require      https://code.jquery.com/jquery-latest.js
// @grant        none
// ==/UserScript==

(function($) {
    'use strict';
    let issueNumber = $("#key-val").text();
    let projectUrl = $("#customfield_14217-val a").attr("href");

    if (!projectUrl) {
        projectUrl = $("#customfield_18505-val a").attr("href");
    }

    if (!projectUrl) {
        console.log("Project URL not found!!!!");
        return;
    }

    // https://ap-3.magento.cloud/projects/ob77kg6julmeu/environments/integration2

    const projectUrlObj = new URL(projectUrl);
    const parts = projectUrlObj.pathname.split("/");
    let projectId = parts[2] || '';
    let envId = parts[4] || '';

    const command = "wdi " + issueNumber + " " + projectId + " " + envId;
    let mgcCommand = "mgc ssh -p " + projectId;
    let sqlCommand = "mgc sql -p " + projectId;

    if (envId) {
        mgcCommand += " -e " + envId;
        sqlCommand += " -e " + envId;
    }
    console.log(command);
    appendToJIRA("warden command", command);
    appendToJIRA("SSH command", mgcCommand);
    appendToJIRA("SQL command", sqlCommand);

    function appendToJIRA(title, command) {
        const fieldsList = $("#customfield-panel-1 ul.property-list");

        const id = "auto_" + makeid(10);
        fieldsList.append("<li id=\"wdi_command_wrap\" class=\"item\">\n" +
            "        <div class=\"wrap\">\n" +
            "            <strong title=\"Company\" class=\"name\">\n" +
            "                                    <label for=\"customfield_10040\">"+title+":</label>\n" +
            "                            </strong>\n" +
            "            <div class=\"value type-textfield\" data-fieldtype=\"textfield\" style='width: 80%'>\n" + '' +
            "<input type=\"text\" value=\""+command+"\" id=\""+id + "\"  style=\"width:50%\">" +
            " (click to copy) \n" +
            "                            </div>\n" +
            "        </div>\n" +
            "    </li>");

        $("#" + id).click(function () {
            copyToClipboard(this.value);
        });
    }
    function copyToClipboard(str) {
        const el = document.createElement('textarea');  // Create a <textarea> element
        el.value = str;                                 // Set its value to the string that you want copied
        el.setAttribute('readonly', '');                // Make it readonly to be tamper-proof
        el.style.position = 'absolute';
        el.style.left = '-9999px';                      // Move outside the screen to make it invisible
        document.body.appendChild(el);                  // Append the <textarea> element to the HTML document
        const selected =
            document.getSelection().rangeCount > 0        // Check if there is any content selected previously
                ? document.getSelection().getRangeAt(0)     // Store selection if found
                : false;                                    // Mark as false to know no selection existed before
        el.select();                                    // Select the <textarea> content
        document.execCommand('copy');                   // Copy - only works as a result of a user action (e.g. click events)
        document.body.removeChild(el);                  // Remove the <textarea> element
        if (selected) {                                 // If a selection existed before copying
            document.getSelection().removeAllRanges();    // Unselect everything on the HTML document
            document.getSelection().addRange(selected);   // Restore the original selection
        }
    }

    function makeid(length) {
        var result           = '';
        var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        var charactersLength = characters.length;
        for ( var i = 0; i < length; i++ ) {
            result += characters.charAt(Math.floor(Math.random() *
                charactersLength));
        }
        return result;
    }


})(window.jQuery.noConflict(true));

