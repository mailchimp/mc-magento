function getCampaign() {
    var urlparams = location.search.substr(1).split('&');
    var params = new Array();
    var mc_cid = null;
    var campaign = null;
    var isMailchimp = false;
    for (var i = 0; i < urlparams.length; i++) {
        var param = urlparams[i].split('=');
        var key = param[0];
        var val = param[1];
        if (key && val) {
            params[key] = val;
        }
        if (key == 'utm_source') {
            var reg = /^mailchimp$/;
            if (reg.exec(val)) {
                isMailchimp = true;
            }
        }
        if (key == 'mc_cid') {
            mc_cid = val;
        }
        if (key == 'utm_campaign') {
            var campaignArray = val.split("-");
            var campaignValue = campaignArray[0];
            if (campaignValue.length == 10)
            campaign = campaignValue;
        }
    }
    if (mc_cid) {
        Mage.Cookies.set('mailchimp_campaign_id', mc_cid);
    } else {
        if (campaign) {
            Mage.Cookies.set('mailchimp_campaign_id', campaign);
        }
    }
    Mage.Cookies.set('mailchimp_landing_page', location);
}

if (document.loaded) {
    getCampaign;
} else {
    document.observe('dom:loaded', getCampaign);
}