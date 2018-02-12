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

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration;

/**
 * Class PodioIntegration.
 */
class PodioIntegration extends CrmAbstractIntegration
{

    /**
     * Get the API helper.
     *
     * @return object
     */
    public function getApiHelper()
    {
        if (empty($this->helper)) {
            $class        = '\\MauticPlugin\\PodioCrmBundle\\Api\\'.$this->getName().'Api';
            $this->helper = new $class($this);
        }

        return $this->helper;
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEm()
    {
        return $this->em;
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
        return ['push_lead', 'get_leads'];
    }

    /**
     * @return array
     */
    public function getLeadsAppId()
    {
        return $this->keys['leads_app_id'] ?? null;
    }

    public function getContactsAppId()
    {
        return $this->keys['contacts_app_id'] ?? null;
    }

    public function getCompaniesAppId()
    {
        return $this->keys['companies_app_id'] ?? null;
    }

    public function getContactFieldId($default = 'contact')
    {
        return $this->keys['contact_field_external_id'] ?? $default;
    }

    public function getCompanyFieldId($default = 'company')
    {
        return $this->keys['company_field_external_id'] ?? $default;
    }

    public function getSynchronizationTag($default = 'sync2mautic')
    {
        return $this->keys['synchronization_tag'] ?? $default;
    }

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
            $builder->add(
                'leads_app_id',
                'text',
                [
                    'label'      => 'mautic.podio.app.leads',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'required'   => false,
                ]
            );
        }

