$j(document).ready(function() {
    $j('#mailchimp_general_apikey').change(function () {
        var apiKey = $j('#mailchimp_general_apikey').val();
        _loadLists(apiKey);
        });
});

_loadLists = function (apiKey) {
    $j('#mailchimp_general_list').empty();
    var url=$j('#mailchimp-getlist-url').val()
    $j.ajax({
        url: url,
        data: {'apikey': apiKey},
        type: 'GET',
        dataType: 'json',
        showLoader: true
    }).done(function (data) {
        $j.each(data, function (i, item) {
            $j('#mailchimp_general_list').append($j('<option>',{
                value: item.id,
                text: item.label
            }));
        })
    });
    _loadDetails(apiKey);
};

_loadDetails = function (apiKey) {
    $j('#mailchimp_general_account_details_ul').empty();
    var url=$j('#mailchimp-getdetails-url').val();
    $j.ajax({
        url: url,
        data: {'apikey': apiKey},
        type: 'GET',
        dataType: 'json',
        showLoader: true
    }).done(function (data) {
        $j.each(data, function (i,item) {
            if (item.hasOwnProperty('label')) {
                console.log(item.label);
                $j('#mailchimp_general_account_details_ul').append('<li>' + item.label + ' ' + item.value + '</li>');
            }
        });

    });


};
