function setRedColorInvalidAPIkey()
{
    var apiKeySelect = $('apikey');
    var text = apiKeySelect[apiKeySelect.selectedIndex].text;

    if(text.startsWith("[Invalid]")) {
        apiKeySelect.style.color = 'red';
    }
    else{
        apiKeySelect.style.color = 'black';
    }
}

function loadStores()
{
    var apiKey = $('apikey').value;

    $("listid").select('option').each(
        function (i) {
            i.remove();
        }
    );

    new Ajax.Request(
        GET_STORES_URL, {
            method: 'get',
            parameters: {api_key: apiKey},
            onComplete: function (transport) {
                var json = transport.responseText.evalJSON();

                if (!json.error) {
                    $H(json).each(
                        function (item) {
                            option = new Option(item.value.name, item.value.id);
                            $("listid").options.add(option);
                        }
                    );
                }
            }
        }
    );
    setRedColorInvalidAPIkey();
}

function loadApiKeys()
{
    let $apikey = $('apikey');

    if ($apikey != undefined && $apikey.offsetWidth > 0 && $apikey.offsetHeight > 0) {
        loadStores();
        $apikey.onchange = loadStores;
    }
}

if (document.loaded) {
    loadApiKeys();
} else {
    document.observe('dom:loaded', loadApiKeys);
}
