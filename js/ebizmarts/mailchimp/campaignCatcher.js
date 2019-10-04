function getCampaign()
{
    let urlparams = location.search.substr(1).split('&');
    let params = [];
    let mc_cid = null;
    let campaign = null;
    let isMailchimp = false;
    let mc_eid = null;
    let paramsLength = urlparams.length;

    if (paramsLength > 0) {
        for (let i = 0; i < paramsLength; i++) {
            let param = urlparams[i].split('=');
            let key = param[0];
            let val = param[1];

            if (key && val) {
                params[key] = val;
            } else {
                continue;
            }

            if (key === 'utm_source') {
                let reg = /^mailchimp$/;

                if (reg.exec(val)) {
                    isMailchimp = true;
                }
            }

            if (key === 'mc_cid') {
                mc_cid = val;
            }

            if (key === 'utm_campaign') {
                let campaignArray = val.split("-");
                let campaignValue = campaignArray[0];

                if (campaignValue.length === 10) {
                    campaign = campaignValue;
                }
            }

            if (key === 'mc_eid') {
                mc_eid = val;
            }
        }
    } else {
        urlparams = location.pathname.split('/');
        let utmIndex = $.inArray('utm_source', urlparams);
        let mccidIndex = $.inArray('mc_cid', urlparams);

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

        if (mc_cid && !isMailchimp) {
            Mage.Cookies.clear('mailchimp_campaign_id');
        }
    }

    if (mc_cid) {
        Mage.Cookies.clear('mailchimp_campaign_id');
        Mage.Cookies.set('mailchimp_campaign_id', mc_cid);
    } else {
        if (campaign) {
            Mage.Cookies.clear('mailchimp_campaign_id');
            Mage.Cookies.set('mailchimp_campaign_id', campaign);
        }
    }
    let landingPage = Mage.Cookies.get('mailchimp_landing_page');

    if (!landingPage) {
        Mage.Cookies.set('mailchimp_landing_page', location);
    }

    if (mc_eid) {
        Mage.Cookies.set('mailchimp_email_id', mc_eid);
    }
}

if (document.loaded) {
    getCampaign;
} else {
    document.observe('dom:loaded', getCampaign);
}
