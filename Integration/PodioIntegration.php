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

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration;
use MauticPlugin\PodioCrmBundle\Api\PodioApi;


/**
 * Class PodioIntegration.
 */
class PodioIntegration extends CrmAbstractIntegration
{
    /**
     * @var array
     */
    protected $supportedFieldTypes = [
        'duration',
        'app',
        'number',
        'text',
        'email',
        'phone',
        'embed'
    ];

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthScope()
    {
        return 'global:all';
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
     * @return PodioApi
     */
    public function getApiHelper()
    {
        if (empty($this->helper)) {
            $this->helper = new PodioApi($this);
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
     *
     * @return bool
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

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_lead'];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getFormTemplate()
    {
        return 'PodioCrmBundle:Integration:form.html.php';
    }

    /**
     * @return int|null
     */
    public function getOrganisationId()
    {
        return isset($this->keys['organisation_id']) ? $this->keys['organisation_id'] : null;
    }

    /**
     * @param $id
     * @return PodioIntegration
     */
    public function setOrganisationId($id)
    {
        $this->encryptAndSetApiKeys(['organisation_id' => $id], $this->getIntegrationSettings());

        return $this;
    }

    /**
     * @return int|null
     */
    public function getContactsAppId()
    {
        if ($this->getOrganisationId()) {
            return isset($this->keys['contacts_app_id']) ? $this->keys['contacts_app_id'] : null;
        }

        return null;
    }

    /**
     * @param $id
     * @return PodioIntegration
     */
    public function setContactsAppId($id)
    {
        $this->keys['contacts_app_id'] = $id;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCompaniesAppId()
    {
        if ($this->getOrganisationId()) {
            return isset($this->keys['companies_app_id']) ? $this->keys['companies_app_id'] : null;
        }

        return null;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setCompaniesAppId($id)
    {
        $this->keys['companies_app_id'] = $id;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getLeadsAppId()
    {
        if ($this->getOrganisationId()) {
            return isset($this->keys['leads_app_id']) ? $this->keys['leads_app_id'] : null;
        }

        return null;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setLeadsAppId($id)
    {
        $this->keys['leads_app_id'] = $id;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getLeadContactFieldId()
    {
        if ($this->getLeadsAppId()) {
            $featureSettings = $this->settings->getFeatureSettings();
            return isset($featureSettings['lead_contact_field_id']) ? $featureSettings['lead_contact_field_id'] : null;
        }

        return null;
    }

    /**
     * @return int|null
     */
    public function getLeadCompanyFieldId()
    {
        if ($this->getLeadsAppId()) {
            $featureSettings = $this->settings->getFeatureSettings();
            return isset($featureSettings['lead_company_field_id']) ? $featureSettings['lead_company_field_id'] : null;
        }

        return null;
    }

    /**
     * @param array $settings
     * @return array|mixed
     * @throws \Exception
     */
    public function getFormLeadFields($settings = [])
    {
        if (isset($settings['list'])) {
            $this->setContactsAppId($settings['list']);
        }

        return $this->getFormFieldsByObject('contacts', $settings);
    }

    /**
     * @param array $settings
     * @return array|mixed
     * @throws \Exception
     */
    public function getFormCompanyFields($settings = [])
    {
        if (isset($settings['list'])) {
            $this->setCompaniesAppId($settings['list']);
        }

        return $this->getFormFieldsByObject('companies', $settings);
    }

    /**
     * @param       $object
     * @param array $settings
     * @return array|mixed
     * @throws \Exception
     */
    public function getFormFieldsByObject($object, $settings = [])
    {
        if ($object == 'companies') {
            $key = $this->getCompaniesAppId();
        } elseif ($object == 'lead') {
            $key = $this->getLeadsAppId();
        } else {
            $key = $this->getContactsAppId();
        }

        $settings['feature_settings']['objects'] = [$key => $object];
        $fields = ($this->isAuthorized()) ? $this->getAvailableLeadFields($settings) : [];

        return (isset($fields[$object])) ? $fields[$object] : [];
    }

    /**
     * @inheritdoc
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
                        'attr' => [
                            'class' => 'form-control',
                            'onchange' => 'Mautic.getIntegrationLeadFields(\'Podio\', this, {"list": this.value});',
                        ],
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
                        'attr' => [
                            'class' => 'form-control',
                            'onchange' => 'Mautic.getIntegrationCompanyFields(\'Podio\', this, {"list": this.value});',
                        ],
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
                        'attr' => [
                            'class' => 'form-control',
                            'onchange' => 'Mautic.setPodioLeadAppId(this, this.value);',
                        ],
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


            if ($this->getLeadsAppId()) {
                $builder->add(
                    'lead_contact_field_id',
                    'choice',
                    [
                        'choices' => $this->getAvailableLeadAppFields(),
                        'label' => 'mautic.podio.app.lead_contact_field',
                        'label_attr' => ['class' => 'control-label'],
                        'required' => false,
                    ]
                );

                $builder->add(
                    'lead_company_field_id',
                    'choice',
                    [
                        'choices' => $this->getAvailableLeadAppFields(),
                        'label' => 'mautic.podio.app.lead_company_field',
                        'label_attr' => ['class' => 'control-label'],
                        'required' => false,
                    ]
                );
            }
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
     * @inheritdoc
     */
    public function amendLeadDataBeforeMauticPopulate($data, $object)
    {
        $fieldsValues = [];
        foreach ($data['fields'] ?: [] as $field) {
            $fieldsValues[$field['external_id']] = $field['values'][0]['value'];
        }

        if (!empty($data['tags'])) {
            $fieldsValues['tags'] = $data['tags'];
        }

        return $fieldsValues;
    }

    /**
     * @param Lead  $mauticLead
     * @param array $config
     * @return array|bool
     */
    public function pushLead($mauticLead, $config = [])
    {
        $podioContact = $this->getApiHelper()->updateOrCreateItem(
            $this->getContactsAppId(),
            $this->populateLeadData($mauticLead, $config),
            $mauticLead
        );

        $leadData = [
            'fields' => []
        ];

        if (!$this->getLeadsAppId()) {
            return true;
        }

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
            foreach ($podioContactCompanies as $podioContactCompany) {
                foreach ($podioContactCompany['values'] as $value) {
                    if (isset($value['value']['item_id'])) {
                        $leadData['fields'][$leadCompanyFieldId][] = $value['value']['item_id'];
                    }
                }
            }
        }

        $podioLead = $this->getApiHelper()->updateOrCreateItem(
            $this->getLeadsAppId(),
            $leadData
        );

        if (
            isset($config['config']['push_to_podio_message']) AND
            !empty($config['config']['push_to_podio_message']) AND
            isset($podioLead['item_id']) AND
            !empty($podioLead['item_id'])
        ) {
            $this->getApiHelper()->addCommentToItem($podioLead['item_id'],
                $config['config']['push_to_podio_message']);
        }


        return true;
    }

    /**
     * @param Lead  $mauticLead
     * @param array $config
     * @return array
     * @throws \Exception
     */
    protected function pushCompanies($mauticLead, $config = [])
    {
        $podioCompanies = [];

        $companies = $this->getCompaniesByLeadId($mauticLead->getId());

        if (!$companies OR !($this->getCompaniesAppId())) {
            return $podioCompanies;
        }

        foreach ($companies as $company) {
            $podioCompanies[] = $this->getApiHelper()->updateOrCreateItem(
                $this->getCompaniesAppId(),
                $this->populateCompanyData($company, $config),
                $company
            );
        }

        return $podioCompanies;
    }

    /**
     * @param Lead  $lead
     * @param array $config
     * @return array
     * @throws \Exception
     */
    public function populateLeadData($lead, $config = [])
    {
        $config['config']['object'] = $this->getContactsAppId();
        if (!isset($config['leadFields'])) {
            $config = $this->mergeConfigToFeatureSettings($config);

            if (empty($config['leadFields'])) {
                return [];
            }
        }

        $config['cache_suffix'] = '.' . $config['object'];
        return $this->populateData($lead, $config['leadFields'], $config);
    }

    /**
     * @param Company $lead
     * @param array   $config
     * @return array
     * @throws \Exception
     */
    public function populateCompanyData($lead, $config = [])
    {
        $config['config']['object'] = $this->getContactsAppId();
        $config['object'] = 'company';
        if (!isset($config['companyFields'])) {
            $config = $this->mergeConfigToFeatureSettings($config);

            if (empty($config['companyFields'])) {
                return [];
            }
        }

        $config['cache_suffix'] = '.' . $config['object'];
        return $this->populateData($lead, $config['companyFields'], $config);
    }

    /**
     * @param Lead|Company $lead
     * @param              $fieldsMapping
     * @param              $config
     * @return array
     * @throws \Exception
     */
    protected function populateData($lead, $fieldsMapping, $config)
    {
        $mauticFields = is_array($lead) ? $lead : $lead->getFields(true);
        $podioFields = $this->getAvailableLeadFields($config);

        $data = [
            'external_id' => strval(is_array($lead) ? $lead['id'] : $lead->getId()),
            'fields' => [],
        ];

        foreach ($podioFields as $podioFieldKey => $podioField) {
            if (!isset($fieldsMapping[$podioFieldKey])) {
                continue;
            }

            $mauticKey = $fieldsMapping[$podioFieldKey];
            if (!isset($mauticFields[$mauticKey]) OR empty($mauticFields[$mauticKey])) {
                continue;
            }

            $value = is_array($mauticFields[$mauticKey]) ? strval($mauticFields[$mauticKey]['value']) : $mauticFields[$mauticKey];
            if (!$value) {
                continue;
            }

            switch ($podioField['type']) {
                case 'phone':
                case 'email':
                    $data['fields'][$podioFieldKey]['value'] = $value;
                    $data['fields'][$podioFieldKey]['type'] = 'other';
                    break;
                case 'app' :
                    if ($mauticKey == 'company') {
                        if ($podioCompanies = $this->pushCompanies($lead, $config)) {
                            $data['fields'][$podioFieldKey] = array_column($podioCompanies, 'item_id');
                        }
                    }
                    break;
                case 'text':
                    $data['fields'][$podioFieldKey] = $value;
                    break;
                case 'number':
                case 'duration':
                case 'progress':
                    $data['fields'][$podioFieldKey] = intval($value);
                    break;
                case 'money':
                    $data['fields'][$podioFieldKey]['value'] = intval($value);
                    break;
                case 'embed' :
                    $embed = $this->getApiHelper()->createEmbed($value);
                    if (isset($embed['embed_id'])) {
                        $data['fields'][$podioFieldKey]['embed'] = $embed['embed_id'];
                    }

                    if (!isset($embed['files'])) {
                        break;
                    }

                    if (isset($embed['files'][0])) {
                        $data['fields'][$podioFieldKey]['file'] = $embed['files'][0]['file_id'];
                    }

                    break;
            }
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
            $podioObjects = [$this->getContactsAppId() => 'contacts'];
        }

        if (empty($podioObjects) or !is_array($podioObjects)) {
            return [];
        }

        $podioFields = [];
        try {
            foreach ($podioObjects as $app => $object) {
                // Check the cache first
                $settings['cache_suffix'] = $cacheSuffix = '.' . $app;
                if ($fields = parent::getAvailableLeadFields($settings)) {
                    $podioFields[$object] = $fields;
                    continue;
                }

                $leadFields = $this->getApiHelper()->getLeadFields($app);
                if (!$leadFields) {
                    return [];
                }

                if ($object == 'lead') {
                    $leadFields = array_filter($leadFields, function ($fieldInfo) {
                        return $fieldInfo['type'] == 'app';
                    });
                } else {
                    $leadFields = array_filter($leadFields, function ($fieldInfo) {
                        return in_array($fieldInfo['type'], $this->supportedFieldTypes);
                    });
                }

                foreach ($leadFields as $fieldInfo) {
                    $podioFields[$object][$fieldInfo['external_id']] = [
                        'type' => $fieldInfo['type'],
                        'label' => $fieldInfo['config']['label'],
                        'required' => $fieldInfo['config']['required'],
                    ];
                }

                $this->cache->set('leadFields' . $cacheSuffix, $podioFields[$object]);
            }

        } catch (\Exception $e) {
            $this->logIntegrationError($e);

            $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;
            if (!$silenceExceptions) {
                throw $e;
            }
        }

        return $podioFields;
    }

    /**
     * @param array $settings
     * @return array
     * @throws \Exception
     */
    public function getAvailableApps($settings = [])
    {
        if (isset($settings['organisation_id']) AND !empty($settings['organisation_id'])) {
            $this->setOrganisationId($settings['organisation_id']);
        }

        $organisationId = $this->getOrganisationId();
        $podioApps = [];

        if (!$organisationId) {
            return $podioApps;
        }

        try {
                $apps = $this->getApiHelper()->getAppsForOrganisation($organisationId);
                foreach ($apps as $app) {
                    $workspace = $app['space'];
                    $podioApps[$workspace['name']][$app['app_id']] = $app['config']['name'];
                }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);

            $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;
            if (!$silenceExceptions) {
                throw $e;
            }
        }

        return $podioApps;
    }

    public function getAvailableLeadAppFields($settings = [])
    {
        if (isset($settings['lead_app_id'])) {
            $this->setLeadsAppId($settings['lead_app_id']);
        }

        $fields = [];
        foreach ($this->getFormFieldsByObject('lead') as $external_id => $field) {
            $fields[$external_id] = $field['label'];
        }

        return $fields;
    }

    /**
     * @param array $settings
     * @return array
     * @throws \Exception
     */
    protected function getAvailableOrganisations($settings = [])
    {
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

        return $podioOrganisations;
    }

    /**
     * @param array $settings
     * @return array
     * @throws \Exception
     */

    /**
     * @param int $leadId
     * @return array
     */
    protected function getCompaniesByLeadId($leadId)
    {
        /** @var CompanyLeadRepository $companyLeadRepository */
        $companyLeadRepository = $this->em->getRepository('MauticLeadBundle:Company');
        return $companyLeadRepository->getCompaniesByLeadId($leadId);
    }

    public function authCallback($settings = [], $parameters = [])
    {
        try {
            return parent::authCallback($settings, $parameters);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Called in extractAuthKeys before key comparison begins to give opportunity to set expiry, rename keys, etc.
     *
     * @param $data
     *
     * @return mixed
     */
    public function prepareResponseForExtraction($data)
    {
        $data['expires_in'] += time();
        return $data;
    }
}
