Installation
============

Update your `deps` and `deps.lock` files:

    // deps
    ...
    [QBTTranslationBundle]
        git=https://github.com/qbt/QBTTranslationBundle.git
        target=/bundles/QBT/Bundle/TranslationBundle

    // deps.lock
    ...
    QBTTranslationBundle <commit>

Register the namespaces with the autoloader:

    // app/autoload.php
     $loader->registerNamespaces(array(
        // ...
        'QBT' => __DIR__.'/../vendor/bundles',
        // ...
    ));

Register the bundle with your kernel:

    // in AppKernel::registerBundles()
    $bundles = array(
        // ...
        new QBT\TranslationBundle\QBTTranslationBundle(),
        // ...
    );

___________________

Configuration
=============

This is the full configuration tree with default values, only `fallback_locale` and `managed_locales` are required:

    # app/config/config.yml
    qbt_translation:
        base_layout:      "QBTTranslationBundle::layout.html.twig"   # layout used with the translation edition template
        fallback_locale:  en                                           # (required) default locale to use
        managed_locales:  [en]                                         # (required) locales that the bundle have to manage
        resources_registration:
            type:                 all                                  # resources type to register: "all", "files" or "database"
            managed_locales_only: true                                 # only load resources for managed locales
        classes:
            translator:      QBT\TranslationBundle\Translation\Translator             # translator service class
            database_loader: QBT\TranslationBundle\Translation\Loader\DatabaseLoader  # database loader class

*Note that MongoDB 2.0.0 or later is required if you choose to use MongoDB to store translations.*

To use the translation edition page, add the routing file to you application:

    # app/config/routing.yml
    qbt_translation_edition:
        resource: "@QBTTranslationBundle/Resources/config/routing.yml"
        prefix:   /my-prefix

The translations edition page will be available here: /my-prefix/translation/grid

Note: The grid will be empty until you import translations in database and use [jqGrid 4.2.0](http://www.trirand.com/blog/).
If the grid does not appear, please check your base template has a block named `javascript_footer`.

___________________

Import translations
===================

To import translations files content into your database just run the following command:

    ./app/console qbt:translations:import [--cache-clear]

This command will import all application and bundles translations files according to the "managed_locales" defined in configuration.
You can use the `--cache-clear` (or `-c`) option to remove translations cache files (it won't clear all cache files but just files from `app/cache/[env]/translations/`).

Export translations
===================

To export translations from the database in to files run the following command:

    ./app/console qbt:translations:export

This command will export all translations from the database in to files. A translation is exported in the same file (and format) it was imported in,
except for vendors files which are exported in `app/Resources/translations/` and in this case the command will only export translations that changed.

*Note that it's not required to export translations to make them appear on your website, the DatabaseLoader will load them.*

