<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Podio as CRM',
    'description' => 'Podio as CRM',
    'version'     => '0.3.0',
    'author'      => 'robjuz',
    'services'    => [
        'forms' => [
            'mautic.form.type.integration.fiead.fields' => [
                'class'     => 'MauticPlugin\PodioCrmBundle\LeadFieldsType',
                'alias'     => 'integration_lied_fields',
                'arguments' => 'translator',
            ],
        ]
    ]
];
