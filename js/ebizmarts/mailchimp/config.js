function loadStores() {
    var apiKey = $('mailchimp_general_apikey').value;
    $("mailchimp_general_storeid").select('option').each(function (i) {
        i.remove();
    });
    $("mailchimp_general_list").select('option').each(function (i) {
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
        }
    });
    loadList();
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
            var jsonInfo = transportInfo.responseText.evalJSON();
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

//@Todo If api key changes and then get back mark the same store as selected.
function loadList() {
    var storeId = $('mailchimp_general_storeid').value;
    var apiKey = $('mailchimp_general_apikey').value;
    $("mailchimp_general_list").select('option').each(function (i) {
        i.remove();
    });
    new Ajax.Request(MGETLISTURL, {
        method: 'get',
        parameters: {api_key: apiKey, mailchimp_store_id: storeId},
        onComplete: function (transport) {
            var json = transport.responseText.evalJSON();
            if (json.length) {
                for (var i = 0; i < json.length; i++) {
                    $option = new Option(json[i].label, json[i].value);
                    $("mailchimp_general_list").options.add($option);
                }
            }
        }
    });
    loadInfo();
}

function changeApikey() {
    loadStores();
}

function initAdmin() {
    $('mailchimp_general_apikey').onchange = changeApikey;
    $('mailchimp_general_storeid').onchange = loadList;
}


if (document.loaded) {
    initAdmin;
} else {
    document.observe('dom:loaded', initAdmin);
}
