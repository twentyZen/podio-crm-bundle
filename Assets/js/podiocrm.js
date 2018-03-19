/* PodioCrmBundle */
Mautic.podioCrmUpdateOrganisation = function (el) {
    Mautic.activateLabelLoadingIndicator('#integration_details_apiKeys_contacts_app_id');
    Mautic.activateLabelLoadingIndicator('#integration_details_apiKeys_companies_app_id');
    Mautic.activateLabelLoadingIndicator('#integration_details_apiKeys_leads_app_id');

    var contactsAppField = mQuery('#integration_details_apiKeys_contacts_app_id');
    var companiesAppField = mQuery('#integration_details_apiKeys_companies_app_id');
    var leadsAppField = mQuery('#integration_details_apiKeys_leads_app_id');

    var chosenOptions = {
        include_group_label_in_selected: true,
        width: '100%'
    };


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

