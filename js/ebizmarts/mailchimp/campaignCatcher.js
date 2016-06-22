
    function getCampaign() {
        var params = location.search.substr(1).split('&');
        var cookies = new Array();
        for (var i = 0; i < params.length; i++) {
            var cookie = params[i].split('=');
            var key = cookie[0];
            var val = cookie[1];
            if (key && val) {
                cookies[key] = val;
            }
        }

        if (cookies['mc_cid']) {
            createCookie('mailchimp_campaign_id=' + cookies['mc_cid'], 30);
        }
        // if (cookies['mc_eid']) {
        //     createCookie('mailchimp_email_id=' + cookies['mc_eid'], 30);
        // }
    }

    function createCookie(cookie, expirationInDays) {
        var now = new Date();
        var expire = new Date(now.getTime() + (expirationInDays * 24 * 60) * 60000);//[(1 * 365 * 24 * 60) * 60000] == 1 year  -- (Years * Days * Hours * Minutes) * 60000
        document.cookie = cookie + '; expires=' + expire + '; path=/';
    }

    if (document.loaded) {
        getCampaign;
    } else {
        document.observe('dom:loaded', getCampaign);
    }