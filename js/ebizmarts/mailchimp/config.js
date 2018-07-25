function loadStores() {
    $apiKey = $('mailchimp_general_apikey').value;
    $("mailchimp_general_storeid").select('option').each(function (i) {
        i.remove();
    });
    $("mailchimp_general_list").select('option').each(function (i) {
        i.remove();
    });
    new Ajax.Request(MGETSTORESRUL, {
            method: 'get',
            parameters: {apikey:$apiKey},
            onComplete: function (transport) {
                var json = transport.responseText.evalJSON(true);
                if(json.error==undefined) {
                    if (json.length) {
                        for(var i=0;i<json.length;i++) {
                            option = new Option(json[i].name, json[i].id);
                            $("mailchimp_general_storeid").options.add(option);
                        }
                    }
                } else {
                    alert('Wrong ApiKey');
                }
                loadInfo();
            }
    });
}
function loadInfo() {
    $storeid = $('mailchimp_general_storeid').value;
    $apiKey = $('mailchimp_general_apikey').value;
    $('mailchimp_general_account_details').select('li').each(function (i) {
        i.remove();
    });
    new Ajax.Request(MGETINFOURL, {
        method: 'get',
        parameters: {apikey:$apiKey, storeid:$storeid},
        onComplete: function (transportInfo) {
            var jsonInfo = transportInfo.responseText.evalJSON();
            if (jsonInfo.length) {
                for(var i=0;i<jsonInfo.length;i++) {
                    $('mailchimp_general_account_details').insert("<li>"+jsonInfo[i].label+"</li>");
                }
            }
        }
    });
}
function loadList() {
    $storeid = $('mailchimp_general_storeid').value;
    $apiKey = $('mailchimp_general_apikey').value;
    $("mailchimp_general_list").select('option').each(function (i) {
        i.remove();
    });
    new Ajax.Request(MGETLISTURL, {
        method: 'get',
        parameters: {apikey:$apiKey, storeid:$storeid},
        onComplete: function (transport) {
            var json = transport.responseText.evalJSON();
            $option = new Option(json.name, json.id);
            $("mailchimp_general_list").options.add($option);
            loadInfo();
        }
    });
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
