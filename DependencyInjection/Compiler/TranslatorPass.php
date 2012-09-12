<?php

namespace QBT\TranslationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Translator compiler pass to automatically pass loader to the other services.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class TranslatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // loaders
        $loaders = array();
        $loadersReferences = array();

        foreach ($container->findTaggedServiceIds('translation.loader') as $id => $attributes) {
            $loaders[$id][] = $attributes[0]['alias'];
            $loadersReferences[$attributes[0]['alias']] = new Reference($id);

            if (isset($attributes[0]['legacy-alias'])) {
                $loaders[$id][] = $attributes[0]['legacy-alias'];
                $loadersReferences[$attributes[0]['legacy-alias']] = new Reference($id);
            }
        }

        if ($container->hasDefinition('qbt_translation.translator')) {
            $container->findDefinition('qbt_translation.translator')->replaceArgument(2, $loaders);
        }

        if ($container->hasDefinition('qbt_translation.importer.file')) {
            $container->findDefinition('qbt_translation.importer.file')->replaceArgument(0, $loadersReferences);
        }

        // exporters
        if ($container->hasDefinition('qbt_translation.exporter_collector')) {
            foreach ($container->findTaggedServiceIds('qbt_translation.exporter') as $id => $attributes) {
                $container->getDefinition('qbt_translation.exporter_collector')->addMethodCall('addExporter', array($id, new Reference($id)));
            }
        }
    }
}