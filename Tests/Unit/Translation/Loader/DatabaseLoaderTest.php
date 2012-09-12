<?php

namespace QBT\TranslationBundle\Tests\Unit\Translation\Loader;

use QBT\TranslationBundle\Translation\Loader\DatabaseLoader;
use QBT\TranslationBundle\Tests\Unit\BaseUnitTestCase;

/**
 * DatabaseLoader tests.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DatabaseLoaderTest extends BaseUnitTestCase
{
    /**
     * @group loader
     */
    public function testLoad()
    {
        $em = $this->getMockSqliteEntityManager();
        $this->createSchema($em);
        $this->loadFixtures($em);

        $loader = new DatabaseLoader($em, 'QBT\\Bundle\\TranslationBundle\\Entity\\TransUnit');

        $catalogue = $loader->load(null, 'it');
        $this->assertInstanceOf('Symfony\Component\Translation\MessageCatalogue', $catalogue);
        $this->assertEquals(array(), $catalogue->all());
        $this->assertEquals('it', $catalogue->getLocale());

        $catalogue = $loader->load(null, 'fr');
        $expectedTranslations = array(
            'messages' => array(
                'key.say_goodbye' => 'au revoir',
                'key.say_wtf' => 'c\'est quoi ce bordel !?!',
            ),
        );
        $this->assertInstanceOf('Symfony\Component\Translation\MessageCatalogue', $catalogue);
        $this->assertEquals($expectedTranslations, $catalogue->all());
        $this->assertEquals('fr', $catalogue->getLocale());

        $catalogue = $loader->load(null, 'en', 'superTranslations');
        $expectedTranslations = array(
            'superTranslations' => array(
                'key.say_hello' => 'hello',
            ),
        );
        $this->assertInstanceOf('Symfony\Component\Translation\MessageCatalogue', $catalogue);
        $this->assertEquals($expectedTranslations, $catalogue->all());
        $this->assertEquals('en', $catalogue->getLocale());
    }
}