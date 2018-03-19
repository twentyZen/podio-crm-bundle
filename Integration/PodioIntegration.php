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

    protected $supportedFieldTypes = [
        'duration',
        'app',
        'number',
        'text',
        'email',
        'phone'
    ];


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

    public function getFormTemplate()
    {
        return 'PodioCrmBundle:Integration:form.html.php';
    }

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
            $featureSettings = $this->settings->getFeatureSettings();
            return $featureSettings['lead_contact_field_id'] ?? null;
        }

        return null;
    }

    public function getLeadCompanyFieldId()
    {
        if ($this->getLeadsAppId()) {
            $featureSettings = $this->settings->getFeatureSettings();
            return $featureSettings['lead_company_field_id'] ?? null;
        }

        return null;
    }

    public function getFormLeadFields($settings = [])
    {
        return $this->getFormFieldsByObject('contacts', $settings);
    }

//    public function getSynchronizationTag($default = 'sync2mautic')
//    {
//        return $this->keys['synchronization_tag'] ?? $default;
//    }

    /**
     * @param \Mautic\PluginBundle\Integration\Form|\Symfony\Component\Form\FormBuilder $builder
     * @param array $data
     * @param string $formArea
     * @throws \Exception
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea == 'keys') {

            if ($this->isAuthorized()) {
                $builder->add(
                    'organisation_id',
                    'choice',
                    [
                        'choices' => $this->getAvailableOrganisations(),
                        'label' => 'mautic.podio.organisation',
                        'label_attr' => ['class' => 'control-label'],
                        'required' => true,
                        'attr' => [
                            'class' => 'form-control',
                            'onchange' => 'Mautic.podioCrmUpdateOrganisation(this);',
                        ],
                    ]
                );

                $apps = $this->getOrganisationId() ? $this->getAvailableApps() : [];
                $builder->add(
                    'contacts_app_id',
                    'choice',
                    [
                        'choices' => $apps,
                        'label' => 'mautic.podio.app.contacts',
                        'label_attr' => ['class' => 'control-label'],
                        'attr' => ['class' => 'form-control'],
                        'required' => true,
                    ]
                );
                $builder->add(
                    'companies_app_id',
                    'choice',
                    [
                        'choices' => $apps,
                        'label' => 'mautic.podio.app.companies',
                        'label_attr' => ['class' => 'control-label'],
                        'attr' => ['class' => 'form-control'],
                        'required' => false,
                    ]
                );
                $builder->add(
                    'leads_app_id',
                    'choice',
                    [
                        'choices' => $apps,
                        'label' => 'mautic.podio.app.leads',
                        'label_attr' => ['class' => 'control-label'],
                        'attr' => ['class' => 'form-control'],
                        'required' => false,
                    ]
                );
            }
        }

        if ($formArea == 'features') {

            $builder->add(
                'objects',
                'choice',
                [
                    'choices' => [
//                        'contacts' => 'mautic.podio.object.contact',
                        'company' => 'mautic.podio.object.company',
//                        'leads' => 'mautic.podio.object.leads',
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


            if ($this->getLeadsAppId()) {
                $settings['feature_settings']['objects'] = ['lead'];
                $builder->add(
                    'lead_contact_field_id',
                    'choice',
                    [
                        'choices' => $this->getAvailableLeadFields($settings)['lead'] ?? [],
                        'label' => 'mautic.podio.app.lead_contact_field',
                        'label_attr' => ['class' => 'control-label'],
                        'required' => false,
                    ]
                );

                $builder->add(
                    'lead_company_field_id',
                    'choice',
                    [
                        'choices' => $this->getAvailableLeadFields($settings)['lead'] ?? [],
                        'label' => 'mautic.podio.app.lead_company_field',
                        'label_attr' => ['class' => 'control-label'],
                        'required' => false,
                    ]
                );
            }


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
        $contactsAppId = $this->getContactsAppId();
        $leadsAppId = $this->getLeadsAppId();

        $podioContact = null;
        if ($contactsAppId) {
            if ($this->isAuthorized()) {
                $podioContact = $this->getApiHelper()->updateOrCreateItem(
                    $contactsAppId,
                    $this->populateLeadData($lead, $config),
                    $lead
                );
            }
        }


        $leadData = [
            'fields' => []
        ];
        if ($leadsAppId) {
            $leadContactFieldId = $this->getLeadContactFieldId();
            if ($podioContact and $leadContactFieldId) {
                $leadData['fields'][$leadContactFieldId] = intval($podioContact['item_id']);
            }

            $leadCompanyFieldId = $this->getLeadCompanyFieldId();
            if ($leadCompanyFieldId) {
                $podioContactCompanies = array_filter($podioContact['fields'],
                    function ($field) use ($leadCompanyFieldId) {
                        return $field['external_id'] == $leadCompanyFieldId;
                    });
                if (isset($podioContactCompanies[0]) AND !empty($podioContactCompanies[0]['values'])) {
                    $values = $podioContactCompanies[0]['values'];
                    foreach ($values as $value) {
                        if (isset($value['value']['item_id'])) {
                            $leadData['fields'][$leadCompanyFieldId][] = $value['value']['item_id'];
                        }
                    }
                }
            }

            if ($this->isAuthorized()) {
                $podioLead = $this->getApiHelper()->updateOrCreateItem($leadsAppId, $leadData);
                if (
                    isset($config['config']['push_to_podio_message']) AND
                    !empty($config['config']['push_to_podio_message']) AND
                    isset($podioLead['item_id']) AND
                    !empty($podioLead['item_id'])
                ) {
                    $this->getApiHelper()->addCommentToItem($podioLead['item_id'],
                        $config['config']['push_to_podio_message']);
                }
            }
        }

        return true;
    }

    protected function pushCompanies($lead, $config = [])
    {
        $companiesAppId = $this->getCompaniesAppId();

        $podioCompanies = [];
        $companies = $this->em->getRepository('MauticLeadBundle:Company')->getCompaniesByLeadId($lead->getId());
        if ($companies AND $companiesAppId) {
            foreach ($companies as $company) {
                if ($this->isAuthorized()) {
                    $podioCompanies[] = $this->getApiHelper()->updateOrCreateItem(
                        $companiesAppId,
                        $this->populateCompanyData($company, $config),
                        $company
                    );
                }
            }
        }

        return $podioCompanies;
    }

    public function populateLeadData($lead, $config = [])
    {
        $config['config']['object'] = 'contacts';
        if (!isset($config['leadFields'])) {
            $config = $this->mergeConfigToFeatureSettings($config);

            if (empty($config['leadFields'])) {
                return [];
            }
        }

        return $this->populateData($lead, $config['leadFields'], $config);
    }

    public function populateCompanyData($lead, $config = [])
    {
        $config['config']['object'] = 'company';
        $config['object'] = 'company';
        if (!isset($config['companyFields'])) {
            $config = $this->mergeConfigToFeatureSettings($config);

            if (empty($config['companyFields'])) {
                return [];
            }
        }

        return $this->populateData($lead, $config['companyFields'], $config);
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

        $data = [
            'external_id' => strval(is_array($lead) ? $lead['id'] : $lead->getId()),
            'fields' => [],
        ];
        foreach ($availableFields as $integrationKey => $integrationField) {
            if (isset($leadFields[$integrationKey])) {
                $mauticKey = $leadFields[$integrationKey];
                if (isset($fields[$mauticKey]) && !empty($fields[$mauticKey])) {
                    $value = isset($fields[$mauticKey]['value']) ? strval($fields[$mauticKey]['value']) : $fields[$mauticKey];
                    switch ($integrationField['type']) {
                        case 'phone':
                        case 'email':
                            $data['fields'][$integrationKey]['value'] = $value;
                            $data['fields'][$integrationKey]['type'] = 'other';
                            break;
                        case 'app' :
                            if ($mauticKey == 'company') {
                                $podioCompanies = $this->pushCompanies($lead, $config);
                                $data['fields'][$integrationKey] = array_column($podioCompanies, 'item_id');
                            }
                            break;
                        case 'text':
                            $data['fields'][$integrationKey] = $value;
                            break;
                        case 'number':
                        case 'duration':
                        case 'progress':
                            $data['fields'][$integrationKey] = intval($value);
                            break;
                        case 'money':
                            $data['fields'][$integrationKey]['value'] = intval($value);
                            break;

                    }
                }
            }

//            if (!empty($field['required']) && empty($data['fields'][$integrationKey]['value'])) {
//                $data['fields'][$integrationKey]['value'] = $this->factory->getTranslator()->trans('mautic.integration.form.lead.unknown');
//            }
        }

        return $data;
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


        if (isset($settings['feature_settings']['objects'])) {
            $podioObjects = $settings['feature_settings']['objects'];
        } else {
            $podioObjects[] = 'contacts';
        }

        if (empty($podioObjects) or !is_array($podioObjects)) {
            return [];
        }

        try {
            if (!$this->isAuthorized()) {
                return [];
            }
            $podioFields = [];
            foreach ($podioObjects as $object) {
                // Check the cache first
                $settings['cache_suffix'] = $cacheSuffix = '.' . $object;
                if ($fields = parent::getAvailableLeadFields($settings)) {
                    $podioFields[$object] = $fields;

                    continue;
                }

                $leadFields = $this->getApiHelper()->getLeadFields($object);
                if (!$leadFields) {
                    return [];
                }

                if ($object == 'lead') {
                    foreach ($leadFields as $fieldInfo) {
                        if ($fieldInfo['type'] == 'app') {
                            $podioFields[$object][$fieldInfo['external_id']] = $fieldInfo['label'];
                        }
                    }
                } else {
                    $leadFields = array_filter($leadFields, function ($fieldInfo) {
                        return in_array($fieldInfo['type'], $this->supportedFieldTypes);
                    });
                    foreach ($leadFields as $fieldInfo) {
                        $podioFields[$object][$fieldInfo['external_id']] = [
                            'type' => $fieldInfo['type'],
                            'label' => $fieldInfo['config']['label'],
                            'required' => $fieldInfo['config']['required'],
                        ];
//                        if ($fieldInfo['type'] == 'app') {
//                            $podioFields[$object][$fieldInfo['external_id']]['appId'] = $fieldInfo['config']['settings']['referenceable_types'][0] ?? null;
//
//                        }
                    }
                }


                $this->cache->set('leadFields' . $cacheSuffix, $podioFields[$object]);
            }
            return $podioFields;

        } catch (\Exception $e) {
            $this->logIntegrationError($e);

            $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;
            if (!$silenceExceptions) {
                throw $e;
            }
        }


        return [];
    }

    public function getAvailableApps($settings = [])
    {
        if ($this->apps) {
            return $this->apps;
        }

        $workspaces = $this->getAvailableWorkspaces($settings);
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

        if (isset($settings['organisation_id']) AND !empty($settings['organisation_id'])) {
            $organisationId = $settings['organisation_id'];
        } else {
            $organisationId = $this->getOrganisationId();
        }
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
