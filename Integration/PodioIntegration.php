<?php
/**
 * @copyright 2016 Mautic Contributors. All rights reserved/
 * @author Mautic
 *
 * @link http://mautic.org
 *
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\PodioCrmBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration;

/**
 * Class PodioIntegration.
 */
class PodioIntegration extends CrmAbstractIntegration
{

    /**
     * Get array key for clientId.
     *
     * @return string
     */
    public function getClientIdKey()
    {
        return 'client_id';
    }

    /**
     * Get array key for client secret.
     *
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'client_secret';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'client_id'     => 'mautic.integration.keyfield.consumerid',
            'client_secret' => 'mautic.integration.keyfield.consumersecret',
        ];
    }

    /**
     * Get key for the refresh token and expiry.
     *
     *
     * @return array
     */
    public function getRefreshToken()
    {
        return ['refresh_token', ''];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAccessTokenUrl()
    {
        return 'https://podio.com/oauth/token';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationUrl()
    {
        return 'https://podio.com/oauth/authorize';
    }

    /**
     * @return string
     */
    public function getAuthScope()
    {
        return 'api refresh_token';
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return 'https://api.podio.com';
    }

    /**
     * {@inheritdoc}
     *
     * @param bool $inAuthorization
     */
    public function getBearerToken($inAuthorization = false)
    {
        if ( ! $inAuthorization) {
            return $this->keys[$this->getAuthTokenKey()];
        }

        return false;
    }

    /**
     * Get array key for auth token.
     *
     * @return string
     */
    public function getAuthTokenKey()
    {
        return 'access_token';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'oauth2';
    }

    public function getSupportedFeatures()
    {
        return ['push_lead'];
    }

    /**
     * Get available company fields for choices in the config UI.
     *
     * @param array $settings
     *
     * @return array
     */
    public function getFormCompanyFields($settings = [])
    {
        return $this->getFormFieldsByObject('company', $settings);
    }

    /**
     * @param array $settings
     *
     * @return array|mixed
     */
    public function getFormLeadFields($settings = [])
    {
        return $this->getFormFieldsByObject('contacts', $settings);
    }

    public function getFormLeadAppFields($settings = [])
    {
        return $this->getFormFieldsByObject('lead', $settings);
    }

    public function getContactsAppId()
    {
        return $this->keys['contacts_app_id'] ?? null;
    }

    public function getCompaniesAppId()
    {
        return $this->keys['companies_app_id'] ?? null;
    }

//    /**
//     * @return array
//     */
//    public function getLeadsAppId()
//    {
//        return $this->keys['leads_app_id'] ?? null;
//    }
//

    public function getSynchronizationTag($default = 'sync2mautic')
    {
        return $this->keys['synchronization_tag'] ?? $default;
    }

    /**
     * Format the lead data to the structure that Podio requires for the createOrUpdate request.
     *
     * @param array $leadData All the lead fields mapped
     *
     * @return array
     */
    public function formatLeadDataForCreateOrUpdate($leadData, $lead)
    {
        $formattedLeadData = [
            'external_id' => strval(is_array($lead) ? $lead['id'] : $lead->getId()),
        ];

        foreach ($leadData as $field => $value) {
            $formattedLeadData['fields'][$field] = is_array($value) ? array_values($value) : $value;
        }

        return $formattedLeadData;
    }

//    public function getContactFieldId($default = 'contact')
//    {
//        return $this->keys['contact_field_external_id'] ?? $default;
//    }
//
//    public function getCompanyFieldId($default = 'company')
//    {
//        return $this->keys['company_field_external_id'] ?? $default;
//    }

    /**
     * @param \Mautic\PluginBundle\Integration\Form|\Symfony\Component\Form\FormBuilder $builder
     * @param array $data
     * @param string $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea == 'keys') {
            $builder->add(
                'contacts_app_id',
                'text',
                [
                    'label'      => 'mautic.podio.app.contacts',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'required'   => true,
                ]
            );
            $builder->add(
                'companies_app_id',
                'text',
                [
                    'label'      => 'mautic.podio.app.company',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'required'   => true,
                ]
            );
//            $builder->add(
//                'leads_app_id',
//                'text',
//                [
//                    'label'      => 'mautic.podio.app.leads',
//                    'label_attr' => ['class' => 'control-label'],
//                    'attr'       => ['class' => 'form-control'],
//                    'required'   => false,
//                ]
//            );
        }

        if ($formArea == 'features') {

            $builder->add(
                'objects',
                'choice',
                [
                    'choices'     => [
                        'contacts' => 'mautic.podio.object.contact',
                        'company'  => 'mautic.podio.object.company',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => $this->getTranslator()->trans('mautic.crm.form.objects_to_pull_from',
                        ['%crm%' => 'Podio']),
                    'label_attr'  => ['class' => ''],
                    'empty_value' => false,
                    'required'    => false,
                ]
            );

//            $builder->add(
//                'synchronization_tag',
//                'text',
//                [
//                    'label'      => 'mautic.podio.app.synchronization_tag',
//                    'label_attr' => ['class' => 'control-label'],
//                    'attr'       => ['class' => 'form-control'],
//                    'required'   => false,
//                ]
//            );
//            $builder->add(
//                'contact_field_external_id',
//                'text',
//                [
//                    'label'      => 'mautic.podio.app.contact_field_external_id',
//                    'label_attr' => ['class' => 'control-label'],
//                    'attr'       => ['class' => 'form-control'],
//                    'required'   => false,
//                ]
//            );
//            $builder->add(
//                'company_field_external_id',
//                'text',
//                [
//                    'label'      => 'mautic.podio.app.company_field_external_id',
//                    'label_attr' => ['class' => 'control-label'],
//                    'attr'       => ['class' => 'form-control'],
//                    'required'   => false,
//                ]
//            );
        }

        if ($formArea == 'integration') {

            $builder->add(
                'push_to_podio_message',
                'textarea',
                [
                    'label'      => 'mautic.podio.push_message',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'required'   => false,

                ]
            );
        }
    }

    /**
     * Amend mapped lead data before creating to Mautic.
     *
     * @param array $data
     * @param $object
     *
     * @return array
     */
    public function amendLeadDataBeforeMauticPopulate($data, $object)
    {
        $fieldsValues = [];
        foreach ($data['fields'] ?? [] as $field) {
            $fieldsValues[$field['external_id']] = $field['values'][0]['value'];
        }

        if ( ! empty($data['tags'])) {
            $fieldsValues['tags'] = $data['tags'];
        }

        return $fieldsValues;
    }

    public function pushLead($lead, $config = [])
    {

        $config['config']['object'] = 'contacts';
        $config                     = $this->mergeConfigToFeatureSettings($config);

        if (empty($config['leadFields'])) {
            return [];
        }

        $mappedData = $this->populateLeadData($lead, $config);

        $this->amendLeadDataBeforePush($mappedData);

        if (empty($mappedData)) {
            return false;
        }

        try {
            if ($this->isAuthorized()) {
                return $this->getApiHelper()->createLead($mappedData, $lead, $config);
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return false;
    }

    public function populateLeadData($lead, $config = [])
    {
        if ( ! isset($config['leadFields'])) {
            $config = $this->mergeConfigToFeatureSettings($config);

            if (empty($config['leadFields'])) {
                return [];
            }
        }

        return $this->populateData($lead, $config['leadFields'], $config);
    }

    protected function populateData($lead, $leadFields, $config)
    {
        if ($lead instanceof Lead) {
            $fields = $lead->getFields(true);
        } else {
            $fields = $lead;
        }
        $config['cache_suffix'] = '.' . $config['object'];
        $availableFields        = $this->getAvailableLeadFields($config);

        $unknown = $this->factory->getTranslator()->trans('mautic.integration.form.lead.unknown');
        $matched = [];

        foreach ($availableFields as $integrationKey => $integrationFields) {
            if (isset($leadFields[$integrationKey])) {
                $mauticKey = $leadFields[$integrationKey];
                if (isset($fields[$mauticKey]) && ! empty($fields[$mauticKey])) {
                    $matched[$integrationKey]['value'] = strval($fields[$mauticKey]['value']);
                    if ($mauticKey === 'email') {
                        $matched[$integrationKey]['type'] = 'other';
                    }
                }
            }

            if ( ! empty($field['required']) && empty($matched[$integrationKey])) {
                $matched[$integrationKey] = $unknown;
            }
        }

        return $matched;
    }

    /**
     * @param array $settings
     *
     * @return array|mixed
     * @throws \Exception
     */
    public function getAvailableLeadFields($settings = [])
    {

        if ($fields = parent::getAvailableLeadFields($settings)) {
            return $fields;
        }

        $podioFields       = [];
        $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;

        if (isset($settings['feature_settings']['objects'])) {
            $podioObjects = $settings['feature_settings']['objects'];
        } else {
            $settings     = $this->settings->getFeatureSettings();
            $podioObjects = isset($settings['objects']) ? $settings['objects'] : ['contacts'];
        }

        try {
            if ($this->isAuthorized()) {
                if ( ! empty($podioObjects) and is_array($podioObjects)) {
                    foreach ($podioObjects as $key => $object) {
                        // Check the cache first
                        $settings['cache_suffix'] = $cacheSuffix = '.' . $object;
                        if ($fields = parent::getAvailableLeadFields($settings)) {
                            $podioFields[$object] = $fields;

                            continue;
                        }

                        $leadFields = $this->getApiHelper()->getLeadFields($object);
                        if (isset($leadFields)) {
                            foreach ($leadFields as $fieldInfo) {
                                $podioFields[$object][$fieldInfo['external_id']] = [
                                    'type'     => 'string',
                                    'label'    => $fieldInfo['config']['label'],
                                    'required' => $fieldInfo['config']['required'],
                                ];
                            }
                        }

                        $this->cache->set('leadFields' . $cacheSuffix, $podioFields[$object]);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);

            if ( ! $silenceExceptions) {
                throw $e;
            }
        }

        return $podioFields;
    }

    /**
     * Get the API helper.
     *
     * @return object
     */
    public function getApiHelper()
    {
        if (empty($this->helper)) {
            $class        = '\\MauticPlugin\\PodioCrmBundle\\Api\\' . $this->getName() . 'Api';
            $this->helper = new $class($this);
        }

        return $this->helper;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Podio';
    }

    public function populateCompanyData($lead, $config = [])
    {
        if ( ! isset($config['companyFields'])) {
            $config = $this->mergeConfigToFeatureSettings($config);

            if (empty($config['companyFields'])) {
                return [];
            }
        }

        return $this->populateData($lead, $config['companyFields'], $config);
    }
}
