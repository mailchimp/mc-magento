function getCampaign() {
    let urlparams = null;
    let isGet = location.search.search('\\?');
    let mc_cid = null;
    let isMailchimp = false;

    if (isGet !== -1) {
        urlparams = getUrlVars();
        urlparams.forEach(
            function (item) {
                if (item.key === 'utm_source') {
                    let reg = /^mailchimp$/;

                    if (reg.exec(item.value)) {
                        isMailchimp = true;
                    }
                } else {
                    if (item.key === 'mc_cid') {
                        mc_cid = item.value;
                    }
                }
            }
        );
    } else {
        urlparams = location.href.split('/');
        let utmIndex = jQuery.inArray('utm_source', urlparams);
        let mccidIndex = jQuery.inArray('mc_cid', urlparams);

        if (utmIndex !== -1) {
            let value = urlparams[utmIndex + 1];
            let reg = /^mailchimp$/;

            if (reg.exec(value)) {
                isMailchimp = true;
            }
        } else {
            if (mccidIndex !== -1) {
                mc_cid = urlparams[mccidIndex + 1];
            }
        }
    }

    if (mc_cid && !isMailchimp) {
        Mage.Cookies.clear('mailchimp_campaign_id');
        Mage.Cookies.set('mailchimp_campaign_id', mc_cid);
    }

    let landingPage = Mage.Cookies.get('mailchimp_landing_page');

    if (!landingPage) {
        Mage.Cookies.set('mailchimp_landing_page', location);
    }

    if (isMailchimp) {
        Mage.Cookies.clear('mailchimp_campaign_id');
        Mage.Cookies.set('mailchimp_landing_page', location);
    }
}

function getUrlVars() {
    let vars = [];
    let i = 0;
    window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi,
        function (m, key, value) {
            vars[i] = {'value': value, 'key': key};
            i++;
        }
    );
    return vars;
}

if (document.loaded) {
    getCampaign();
} else {
    document.observe('dom:loaded', getCampaign);
}
