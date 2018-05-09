# Podio CRM Bundle

This is a [Mautic](https://www.mautic.org/) Plugin that allows you to use [Podio](https://podio.com/site) as CRM Integration

## Features
* Push contact to Podio (create new or update when already exist)
* Push related company to Podio (create new or update when already exist)
* Create a simple Lead, that consist of contact and company
* Add a comment to the Lead App specified in the "push to integration" Mautic action 

## Installation

If you have chosen composer to manage you Mautic installation, you can easily install this package via composer
```
composer require twentyzen/podio-crm-bundle
``` 

You can also install it manually, by downloading the latest release

## Configuration
Prerequisite
* You need an Podio API key for your mautic. For help see [Generating API keys for Podio](https://developers.podio.com/api-key)

After installation
* Go to Plugins -> Podio
* Provide you "Client ID" and "Client Secret" in the "Enabled/Auth" tab 
* Be sure to check "Triggered action push contacts to integration" in the "Features" tab
* Be sure to check both checkboxes in the "Features" tab
* Go back to the "Enabled/Auth" tab and click the "Authorize App" button
* If you get a blocking pop-up notification, just allow pop-ups and try again
* Podio will ask you to allow mautic all permissions. I know this seems strange, but this is how it is working at the moment. Just allow.

After successful authorisation, you need to tell Mautic, which Apps should be use.
* In the "Enable/Auth" tab, select you desired organisation and apps you are using for your CRM
* Use the "Contact Mapping" and "Company Mapping" tabs to map Mautic fields with your Podio Fields (not all Podio fields are allowed)
* If you have a "Leads App" that references the "Contacts App" and the "Companies App", you can map the reference in the "Features" tab

Save &amp; Close
## Known Limitations

* Not all Podio Field types are supported. See the list of supported fields below:
    * duration
    * app
    * number
    * text
    * email
    * phone
    * embed