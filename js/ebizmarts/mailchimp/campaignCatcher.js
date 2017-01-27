    function getCampaign() {
        var urlparams = location.search.substr(1).split('&');
        var params = new Array();
        var mc_cid = null;
        for (var i = 0; i < urlparams.length; i++) {
            var param = urlparams[i].split('=');
            var key = param[0];
            var val = param[1];
            if (key && val) {
                params[key] = val;
            }
            if(key=='utm_source') {
                var reg = /^mailchimp-/;
                if(reg.exec(val)) {
                    var aux =val.split('-');
                    mc_cid = aux[1];
                }
            }
            else {
                if (key=='mc_cid') {
                    mc_cid = val;
                }
            }
        }

        if (mc_cid) {
            createCookie('mailchimp_campaign_id' , mc_cid, 3600*3);
            createCookie('maichimp_landing_page', location, 3600*3);
        }
    }

    function createCookie(name, value) {
        Mage.Cookies.set(name,value);
    }

    if (document.loaded) {
        getCampaign;
    } else {
        document.observe('dom:loaded', getCampaign);
    }