<?php
/**
 * Created by IntelliJ IDEA.
 * User: robjuz
 * Date: 24/03/2017
 * Time: 14:53.
 */

namespace MauticPlugin\PodioCrmBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\MauticCrmBundle\Api\CrmApi;
use MauticPlugin\PodioCrmBundle\Integration\PodioIntegration;

class PodioApi extends CrmApi
{

    /**
     * @var PodioIntegration
     */
    protected $integration;

    public function getLeadFields($object = 'contacts')
    {
        if ($object == 'company') {
            $appId = $this->integration->getCompaniesAppId();
        } else if ($object == 'contacts') {
            $appId = $this->integration->getContactsAppId();
        } else {
            $appId = $this->integration->getLeadsAppId();
        }
        $request = $this->integration->makeRequest(
            sprintf('%s/app/%s', $this->integration->getApiUrl(), $appId)
        );
        if (isset($request['status']) && $request['status'] == 'error') {
            throw new ApiErrorException($request['message']);
        }
        $fields = $request['fields'] ?? [];

        if ($fields) {
            $fields = array_filter($fields, function ($field) {
                return $field['status'] == 'active';
            });
        }

        return $fields;
    }

    public function createLead(array $data, $lead, $config = [])
    {
        $companyRepository = $this->integration->getEm()->getRepository('MauticLeadBundle:Company');

        $result = [];
        //Format data for request
        $leadData[$this->integration->getContactFieldId()] = $this->updateOrCreateItem(
            $this->integration->getContactsAppId(),
            $data,
            $lead
        );

        if ($companies = $companyRepository->getCompaniesByLeadId($lead->getId())) {
            foreach ($companies as $company) {
                $config['object'] = 'company';

                $leadData[$this->integration->getCompanyFieldId()][] = $this->updateOrCreateItem(
                    $this->integration->getCompaniesAppId(),
                    $this->integration->populateCompanyData($company, $config),
                    $company
                );
            }
        }

        $formattedLeadData = $this->integration->formatLeadDataForCreateOrUpdate($leadData, $lead);

        if ($formattedLeadData) {
            $result = $this->integration->makeRequest(
                sprintf('%s/item/app/%s/', $this->integration->getApiUrl(), $this->integration->getLeadsAppId()),
                $formattedLeadData,
                'POST',
                ['encode_parameters' => 'json']
            );
        }

        return !empty($result['item_id']);
    }

    public function createContact(array $data, $lead)
    {
        $appId = $this->integration->getLeadsAppId();

        $getResult = $this->integration->makeRequest(
            sprintf('%s/item/app/%s/external_id/%s/', $this->integration->getApiUrl(), $appId, $lead->getId())
        );

        $formattedLeadData = $this->integration->formatLeadDataForCreateOrUpdate($data, $lead);

        if ( ! empty($getResult['item_id'])) {
            $itemId = $getResult['item_id'];
            $this->integration->makeRequest(
                sprintf('%s/item/%s/', $this->integration->getApiUrl(), $itemId),
                $formattedLeadData,
                'PUT',
                ['encode_parameters' => 'json']
            );

            return $itemId;
        } else {
            $postResult = $this->integration->makeRequest(
                sprintf('%s/item/app/%s/', $this->integration->getApiUrl(), $appId),
                $formattedLeadData,
                'POST',
                ['encode_parameters' => 'json']
            );

            return $postResult['item_id'];
        }
    }


    public function getLeads($params = [])
    {
        return [];
    }

    /**
     * gets Podio contacts.
     *
     * @param array $params
     *
     * @return mixed
     */
    public function getContacts($params = [])
    {
        $appId = $this->integration->getContactsAppId();

        $params['tags'] = $this->integration->getSynchronizationTag();

        $result = $this->integration->makeRequest(
            sprintf('%s/item/app/%s/filter/', $this->integration->getApiUrl(), $appId),
            $params,
            'POST',
            ['encode_parameters' => 'json']
        );

        return $result['items'] ?? [];
    }

    /**
     * gets Podio companies.
     *
     * @param array $params
     * @param int $id
     *
     * @return mixed
     */
    public function getCompanies($params = [], $id = null)
    {
        $appId = $this->integration->getCompaniesAppId();

        if ($id) {
            return $this->integration->makeRequest(
                sprintf('%s/app/%s/item/%s', $this->integration->getApiUrl(), $appId, $id)
            );
        }

        $result = $this->integration->makeRequest(
            sprintf('%s/item/app/%s/filter/', $this->integration->getApiUrl(), $appId),
            $params,
            'POST',
            ['encode_parameters' => 'json']
        );

        return $result['items'] ?? [];
    }

    protected function updateOrCreateItem($appId, $data, $item)
    {
        $itemId = is_array($item) ? $item['id'] : $item->getId();

        $getResult = $this->integration->makeRequest(
            sprintf('%s/item/app/%s/external_id/%s/', $this->integration->getApiUrl(), $appId, $itemId)
        );


        $formattedLeadData = $this->integration->formatLeadDataForCreateOrUpdate($data, $item);

        if ( ! empty($getResult['item_id'])) {
            $itemId = $getResult['item_id'];
            $this->integration->makeRequest(
                sprintf('%s/item/%s/', $this->integration->getApiUrl(), $itemId),
                $formattedLeadData,
                'PUT',
                ['encode_parameters' => 'json']
            );

            return $itemId;
        } else {
            $postResult = $this->integration->makeRequest(
                sprintf('%s/item/app/%s/', $this->integration->getApiUrl(), $appId),
                $formattedLeadData,
                'POST',
                ['encode_parameters' => 'json']
            );

            return $postResult['item_id'];
        }
    }
}
