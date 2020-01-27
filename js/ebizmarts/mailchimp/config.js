var listSelected = null;
var interestSelected = [];
var firstTime = true;

function loadStores()
{
    var apiKey = $('mailchimp_general_apikey').value;
    $("mailchimp_general_storeid").select('option').each(
        function (i) {
            i.remove();
        }
    );
    new Ajax.Request(
        MGETSTORESURL, {
            method: 'get',
            parameters: {api_key: apiKey},
            onComplete: function (transport) {
                var json = transport.responseText.evalJSON(true);
                if (json.length) {
                    for (var i = 0; i < json.length; i++) {
                        option = new Option(json[i].label, json[i].value);
                        $("mailchimp_general_storeid").options.add(option);
                    }
                }
                loadList();
            }
        }
    );
}

function validateAPIkey($apiKeyInput, jsonInfo)
{
    $apiKeyInput.style.border = "solid 2px red";
    let comment = $$('#row_mailchimp_general_apikey p.note')[0];
    let paragraph = comment.cloneNode(true);
    paragraph.firstChild.textContent = "API key is invalid";
    paragraph.style.color = "red";
    paragraph.id = "invalidAPIkey";
    comment.parentNode.insertBefore(paragraph, comment.previousSibling);

    $('mailchimp_general_account_details').insert("<li>" + jsonInfo[0].label + "</li>");
}

function loadInfo()
{
    const syncLabelKey = 11;
    var storeId = $('mailchimp_general_storeid').value;

    let $apiKeyInput = $('mailchimp_general_apikey');
    var apiKey = $apiKeyInput.value;
    $('mailchimp_general_account_details').select('li').each(
        function (i) {
            i.remove();
        }
    );
    new Ajax.Request(
        MGETINFOURL, {
            method: 'get',
            parameters: {api_key: apiKey, mailchimp_store_id: storeId},
            onComplete: function (transportInfo) {
                var jsonInfo = transportInfo.responseText.evalJSON(true);

                if (jsonInfo.length) {
                    if (jsonInfo[0].label === "--- Invalid API Key ---") {
                        validateAPIkey($apiKeyInput, jsonInfo);
                        return;
                    }
                    for (var i = 0; i < jsonInfo.length; i++) {
                        if (jsonInfo[i].value === syncLabelKey) {
                            $('mailchimp_general_account_details').insert(jsonInfo[i].label);
                        } else {
                            $('mailchimp_general_account_details').insert("<li>" + jsonInfo[i].label + "</li>");
                        }
                    }
                }
            }
        }
    );
}

function loadList()
{
    var storeId = $('mailchimp_general_storeid').value;
    var apiKey = $('mailchimp_general_apikey').value;
    var listId = $("mailchimp_general_list");

    listId.select('option').each(
        function (i) {
            if (i.selected && firstTime) {
                if (i.value !== '') {
                    listSelected = i.value;
                }
                firstTime = false;
            }
            i.remove();
        }
    );

    new Ajax.Request(
        MGETLISTURL, {
            method: 'get',
            parameters: {api_key: apiKey, mailchimp_store_id: storeId},
            onComplete: function (transport) {
                var json = transport.responseText.evalJSON(true);

                if (json.length) {
                    for (var i = 0; i < json.length; i++) {
                        if (json[i].value === listSelected || (json.length === 1)) {
                            var inheritField = $('mailchimp_general_list_inherit');
                            var option = null;

                            if (inheritField && inheritField.checked === true) {
                                inheritField.checked = false;
                                $("mailchimp_general_list").disabled = false;
                            }
                            option = new Option(json[i].label, json[i].value, true, true);
                        } else {
                            option = new Option(json[i].label, json[i].value);
                        }
                        listId.options.add(option);
                    }
                }

                loadInfo();
                loadInterest();
            }
        }
    );
}

function loadInterest()
{
    var listOptions = $('mailchimp_general_list');
    var index = listOptions.selectedIndex;
    var listIdOptions = listOptions.options[index];

    $("mailchimp_general_interest_categories").select('option').each(
        function (i) {
            if (i.selected && firstTime) {
                interestSelected[i.value] = true;
            }
            i.remove();
        }
    );

    if (listIdOptions != null) {
        var listId = listIdOptions.value;
        var apiKey = $('mailchimp_general_apikey').value;

        new Ajax.Request(
            MGETINTERESTURL, {
                method: 'get',
                parameters: {api_key: apiKey, list_id: listId},
                onComplete: function (transport) {
                    var json = transport.responseText.evalJSON(true);

                    if (json.length) {
                        for (var i = 0; i < json.length; i++) {
                            var option = null;

                            if (interestSelected[json[i].value] === true) {
                                option = new Option(json[i].label, json[i].value, true, true);
                            } else {
                                option = new Option(json[i].label, json[i].value);
                            }
                            $("mailchimp_general_interest_categories").options.add(option);
                        }
                    }
                }
            }
        );
    }
}

function changeApikey()
{
    let apiKeyInput = $('mailchimp_general_apikey');
    apiKeyInput.style.border = "";
    let invalidAPIkey = $('invalidAPIkey');

    if (invalidAPIkey) {
        invalidAPIkey.remove();
    }

    loadStores();
}

function initAdmin()
{
    $('mailchimp_general_apikey').onchange = changeApikey;
    $('mailchimp_general_storeid').onchange = loadList;
    $('mailchimp_general_list').onchange = loadInterest;
}


if (document.loaded) {
    initAdmin();
} else {
    document.observe('dom:loaded', initAdmin);
}
