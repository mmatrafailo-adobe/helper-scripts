// ==UserScript==
// @name         JIRA Commands Display
// @namespace    https://adobe.com
// @version      0.8.0
// @description  Add magento cloud cli commands into jira under project url
// @author       You
// @match        https://jira.corp.magento.com/browse/*
// @match        https://jira.corp.adobe.com/browse/*
// @icon         https://www.google.com/s2/favicons?sz=64&domain=magento.com
// @require      https://code.jquery.com/jquery-latest.js
// @grant        none
// ==/UserScript==

(function($j) {
    'use strict';
    let existingIds = new Map();

    let commentBinded = false;

    // subscribe to original jQuery ajax complete
    $ && $(document).ajaxComplete(function () {
        console.log('Request Complete');
        updateHandle();
    });
    updateHandle();
    function updateHandle() {
        let issueNumber = $j("#key-val").text();
        let projectUrl = $j("#customfield_14217-val a").attr("href");
        let currentUrl = document.location.href;

        if (currentUrl.indexOf('corp.adobe.com') !== -1 && currentUrl.indexOf('/ACSD-') !== -1) {
            const magentoUrl = 'https://jira.corp.magento.com/browse/MDVA' + issueNumber.replace('ACSD', '');
            appendUrl('Old JIRA', magentoUrl);
        }

        if (currentUrl.indexOf('corp.magento.com') !== -1 && currentUrl.indexOf('/MDVA-') !== -1) {
            const magentoUrl = 'https://jira.corp.adobe.com/browse/ACSD' + issueNumber.replace('MDVA', '');
            appendUrl('NEW JIRA', magentoUrl);
        }

        appendUrl('Warden env', 'https://app.' + issueNumber.toLowerCase().replace('-', '')  + '.test/');
        appendUrl('Warden env admin', 'https://app.' + issueNumber.toLowerCase().replace('-', '')  + '.test/admin/');

        if (!projectUrl) {
            projectUrl = $j("#customfield_18505-val a").attr("href");
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
        let region = projectUrlObj.host.split('.')[0];


        let gitCloneCommand = "git clone --branch "+envId+" "+projectId+"@git."+region+".magento.cloud:"+projectId+".git git_repo"

        const command = "wdi " + issueNumber + " " + projectId + " " + envId;

        let magentoCloudCommandParams = "-p " + projectId;
        if (envId) {
            magentoCloudCommandParams += " -e " + envId;
        }

        appendToJIRA("warden command", command);
        appendToJIRA("GIT CLONE", gitCloneCommand);
        appendToJIRA("SSH command", "magento-cloud ssh " + magentoCloudCommandParams);
        appendToJIRA("SQL command", "magento-cloud sql " + magentoCloudCommandParams);


        appendToJIRA("MSC command", "msc " + projectId + " " + envId);

        const newCloudUrl = "https://console.magento.cloud/projects/" + projectId + "/" + envId;

        appendUrl("NEW Project Page", newCloudUrl, "#rowForcustomfield_18505");



        let footerCommentButton = $j("#footer-comment-button");

        if (footerCommentButton.length > 0 && !commentBinded) {
            let footerHandler = function (){
                setTimeout(function (){
                    addResolutionTemplate("bottom");
                }, 300)
            };

            $j("#footer-comment-button").bind("click", footerHandler);
            commentBinded = true;
        }

    }

    function addResolutionTemplate(place) {
        let container = $j('nav.editor-toggle-tabs ul');
        let prefixId = 'choosetemplate-' + place;
        $j("#" + prefixId + '-container').remove();
        container.append('<li id="'+prefixId+'-container"><select id="'+prefixId+'-select" class="aui-button"><option>---Select template---\n' +
            '\n' +
            '</option><option value="full">Full</option> <option value="short">Short</option> </select></li>');


        $j("#" + prefixId + '-select').on("change", function (event) {
            let commentField = $j(event.target).parents('.jira-wikifield').find('#comment');

            let value = commentField.val();
            if (event.target.value === 'full') {
                value += "{panel:borderStyle=dashed|borderColor=#cccccc|titleBGColor=#dddddd|bgColor=#deebff}\n" +
                    "* *Fix summary*\n" +
                    "  Setting `'persistent' => '1'` in `env.php` no longer throws an error when you run `setup:upgrade`\n" +
                    "* *Caused by extensions*  \n" +
                    "  Vendor_Extension - 1.34.1\n" +
                    "  app/code/Vendor/Extension/Observer/CustomObserver.php\n" +
                    "* *Issue identification*\n" +
                    "  *Log path:*\n" +
                    "    var/log/system.log\n" +
                    "  *Log message:*\n" +
                    "   {code}\n" +
                    "[2022-06-08T13:55:50.858649+00:00] report.ERROR: Magento\\Framework\\GraphQl\\Exception\\GraphQlInputException: \"postcode\" is required. Enter and try again. in /app/ef2udrg5x6pka/vendor/magento/app/code/Magento/QuoteGraphQl/Model/Cart/SetBillingAddressOnCart.php:182\n" +
                    "   {code}\n" +
                    "  *Config settings:* \n" +
                    "    carriers/fedex/active = 1\n" +
                    "* *Documentation Link*\n" +
                    "https://experienceleague.adobe.com/docs/commerce-admin/config/catalog/inventory.html?lang=en\n" +
                    "{panel}";
            } else {
                value += "{panel:borderStyle=dashed|borderColor=#cccccc|titleBGColor=#dddddd|bgColor=#deebff}\n" +
                    "* *Fix summary*\n" +
                    "  SUMMARY HERE\n" +
                    "* *Caused by extensions*  \n" +
                    "  N/A\n" +
                    "* *Issue identification*\n" +
                    "  N/A\n" +
                    "* *Documentation Link*\n" +
                    "  N/A\n" +
                    "{panel}";
            }

            commentField.val(value);
            commentField.css('height', '500px');

        })
    }


    function appendToJIRA(title, command, afterSelector) {
        const fieldsList = $j("#customfield-panel-1 ul.property-list");

        const id = "auto_" + makeid(10);
        const wrapId = id + "_wrap";
        if (existingIds.has(title)) {
            $j("#" + existingIds.get(title)).remove();
        }

        existingIds.set(title, wrapId);
        fieldsList.append("<li id=\""+wrapId+"\" class=\"item\">\n" +
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

        if(afterSelector) {
            $j("#" + id).insertAfter(afterSelector);
        }

        $j("#" + id).click(function () {
            copyToClipboard(this.value);
        });
    }

    function appendUrl(title, url, afterSelector) {
        const fieldsList = $j("#customfield-panel-1 ul.property-list");
        const id = "auto_" + makeid(10);

        const wrapId = id + "_wrap";
        if (existingIds.has(title)) {
            $j("#" + existingIds.get(title)).remove();
        }
        existingIds.set(title, wrapId);

        fieldsList.append("<li id=\""+wrapId+"\" class=\"item\">\n" +
            "        <div class=\"wrap\">\n" +
            "            <strong title=\"Company\" class=\"name\">\n" +
            "                                    <label for=\"customfield_10040\">"+title+":</label>\n" +
            "                            </strong>\n" +
            "            <div class=\"value type-textfield\" data-fieldtype=\"textfield\" style='width: 80%'>\n" + '' +
            "<a href='" + url +"'   style=\"width:50%\">" +
            url +
            "</a>                            </div>\n" +
            "        </div>\n" +
            "    </li>");

        if (afterSelector) {
            $j("#" + existingIds.get(title)).insertAfter(afterSelector);
        }
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

