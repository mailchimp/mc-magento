var listSelected = null;
var interestSelected = [];
var firstTime = true;

function loadStores() {
    var apiKey = $('mailchimp_general_apikey').value;
    $("mailchimp_general_storeid").select('option').each(function (i) {
        i.remove();
    });
    new Ajax.Request(MGETSTORESRUL, {
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
    });
}

function loadInfo() {
    const syncLabelKey = 11;
    var storeId = $('mailchimp_general_storeid').value;
    var apiKey = $('mailchimp_general_apikey').value;
    $('mailchimp_general_account_details').select('li').each(function (i) {
        i.remove();
    });
    new Ajax.Request(MGETINFOURL, {
        method: 'get',
        parameters: {api_key: apiKey, mailchimp_store_id: storeId},
        onComplete: function (transportInfo) {
            var jsonInfo = transportInfo.responseText.evalJSON(true);
            if (jsonInfo.length) {
                for (var i = 0; i < jsonInfo.length; i++) {
                    if (jsonInfo[i].value == syncLabelKey) {
                        $('mailchimp_general_account_details').insert(jsonInfo[i].label);
                    } else {
                        $('mailchimp_general_account_details').insert("<li>" + jsonInfo[i].label + "</li>");
                    }
                }
            }
        }
    });
}

function loadList() {
    var storeId = $('mailchimp_general_storeid').value;
    var apiKey = $('mailchimp_general_apikey').value;
    var listId = $("mailchimp_general_list");
    listId.select('option').each(function (i) {
        if (i.selected && firstTime) {
            if (i.value !== '') {
                listSelected = i.value;
            }
            firstTime = false;
        }
        i.remove();
    });
    new Ajax.Request(MGETLISTURL, {
        method: 'get',
        parameters: {api_key: apiKey, mailchimp_store_id: storeId},
        onComplete: function (transport) {
            var json = transport.responseText.evalJSON(true);
            if (json.length) {
                for (var i = 0; i < json.length; i++) {
                    if (json[i].value === listSelected || (json.length === 1)) {
                        var inheritField = $('mailchimp_general_list_inherit');
                        if (inheritField && inheritField.checked === true) {
                            inheritField.checked = false;
                            $("mailchimp_general_list").disabled = false;
                        }
                        var option = new Option(json[i].label, json[i].value, true, true);
                    } else {
                        var option = new Option(json[i].label, json[i].value);
                    }
                    listId.options.add(option);
                }
            }
            loadInfo();
            loadInterest();
        }
    });
}

function loadInterest() {
    var listOptions = $('mailchimp_general_list');
    var index = listOptions.selectedIndex;
    var listId = listOptions.options[index].value;
    var apiKey = $('mailchimp_general_apikey').value;

    $("mailchimp_general_interest_categories").select('option').each(function (i) {
        if (i.selected && firstTime) {
            interestSelected[i.value] = true;
        }
        i.remove();
    });
    new Ajax.Request(MGETINTERESTURL, {
        method: 'get',
        parameters: {api_key: apiKey, list_id: listId},
        onComplete: function (transport) {
            var json = transport.responseText.evalJSON(true);
            if (json.length) {
                for (var i = 0; i < json.length; i++) {
                    if (interestSelected[json[i].value] === true) {
                        var option = new Option(json[i].label, json[i].value, true, true);
                    } else {
                        var option = new Option(json[i].label, json[i].value);
                    }
                    $("mailchimp_general_interest_categories").options.add(option);
                }
            }
        }
    });
}

function changeApikey() {
    loadStores();
}

function initAdmin() {
    $('mailchimp_general_apikey').onchange = changeApikey;
    $('mailchimp_general_storeid').onchange = loadList;
    $('mailchimp_general_list').onchange = loadInterest;
}


if (document.loaded) {
    initAdmin;
} else {
    document.observe('dom:loaded', initAdmin);
}
