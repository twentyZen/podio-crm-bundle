<?php

/**
 * @copyright   2018 twentyZen. All rights reserved
 * @author      Robert Juzak
 *
 * @link        https://twentyzen.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\PodioCrmBundle\Integration;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration;
use MauticPlugin\PodioCrmBundle\Api\PodioApi;

/**
 * Class PodioIntegration.
 */
class PodioIntegration extends CrmAbstractIntegration
{
    protected $organizations;
    protected $workspaces;
    protected $apps;


    /**
     * Get key for the refresh token and expiry.
     *
     *
     * @return array
     */
    public function getRefreshTokenKeys()
    {
        return [
            'refresh_token',
            'expires_in',
        ];
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
     * Get the API helper.
     *
     * @return PodioApi
     */
    public function getApiHelper()
    {
        if (empty($this->helper)) {
            $class = '\\MauticPlugin\\PodioCrmBundle\\Api\\' . $this->getName() . 'Api';
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

    /**
     * {@inheritdoc}
     *
     * @param bool $inAuthorization
     */
    public function getBearerToken($inAuthorization = false)
    {
        if (!$inAuthorization) {
            return $this->keys[$this->getAuthTokenKey()];
        }

        return false;
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

//    public function getFormTemplate()
//    {
//        return 'PodioCrmBundle:Integration:form.html.php';
//    }

    public function getOrganisationId()
    {
        return $this->keys['organisation_id'] ?? null;
    }

    public function getContactsAppId()
    {
        if ($this->getOrganisationId()) {
            return $this->keys['contacts_app_id'] ?? null;
        }

        return null;
    }

    public function getCompaniesAppId()
    {
        if ($this->getOrganisationId()) {
            return $this->keys['companies_app_id'] ?? null;
        }

        return null;
    }

    public function getLeadsAppId()
    {
        if ($this->getOrganisationId()) {
            return $this->keys['leads_app_id'] ?? null;
        }

        return null;
    }

    public function getLeadContactFieldId()
    {
        if ($this->getLeadsAppId()) {
            return $this->keys['lead_contact_field_id'] ?? null;
        }

        return null;
    }

    public function getLeadCompanyFieldId()
    {
        if ($this->getLeadsAppId()) {
            return $this->keys['lead_company_field_id'] ?? null;
        }

        return null;
    }

//    public function getSynchronizationTag($default = 'sync2mautic')
//    {
//        return $this->keys['synchronization_tag'] ?? $default;
//    }

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
            'fields' => [],
        ];

        foreach ($leadData as $field => $values) {
            if ($values) {
                $formattedLeadData['fields'][$field] = $values;
            }
        }

        return $formattedLeadData;
    }

    /**
     * @param \Mautic\PluginBundle\Integration\Form|\Symfony\Component\Form\FormBuilder $builder
     * @param array $data
     * @param string $formArea
     * @throws \Exception
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
//        if ($formArea == 'keys') {

//            $builder->add(
//                'organisation_id',
//                'choice',
//                [
//                    'choices' => $this->getAvailableOrganisations(),
//                    'label' => 'mautic.podio.organisation',
//                    'label_attr' => ['class' => 'control-label'],
//                    'required' => true,
////                    'attr'       => [
////                        'class'    => 'form-control',
////                        'onchange' => 'Mautic.getIntegrationConfig(this);',
////                    ],
//                ]
//            );
//            if ($this->getOrganisationId()) {
//                $builder->add(
//                    'contacts_app_id',
//                    'choice',
//                    [
//                        'choices' => $this->getAvailableApps(),
//                        'label' => 'mautic.podio.app.contacts',
//                        'label_attr' => ['class' => 'control-label'],
//                        'attr' => ['class' => 'form-control'],
//                        'required' => true,
//                    ]
//                );
//            }
//            if ($this->getOrganisationId()) {
//                $builder->add(
//                    'companies_app_id',
//                    'choice',
//                    [
//                        'choices' => $this->getAvailableApps(),
//                        'label' => 'mautic.podio.app.companies',
//                        'label_attr' => ['class' => 'control-label'],
//                        'required' => true,
//                    ]
//                );
//            }
//            if ($this->getOrganisationId()) {
//                $builder->add(
//                    'leads_app_id',
//                    'choice',
//                    [
//                        'choices' => $this->getAvailableApps(),
//                        'label' => 'mautic.podio.app.leads',
//                        'label_attr' => ['class' => 'control-label'],
//                        'required' => false,
//                    ]
//                );
//            }
//
//            if ($this->getLeadsAppId()) {
//                $settings['ignore_field_cache'] = true;
//                $settings['feature_settings']['objects']['lead'] = 'lead';
//                $builder->add(
//                    'lead_contact_field_id',
//                    'choice',
//                    [
//                        'choices' => $this->getAvailableLeadFields($settings)['lead'] ?? [],
//                        'label' => 'mautic.podio.app.lead_contact_field',
//                        'label_attr' => ['class' => 'control-label'],
//                        'required' => true,
//                    ]
//                );
//            }
//
//            if ($this->getLeadsAppId()) {
//                $settings['ignore_field_cache'] = true;
//                $settings['feature_settings']['objects']['lead'] = 'lead';
//                $builder->add(
//                    'lead_company_field_id',
//                    'choice',
//                    [
//                        'choices' => $this->getAvailableLeadFields($settings)['lead'] ?? [],
//                        'label' => 'mautic.podio.app.lead_company_field',
//                        'label_attr' => ['class' => 'control-label'],
//                        'required' => true,
//                    ]
//                );
//            }
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
//        }

        if ($formArea == 'features') {

            $builder->add(
                'objects',
                'choice',
                [
                    'choices' => [
                        'contacts' => 'mautic.podio.object.contact',
                        'company' => 'mautic.podio.object.company',
                    ],
                    'expanded' => true,
                    'multiple' => true,
                    'label' => $this->getTranslator()->trans('mautic.crm.form.objects_to_pull_from',
                        ['%crm%' => 'Podio']),
                    'label_attr' => ['class' => ''],
                    'empty_value' => false,
                    'required' => false,
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
        }

        if ($formArea == 'integration') {

            $builder->add(
                'push_to_podio_message',
                'textarea',
                [
                    'label' => 'mautic.podio.push_message',
                    'label_attr' => ['class' => 'control-label'],
                    'attr' => ['class' => 'form-control'],
                    'required' => false,

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

        if (!empty($data['tags'])) {
            $fieldsValues['tags'] = $data['tags'];
        }

        return $fieldsValues;
    }

    public function pushLead($lead, $config = [])
    {

        $config['config']['object'] = 'contacts';
        $config = $this->mergeConfigToFeatureSettings($config);

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
        if (!isset($config['leadFields'])) {
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
        $availableFields = $this->getAvailableLeadFields($config);

        $matched = [];

        foreach ($availableFields as $integrationKey => $integrationFields) {
            if (isset($leadFields[$integrationKey])) {
                $mauticKey = $leadFields[$integrationKey];
                if (isset($fields[$mauticKey]) && !empty($fields[$mauticKey])) {
                    if ($mauticKey === 'email') {
                        $matched[$integrationKey]['value'] = strval($fields[$mauticKey]['value']);
                        $matched[$integrationKey]['type'] = 'other';
                    } else {
                        $matched[$integrationKey] = strval($fields[$mauticKey]['value']);
                    }
                }
            }

            if (!empty($field['required']) && empty($matched[$integrationKey])) {
                $matched[$integrationKey] = $this->factory->getTranslator()->trans('mautic.integration.form.lead.unknown');
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

        $podioFields = [];
        $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;

        if (isset($settings['feature_settings']['objects'])) {
            $podioObjects = $settings['feature_settings']['objects'];
        } else {
            $settings = $this->settings->getFeatureSettings();
            $podioObjects = isset($settings['objects']) ? $settings['objects'] : ['lead'];
        }

        try {
            if ($this->isAuthorized()) {
                if (!empty($podioObjects) and is_array($podioObjects)) {
                    foreach ($podioObjects as $key => $object) {
                        // Check the cache first
                        $settings['cache_suffix'] = $cacheSuffix = '.' . $object;
                        if ($fields = parent::getAvailableLeadFields($settings)) {
                            $podioFields[$object] = $fields;

                            continue;
                        }

                        $leadFields = $this->getApiHelper()->getLeadFields($object);
                        if ($leadFields) {
                            if ($object == 'lead') {
                                foreach ($leadFields as $fieldInfo) {
                                    if ($fieldInfo['type'] == 'app') {
                                        $podioFields[$object][$fieldInfo['external_id']] = $fieldInfo['label'];
                                    }
                                }
                            } else {
                                foreach ($leadFields as $fieldInfo) {
                                    $podioFields[$object][$fieldInfo['external_id']] = [
                                        'type' => 'string',
                                        'label' => $fieldInfo['config']['label'],
                                        'required' => $fieldInfo['config']['required'],
                                    ];
                                    if ($fieldInfo['type'] == 'app') {
                                        $podioFields[$object][$fieldInfo['external_id']]['appId'] = $fieldInfo['config']['settings']['referenceable_types'][0] ?? null;

                                    }
                                }
                            }

                            $this->cache->set('leadFields' . $cacheSuffix, $podioFields[$object]);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);

            if (!$silenceExceptions) {
                throw $e;
            }
        }


        return $podioFields;
    }


    public function populateCompanyData($lead, $config = [])
    {
        if (!isset($config['companyFields'])) {
            $config = $this->mergeConfigToFeatureSettings($config);

            if (empty($config['companyFields'])) {
                return [];
            }
        }

        return $this->populateData($lead, $config['companyFields'], $config);
    }

    protected function getAvailableApps($settings = [])
    {
        if ($this->apps) {
            return $this->apps;
        }

        $workspaces = $this->getAvailableWorkspaces();
        $podioApps = [];

        $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;

        if (!$workspaces) {
            return $podioApps;
        }

        try {
            foreach ($workspaces as $workspaceId => $workspaceName) {
                $apps = $this->getApiHelper()->getAppsForWorkspace($workspaceId);
                foreach ($apps as $app) {
                    $podioApps[$workspaceName][$app['app_id']] = $app['config']['name'];
                }
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);

            if (!$silenceExceptions) {
                throw $e;
            }
        }

        return $this->apps = $podioApps;
    }

    protected function getAvailableOrganisations($settings = [])
    {
        if ($this->organizations) {
            return $this->organizations;
        }

        $podioOrganisations = [];
        $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;

        try {
            $organisations = $this->getApiHelper()->getOrganisations();
            foreach ($organisations as $organisation) {
                $podioOrganisations[$organisation['org_id']] = $organisation['name'];
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);

            if (!$silenceExceptions) {
                throw $e;
            }
        }

        return $this->organizations = $podioOrganisations;

    }

    protected function getAvailableWorkspaces($settings = [])
    {
        if ($this->workspaces) {
            return $this->workspaces;
        }

        $organisationId = $this->getOrganisationId();
        $podioWorkspaces = [];


        if (!$organisationId) {
            return $podioWorkspaces;
        }

        $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;

        try {
            $workspaces = $this->getApiHelper()->getWorkspacesForOrganisation($organisationId);
            foreach ($workspaces as $workspace) {
                $podioWorkspaces[$workspace['space_id']] = $workspace['name'];
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);

            if (!$silenceExceptions) {
                throw $e;
            }
        }

        return $this->workspaces = $podioWorkspaces;

    }

    public function getCompanyRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:Company');
    }
}
