function loadStores() {
    var $apiKey = $('apikey').value;
    $("listid").select('option').each(function (i) {
        i.remove();
    });
    new Ajax.Request(GET_STORES_URL, {
            method: 'get',
            parameters: {apikey:$apiKey},
            onComplete: function (transport) {
                var json = transport.responseText.evalJSON();
                $H(json).each(function (item) {
                    option = new Option(item.value.name,item.value.id);
                    $("listid").options.add(option);
                });
            }
        }
        );
}

function loadApiKeys()
{
    if($('apikey')!= undefined) {
        loadStores();
        $("apikey").onchange = loadStores;
    }
}

if (document.loaded) {
    loadApiKeys;
} else {
    document.observe('dom:loaded', loadApiKeys);
}