        if ($formArea == 'features') {

            $builder->add(
                'objects',
                'choice',
                [
                    'choices' => [
                        'contacts' => 'mautic.podio.object.contact',
                        'company'  => 'mautic.podio.object.company',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => $this->getTranslator()->trans('mautic.crm.form.objects_to_pull_from', ['%crm%' => 'Podio']),
                    'label_attr'  => ['class' => ''],
                    'empty_value' => false,
                    'required'    => false,
                ]
            );

            $builder->add(
                'synchronization_tag',
                'text',
                [
                    'label'      => 'mautic.podio.app.synchronization_tag',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'required'   => false,
                ]
            );
            $builder->add(
                'contact_field_external_id',
                'text',
                [
                    'label'      => 'mautic.podio.app.contact_field_external_id',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'required'   => false,
                ]
            );
            $builder->add(
                'company_field_external_id',
                'text',
                [
                    'label'      => 'mautic.podio.app.company_field_external_id',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'required'   => false,
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param $section
     *
     * @return string
     */
    public function getFormNotes($section)
    {
        if ($section == 'authorization') {
            return ['mautic.podio.form.oauth_requirements', 'warning'];
        }

        return parent::getFormNotes($section);
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

    public function getFormCompanyFields($settings = [])
    {
        return $this->getFormFieldsByObject('company', $settings);
    }

    /**
     * @return array|mixed
     */
    public function getAvailableLeadFields($settings = [])
    {
        $podioObjects = ['contacts', 'company'];
        $podioFields  = [];

        try {
            if ( ! empty($podioObjects) and is_array($podioObjects)) {
                foreach ($podioObjects as $object) {
                    // Check the cache first
                    $settings['cache_suffix'] = $cacheSuffix = '.' . $object;

                    if ($fields = parent::getAvailableLeadFields($settings)) {
                        $podioFields[$object] = $fields;
                        continue;
                    }
                    if ($this->isAuthorized()) {
                        $podioFields[$object] = $this->getApiHelper()->getLeadFields($object);
                        if (isset($podioFields[$object])) {
                            $tmpPodioFields[$object] = [];
                            foreach ($podioFields[$object] as $fieldInfo) {
                                $tmpPodioFields[$object][$fieldInfo['external_id']] = [
                                    'type'     => 'string',
                                    'label'    => $fieldInfo['config']['label'],
                                    'required' => $fieldInfo['config']['required'],
                                ];
                            }
                            $podioFields[$object] = $tmpPodioFields[$object];
                        }

                        $this->cache->set('leadFields' . $cacheSuffix, $podioFields[$object]);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);

            if ( ! ((isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true)) {
                throw $e;
            }
        }

        return $podioFields;
    }

    /**
     * Amend mapped lead data before pushing to CRM.
     *
     * @param $mappedData
     */
    public function amendLeadDataBeforePush(&$mappedData)
    {
    }

    /**
     * get query to fetch lead data.
     *
     * @param $config
     */
    public function getFetchQuery($config)
    {
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

    /**
     * {@inheritdoc}
     */
    public function sortFieldsAlphabetically()
    {
        return false;
    }

    /**
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @param                $leadId
     *
     * @return array
     */
    public function getLeadData(\DateTime $startDate = null, \DateTime $endDate = null, $leadId)
    {
        return [];
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

    public function pushLeads($params = [])
    {
        return;
    }

    /**
     * @param array $params
     */
    public function pushLeadActivity($params = [])
    {
    }

    public function getLeads($params, $query, &$executed, $result = [], $object = 'Contacts')
    {
        $executed = 0;
        try {
            if ($this->isAuthorized()) {
                $method = "get{$object}";
                $data   = $this->getApiHelper()->$method($params);
                foreach ($data ?? [] as $contact) {
                    $contactData = $this->amendLeadDataBeforeMauticPopulate($contact, $object);
                    $contact     = $this->getMauticLead($contactData);
                    if ($contact) {
                        ++$executed;
                    }
                }

                return $executed;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $executed;
    }

    public function getCompanies($params = [])
    {
        $executed = 0;
        try {
            if ($this->isAuthorized()) {
                $data = $this->getApiHelper()->getCompanies($params);

                foreach ($data ?? [] as $company) {
                    $companyData = $this->amendLeadDataBeforeMauticPopulate($company, null);
                    $company     = $this->getMauticCompany($companyData);
                    if ($company) {
                        ++$executed;
                    }
                }
//                    if (isset($data['hasMore']) and $data['hasMore']) {
//                        $params['vidOffset']  = $data['vid-offset'];
//                        $params['timeOffset'] = $data['time-offset'];
//                        $this->getCompanies($params);
//                    }

                return $executed;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $executed;
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

    protected function populateData($lead, $leadFields, $config)
    {
        if ($lead instanceof Lead) {
            $fields = $lead->getFields(true);
        } else {
            $fields = $lead;
        }
        $availableFields = $this->getAvailableLeadFields($config);
        if (isset($config['object'])) {
            $availableFields = $availableFields[$config['object']];
        }
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

    /**
     * Create or update existing Mautic lead from the integration's profile data.
     *
     * @param mixed $data Profile data from integration
     * @param bool|true $persist Set to false to not persist lead to the database in this method
     * @param array|null $socialCache
     * @param null $identifiers
     * @param null $object
     *
     * @return Lead
     */
    public function getMauticLead($data, $persist = true, $socialCache = NULL, $identifiers = NULL, $object = NULL)
    {
        if (is_object($data)) {
            // Convert to array in all levels
            $data = json_encode(json_decode($data), true);
        } elseif (is_string($data)) {
            // Assume JSON
            $data = json_decode($data, true);
        }

        // Match that data with mapped lead fields
        $matchedFields = $this->populateMauticLeadData($data);

        if (empty($matchedFields)) {
            return;
        }

        // Find unique identifier fields used by the integration
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel           = $this->factory->getModel('lead');
        $uniqueLeadFields    = $this->factory->getModel('lead.field')->getUniqueIdentiferFields();
        $uniqueLeadFieldData = [];

        foreach ($matchedFields as $leadField => $value) {
            if (array_key_exists($leadField, $uniqueLeadFields) && ! empty($value)) {
                $uniqueLeadFieldData[$leadField] = $value;
            }
        }

        // Default to new lead
        $lead = new Lead();

        if (count($uniqueLeadFieldData)) {
            $existingLeads = $this->factory->getEntityManager()->getRepository('MauticLeadBundle:Lead')
                                           ->getLeadsByUniqueFields($uniqueLeadFieldData);

            if ( ! empty($existingLeads)) {
                $lead = array_shift($existingLeads);
            }
        }

        $leadModel->setFieldValues($lead, $matchedFields, false, false);

        // Update the social cache
        $leadSocialCache = $lead->getSocialCache();
        if ( ! isset($leadSocialCache[$this->getName()])) {
            $leadSocialCache[$this->getName()] = [];
        }

        if (null !== $socialCache) {
            $leadSocialCache[$this->getName()] = array_merge($leadSocialCache[$this->getName()], $socialCache);
        }

        // Check for activity while here
        if (null !== $identifiers && in_array('public_activity', $this->getSupportedFeatures())) {
            $this->getPublicActivity($identifiers, $leadSocialCache[$this->getName()]);
        }

        $lead->setSocialCache($leadSocialCache);

        // Update the internal info integration object that has updated the record
        if (isset($data['internal'])) {
            $internalInfo                   = $lead->getInternal();
            $internalInfo[$this->getName()] = $data['internal'];
            $lead->setInternal($internalInfo);
        }

        if ($persist) {
            // Only persist if instructed to do so as it could be that calling code needs to manipulate the lead prior to executing event listeners
            try {
                $leadModel->saveEntity($lead, false);
            } catch (\Exception $exception) {
                $this->factory->getLogger()->addWarning($exception->getMessage());

                return;
            }
        }

        return $lead;
    }

    /**
     * Create or update existing Mautic company from the integration's company data.
     *
     * @param mixed $data Company data from integration
     * @param bool|true $persist Set to false to not persist company to the database in this method
     * @param mixed||null $identifiers
     *
     * @return Company
     */
    public function getMauticCompany($data, $persist = true, $identifiers = null)
    {
        if (is_object($data)) {
            // Convert to array in all levels
            $data = json_encode(json_decode($data), true);
        } elseif (is_string($data)) {
            // Assume JSON
            $data = json_decode($data, true);
        }
        $config        = $this->mergeConfigToFeatureSettings([]);
        $matchedFields = $this->populateMauticLeadData($data, $config, 'company');

        // Find unique identifier fields used by the integration
        /** @var \Mautic\LeadBundle\Model\CompanyModel $companyModel */
        $companyModel = $this->factory->getModel('lead.company');

        // Default to new company
        $company = new Company();

        $existingCompany = IdentifyCompanyHelper::identifyLeadsCompany($matchedFields, null, $companyModel);
        if ($existingCompany[2]) {
            $company = $existingCompany[2];
        }
        $companyModel->setFieldValues($company, $matchedFields, false);
        $companyModel->saveEntity($company, false);

        return $company;
    }
}
