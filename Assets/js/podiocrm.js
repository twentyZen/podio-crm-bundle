/* PodioCrmBundle */

Mautic.podioCrmUpdateOrganisation = function (el) {
    Mautic.activateLabelLoadingIndicator(mQuery(el).attr('id'));

    var contactsAppField = mQuery('#integration_details_apiKeys_contacts_app_id');
    var companiesAppField = mQuery('#integration_details_apiKeys_companies_app_id');
    var leadsAppField = mQuery('#integration_details_apiKeys_leads_app_id');

    contactsAppField.html('');
    contactsAppField.chosen('destroy');

    companiesAppField.html('');
    companiesAppField.chosen('destroy');

    leadsAppField.html('');
    leadsAppField.chosen('destroy');

    Mautic.ajaxActionRequest('plugin:PodioCrm:getApps', {
            settings: {
                organisation_id: mQuery(el).val()
            }
        },
        function (response) {
            var options = '';
            for (var key in response) {
                var obj = response[key];
                options += '<optgroup label="' + key + '">';
                for (var prop in obj) {
                    options += '<option value="' + prop + '">' + obj[prop] + '</option>';
                }
                options += '</optgroup>';
            }


            var chosenOptions = {
                width: "100%",
                allow_single_deselect: true,
                include_group_label_in_selected: true,
                search_contains: true
            };

            contactsAppField.html(options);
            contactsAppField.chosen(chosenOptions);

            companiesAppField.html(options);
            companiesAppField.chosen(chosenOptions);

            leadsAppField.html(options);
            leadsAppField.chosen(chosenOptions);

            Mautic.removeLabelLoadingIndicator();
        }
    );
};

Mautic.setPodioLeadAppId = function (el, $id) {

    Mautic.activateLabelLoadingIndicator(mQuery(el).attr('id'));
    var leadAppContactField = mQuery('#integration_details_featureSettings_lead_contact_field_id');
    var leadAppCompanyField = mQuery('#integration_details_featureSettings_lead_company_field_id');

    leadAppContactField.html('');
    leadAppContactField.chosen('destroy');

    leadAppCompanyField.html('');
    leadAppCompanyField.chosen('destroy');

    Mautic.ajaxActionRequest('plugin:PodioCrm:setLeadApp', {
            settings: {
                lead_app_id: $id
            }
        },
        function (response) {
            var options = '';
            var fields = response['fields'] || {};
            for (var key in fields) {
                options += '<option value="' + key + '">' + fields[key] + '</option>';
            }

            var chosenOptions = {
                width: "100%",
                allow_single_deselect: true,
                include_group_label_in_selected: true,
                search_contains: true
            };

            leadAppContactField.html(options);
            leadAppContactField.chosen(chosenOptions);

            leadAppCompanyField.html(options);
            leadAppCompanyField.chosen(chosenOptions);

            Mautic.removeLabelLoadingIndicator();
        }
    );
};