<?php

/**
 * @copyright   2018 twentyZen. All rights reserved
 * @author      Robert Juzak
 *
 * @link        https://twentyzen.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\PodioCrmBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\MauticCrmBundle\Api\CrmApi;
use MauticPlugin\PodioCrmBundle\Integration\PodioIntegration;

class PodioApi extends CrmApi
{

    /**
     * @var PodioIntegration $integration
     */
    protected $integration;

    public function getLeadFields($object = 'lead')
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

    public function createLead(array $data, $lead)
    {
        $result = [];
        //Format data for request
        $leadData[$this->integration->getLeadContactFieldId()] = $this->updateOrCreateItem(
            $this->integration->getContactsAppId(),
            $data,
            $lead
        );

        if ($companies = $this->integration->getCompanyRepository()->getCompaniesByLeadId($lead->getId())) {
            foreach ($companies as $company) {
                $config['object'] = 'company';

                $leadData[$this->integration->getLeadCompanyFieldId()][] = $this->updateOrCreateItem(
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

    /**
     * gets Podio contacts.
     *
     * @param array $params
     *
     * @return mixed
     */
    public function getContacts($params = [])
    {
        $result = $this->integration->makeRequest(
            sprintf('%s/item/app/%s/filter/', $this->integration->getApiUrl(), $this->integration->getContactsAppId()),
            null,
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

    public function getOrganisations($params = [])
    {
        $params['limit'] = 100;
        $items =  $this->integration->makeRequest(
            sprintf('%s/org/', $this->integration->getApiUrl()),
            $params,
            'get',
            ['encode_parameters' => 'json']
        );

        return array_filter($items, function ($item) {
            return $item['status'] == 'active';
        });
    }

    public function getWorkspacesForOrganisation(int $org_id, $params = [])
    {
        return $this->integration->makeRequest(
            sprintf('%s/space/org/%s', $this->integration->getApiUrl(), $org_id),
            $params,
            'get',
            ['encode_parameters' => 'json']
        );
    }

    public function getAppsForWorkspace(int $space_id, $params = [])
    {
        $params['limit'] = 100;
        $items =  $this->integration->makeRequest(
            sprintf('%s/app/space/%s', $this->integration->getApiUrl(), $space_id),
            $params,
            'get',
            ['encode_parameters' => 'json']
        );

        return array_filter($items, function ($item) {
            return $item['status'] == 'active';
        });

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

            return $postResult['item_id'] ?? null;
        }

    }
}
