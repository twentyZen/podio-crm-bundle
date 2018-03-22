<?php
/**
 * Created by PhpStorm.
 * User: robjuz
 * Date: 19/03/18
 * Time: 14:23
 */

namespace MauticPlugin\PodioCrmBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use MauticPlugin\PodioCrmBundle\Integration\PodioIntegration;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends CommonAjaxController
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getAppsAction(Request $request)
    {
        $settings = $request->request->get('settings');

        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $helper */
        $helper = $this->get('mautic.helper.integration');
        /** @var PodioIntegration $podioIntegrationObject */
        $podioIntegrationObject = $helper->getIntegrationObject('Podio');


        $apps = $podioIntegrationObject->getAvailableApps($settings);

        return $this->sendJsonResponse($apps);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function setLeadAppAction(Request $request)
    {
        $settings = $request->request->get('settings');

        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $helper */
        $helper = $this->get('mautic.helper.integration');
        /** @var PodioIntegration $podioIntegrationObject */
        $podioIntegrationObject = $helper->getIntegrationObject('Podio');

        $fields = $podioIntegrationObject->getAvailableLeadAppFields($settings);

        return $this->sendJsonResponse(['fields' => $fields]);
    }
}