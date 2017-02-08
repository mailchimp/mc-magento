    function getCampaign() 
{
        var urlparams = location.search.substr(1).split('&');
        var params = new Array();
        var mc_cid = null;
        var isMailchimp = false;
        for (var i = 0; i < urlparams.length; i++) {
            var param = urlparams[i].split('=');
            var key = param[0];
            var val = param[1];
            if (key && val) {
                params[key] = val;
            }

            if(key=='utm_source') {
                var reg = /^mailchimp$/;
                if(reg.exec(val)) {
                    isMailchimp = true;
                }
            }
            else {
                if (key=='mc_cid') {
                    mc_cid = val;
                }
            }
        }

        if (mc_cid&&!isMailchimp) {
            Mage.Cookies.set('mailchimp_campaign_id' , mc_cid);
            Mage.Cookies.set('mailchimp_landing_page', location);
        }

        if(isMailchimp) {
            Mage.Cookies.clear('mailchimp_campaign_id');
            Mage.Cookies.set('mailchimp_landing_page', location);
        }
    }
    if (document.loaded) {
        getCampaign;
    } else {
        document.observe('dom:loaded', getCampaign);
    }