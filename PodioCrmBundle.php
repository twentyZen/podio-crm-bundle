<?php

/**
 * @copyright   2018 twentyZen. All rights reserved
 * @author      Robert Juzak
 *
 * @link        https://twentyzen.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\PodioCrmBundle;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\PluginBundle\Bundle\PluginBundleBase;
use Mautic\PluginBundle\Entity\Plugin;

class PodioCrmBundle extends PluginBundleBase
{
    public static function onPluginInstall(
        Plugin $plugin,
        MauticFactory $factory,
        $metadata = null,
        $installedSchema = null
    ) {
        if ($metadata === null) {
            $metadata = self::getMetadata($factory->getEntityManager());
        }

        if ($metadata !== null) {
            parent::onPluginInstall($plugin, $factory, $metadata, $installedSchema);
        }
    }

    /**
     * Fix: plugin installer doesn't find metadata entities for the plugin
     * PluginBundle/Controller/PluginController:410.
     *
     * @param EntityManager $em
     *
     * @return array|null
     */
    private static function getMetadata(EntityManager $em)
    {
        $allMetadata   = $em->getMetadataFactory()->getAllMetadata();
        $currentSchema = $em->getConnection()->getSchemaManager()->createSchema();

        $classes = [];

        /** @var \Doctrine\ORM\Mapping\ClassMetadata $meta */
        foreach ($allMetadata as $meta) {
            if (strpos($meta->namespace, 'MauticPlugin\\MauticCrmBundle') === false) {
                continue;
            }

            $table = $meta->getTableName();

            if ($currentSchema->hasTable($table)) {
                continue;
            }

            $classes[] = $meta;
        }

        return $classes ?: null;
    }
}
