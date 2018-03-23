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

use Mautic\LeadBundle\Entity\Company;
use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\MauticCrmBundle\Api\CrmApi;
use MauticPlugin\MauticSocialBundle\Entity\Lead;
use MauticPlugin\PodioCrmBundle\Integration\PodioIntegration;

class PodioApi extends CrmApi
{

    /**
     * @var PodioIntegration $integration
     */
    protected $integration;

    /**
     * @param $appId
     * @return array
     * @throws ApiErrorException
     */
    public function getLeadFields($appId)
    {
        if (!$appId) {
            return [];
        }

        $request = $this->integration->makeRequest(
            sprintf('%s/app/%s', $this->integration->getApiUrl(), $appId)
        );

        if (isset($request['status']) && $request['status'] == 'error') {
            throw new ApiErrorException($request['message']);
        }
        $fields = $request['fields'] ?: [];

        if ($fields) {
            $fields = array_filter($fields, function ($field) {
                return $field['status'] == 'active';
            });
        }

        return $fields;
    }

    /**
     * @param array $params
     * @return array|mixed|string
     */
    public function getOrganisations($params = [])
    {
        $params['limit'] = 100;
        $items = $this->integration->makeRequest(
            sprintf('%s/org/', $this->integration->getApiUrl()),
            $params,
            'get',
            ['encode_parameters' => 'json']
        );

        return array_filter($items, function ($item) {
            return $item['status'] == 'active';
        });
    }

    /**
     * @param $org_id
     * @param array $params
     * @return mixed|string
     */
    public function getWorkspacesForOrganisation($org_id, $params = [])
    {
        return $this->integration->makeRequest(
            sprintf('%s/space/org/%s', $this->integration->getApiUrl(), $org_id),
            $params,
            'get',
            ['encode_parameters' => 'json']
        );
    }

    /**
     * @param $space_id
     * @param array $params
     * @return array|mixed|string
     */
    public function getAppsForWorkspace($space_id, $params = [])
    {
        $params['limit'] = 100;
        $items = $this->integration->makeRequest(
            sprintf('%s/app/space/%s', $this->integration->getApiUrl(), $space_id),
            $params,
            'get',
            ['encode_parameters' => 'json']
        );

        return array_filter($items, function ($item) {
            return $item['status'] == 'active';
        });

    }

    /**
     * @param int $appId
     * @param array $data
     * @param Company|Lead $mauticItem
     * @return bool|mixed|string
     */
    public function updateOrCreateItem($appId, $data, $mauticItem = null)
    {
        if (!$appId) {
            return false;
        }

        if ($mauticItem) {
            $getResult = $this->integration->makeRequest(
                sprintf('%s/item/app/%s/external_id/%s/', $this->integration->getApiUrl(), $appId, $mauticItem->getId())
            );

            if (!empty($getResult['item_id'])) {

                $this->integration->makeRequest(
                    sprintf('%s/item/%s/', $this->integration->getApiUrl(), $getResult['item_id']),
                    $data,
                    'PUT',
                    ['encode_parameters' => 'json']
                );

                return $this->integration->makeRequest(
                    sprintf('%s/item/app/%s/external_id/%s/', $this->integration->getApiUrl(), $appId, $mauticItem->getId())
                );
            }
        }

        return $this->integration->makeRequest(
            sprintf('%s/item/app/%s/', $this->integration->getApiUrl(), $appId),
            $data,
            'POST',
            ['encode_parameters' => 'json']
        );
    }

    public function createEmbed($url, $attributes = [])
    {
        $attributes['url'] = $url;
        return $this->integration->makeRequest(
            sprintf('%s/embed/', $this->integration->getApiUrl()),
            $attributes,
            'POST',
            ['encode_parameters' => 'json']
        );
    }

    /**
     * @param int $itemId
     * @param string $comment
     * @return mixed|string
     */
    public function addCommentToItem($itemId, $comment)
    {
        return $this->integration->makeRequest(
            sprintf('%s/comment/item/%s/', $this->integration->getApiUrl(), $itemId),
            ['value' => $comment],
            'POST',
            ['encode_parameters' => 'json']
        );
    }
}
