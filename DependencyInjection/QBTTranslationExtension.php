<?php

namespace QBT\TranslationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Finder\Finder;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class QBTTranslationExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
        
        $container->setAlias('qbt_translation.storage_manager', 'doctrine.orm.entity_manager');

        // set parameters
        sort($config['managed_locales']);
        $container->setParameter('qbt_translation.managed_locales', $config['managed_locales']);
        $container->setParameter('qbt_translation.fallback_locale', $config['fallback_locale']);
        //$container->setParameter('qbt_translation.storage', $config['storage']);
        $container->setParameter('qbt_translation.base_layout', $config['base_layout']);
        $container->setParameter('qbt_translation.grid_input_type', $config['grid_input_type']);

        $container->setParameter('qbt_translation.translator.class', $config['classes']['translator']);
        $container->setParameter('qbt_translation.loader.database.class', $config['classes']['database_loader']);
        $container->setParameter('qbt_translation.trans_unit.class', 'QBT\TranslationBundle\Entity\TransUnit');
        $container->setParameter('qbt_translation.translation.class', 'QBT\TranslationBundle\Entity\Translation');
        $container->setParameter('qbt_translation.file.class', 'QBT\TranslationBundle\%s\File');

        $this->registerTranslatorConfiguration($config, $container);
    }

    /**
     * Register the "qbt_translation.translator" service configuration.
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    protected function registerTranslatorConfiguration(array $config, ContainerBuilder $container)
    {
        // use the QBT translator as default translator service
        $container->setAlias('translator', 'qbt_translation.translator');

        $translator = $container->findDefinition('qbt_translation.translator');
        $translator->addMethodCall('setFallbackLocale', array($config['fallback_locale']));

        $registration = $config['resources_registration'];

        if ('all' == $registration['type'] || 'files' == $registration['type']) {
            // Discover translation directories
            $dirs = array();
            if (class_exists('Symfony\Component\Validator\Validator')) {
                $r = new \ReflectionClass('Symfony\Component\Validator\Validator');

                $dirs[] = dirname($r->getFilename()).'/Resources/translations';
            }
            if (class_exists('Symfony\Component\Form\Form')) {
                $r = new \ReflectionClass('Symfony\Component\Form\Form');

                $dirs[] = dirname($r->getFilename()).'/Resources/translations';
            }
            $overridePath = $container->getParameter('kernel.root_dir').'/Resources/%s/translations';
            foreach ($container->getParameter('kernel.bundles') as $bundle => $class) {
                $reflection = new \ReflectionClass($class);
                if (is_dir($dir = dirname($reflection->getFilename()).'/Resources/translations')) {
                    $dirs[] = $dir;
                }
                if (is_dir($dir = sprintf($overridePath, $bundle))) {
                    $dirs[] = $dir;
                }
            }
            if (is_dir($dir = $container->getParameter('kernel.root_dir').'/Resources/translations')) {
                $dirs[] = $dir;
            }

            // Register translation resources
            if (count($dirs) > 0) {
                foreach ($dirs as $dir) {
                    $container->addResource(new DirectoryResource($dir));
                }

                $finder = Finder::create();
                $finder->files();

                if (true === $registration['managed_locales_only']) {
                    // only look for managed locales
                    $finder->name(sprintf('/(.*\.(%s)\..*)/', implode('|', $config['managed_locales'])));
                } else {
                    $finder->filter(function (\SplFileInfo $file) {
                        return 2 === substr_count($file->getBasename(), '.') && preg_match('/\.\w+$/', $file->getBasename());
                    });
                }

                $finder->in($dirs);

                foreach ($finder as $file) {
                    // filename is domain.locale.format
                    list($domain, $locale, $format) = explode('.', $file->getBasename());
                    $translator->addMethodCall('addResource', array($format, (string) $file, $locale, $domain));
                }
            }
        }

        if ('all' == $registration['type'] || 'database' == $registration['type']) {
            // add ressources from database
            $translator->addMethodCall('addDatabaseResources', array());
        }
    }
}
