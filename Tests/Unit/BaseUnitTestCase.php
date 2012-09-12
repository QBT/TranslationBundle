<?php

namespace QBT\TranslationBundle\Tests\Unit;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Executor\MongoDBExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;
use Doctrine\Common\Annotations\AnnotationReader;

use QBT\TranslationBundle\Tests\Fixtures\TransUnitData;

/**
 * Base unit test class providing functions to create a mock entity manger, load schema and fixtures.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
abstract class BaseUnitTestCase extends \PHPUnit_Framework_TestCase
{
    const ENTITY_TRANS_UNIT_CLASS  = 'QBT\TranslationBundle\Entity\TransUnit';
    const ENTITY_TRANSLATION_CLASS = 'QBT\TranslationBundle\Entity\Translation';
    const ENTITY_FILE_CLASS        = 'QBT\TranslationBundle\Entity\File';

    const DOCUMENT_TRANS_UNIT_CLASS  = 'QBT\TranslationBundle\Document\TransUnit';
    const DOCUMENT_TRANSLATION_CLASS = 'QBT\TranslationBundle\Document\Translation';
    const DOCUMENT_FILE_CLASS        = 'QBT\TranslationBundle\Document\File';

    /**
     * Create the database schema.
     *
     * @param ObjectManager $om
     */
    protected function createSchema(ObjectManager $om)
    {
        if ($om instanceof \Doctrine\ORM\EntityManager) {
            $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($om);
            $schemaTool->createSchema($om->getMetadataFactory()->getAllMetadata());
        } else if ($om instanceof \Doctrine\ODM\MongoDB\DocumentManager) {
            $sm = new \Doctrine\ODM\MongoDB\SchemaManager($om, $om->getMetadataFactory());
            $sm->createCollections();
        }
    }

    /**
     * Load test fixtures.
     *
     * @param ObjectManager $om
     */
    protected function loadFixtures(ObjectManager $om)
    {
        if ($om instanceof \Doctrine\ORM\EntityManager) {
            $purger = new ORMPurger();
            $executor = new ORMExecutor($om, $purger);
        } else if ($om instanceof \Doctrine\ODM\MongoDB\DocumentManager) {
            $purger = new MongoDBPurger();
            $executor = new MongoDBExecutor($om, $purger);
        }

        $fixtures = new TransUnitData();
        $executor->execute(array($fixtures), false);
    }

    /**
     * EntityManager mock object together with annotation mapping driver and
     * pdo_sqlite database in memory
     *
     * @return EntityManager
     */
    protected function getMockSqliteEntityManager($mockCustomHydrator = false)
    {
        $cache = new \Doctrine\Common\Cache\ArrayCache();

        // annotation driver
        $reader = new AnnotationReader($cache);
        $annotationDriver = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader, array(
            __DIR__.'/../../vendor/doctrine/lib',
            __DIR__.'/../../Entity',
        ));

        // xml driver
        $xmlDriver = new \Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver(array(
            __DIR__.'/../../Resources/config/doctrine' => 'QBT\TranslationBundle\Entity',
        ));

        // configuration mock
        $config = $this->getMock('Doctrine\ORM\Configuration');
        $config->expects($this->any())
            ->method('getMetadataCacheImpl')
            ->will($this->returnValue($cache));
        $config->expects($this->any())
            ->method('getQueryCacheImpl')
            ->will($this->returnValue($cache));
        $config->expects($this->once())
            ->method('getProxyDir')
            ->will($this->returnValue(sys_get_temp_dir()));
        $config->expects($this->once())
            ->method('getProxyNamespace')
            ->will($this->returnValue('Proxy'));
        $config->expects($this->once())
            ->method('getAutoGenerateProxyClasses')
            ->will($this->returnValue(true));
        $config->expects($this->any())
            ->method('getMetadataDriverImpl')
            ->will($this->returnValue($xmlDriver));
        $config->expects($this->any())
            ->method('getClassMetadataFactoryName')
            ->will($this->returnValue('Doctrine\ORM\Mapping\ClassMetadataFactory'));
        $config->expects($this->any())
            ->method('getDefaultRepositoryClassName')
            ->will($this->returnValue('Doctrine\\ORM\\EntityRepository'));

        if ($mockCustomHydrator) {
            $config->expects($this->any())
                ->method('getCustomHydrationMode')
                ->with($this->equalTo('SingleColumnArrayHydrator'))
                ->will($this->returnValue('QBT\TranslationBundle\Hydrators\SingleColumnArrayHydrator'));
        }

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        $em = \Doctrine\ORM\EntityManager::create($conn, $config);

        return $em;
    }

    /**
     * Create a DocumentManager instance for tests.
     *
     * @return Doctrine\ODM\MongoDB\DocumentManager
     */
    protected function getMockMongoDbDocumentManager()
    {
        $prefixes = array(
            'QBT\TranslationBundle\Document' => __DIR__.'/../../Resources/config/doctrine'
        );
        $xmlDriver = new \Symfony\Bundle\DoctrineMongoDBBundle\Mapping\Driver\XmlDriver(array_values($prefixes));
        $xmlDriver->setNamespacePrefixes(array_flip($prefixes));

        $cache = new \Doctrine\Common\Cache\ArrayCache();

        $config = $this->getMock('Doctrine\ODM\MongoDB\Configuration');
        $config->expects($this->any())
            ->method('getMetadataCacheImpl')
            ->will($this->returnValue($cache));
        $config->expects($this->any())
            ->method('getQueryCacheImpl')
            ->will($this->returnValue($cache));
        $config->expects($this->once())
            ->method('getProxyDir')
            ->will($this->returnValue(sys_get_temp_dir()));
        $config->expects($this->once())
            ->method('getProxyNamespace')
            ->will($this->returnValue('Proxy'));
        $config->expects($this->once())
            ->method('getAutoGenerateProxyClasses')
            ->will($this->returnValue(true));
        $config->expects($this->any())
            ->method('getMetadataDriverImpl')
            ->will($this->returnValue($xmlDriver));
        $config->expects($this->any())
            ->method('getClassMetadataFactoryName')
            ->will($this->returnValue('Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory'));
        $config->expects($this->any())
            ->method('getDefaultDB')
            ->will($this->returnValue('qbt_translation_bundle_test'));
        $config->expects($this->any())
            ->method('getHydratorDir')
            ->will($this->returnValue(sys_get_temp_dir()));
        $config->expects($this->any())
            ->method('getHydratorNamespace')
            ->will($this->returnValue('Doctrine\ODM\MongoDB\Hydrator'));
        $config->expects($this->any())
            ->method('getAutoGenerateHydratorClasses')
            ->will($this->returnValue(true));
        $config->expects($this->any())
            ->method('getDefaultCommitOptions')
            ->will($this->returnValue(array()));
        $config->expects($this->any())
            ->method('getMongoCmd')
            ->will($this->returnValue('$'));

        $options = array(
            'connect'  => true,
            'username' => 'travis',
            'password' => 'qbt',
        );
        $conn = new \Doctrine\MongoDB\Connection(null, $options, $config);

        $dm = \Doctrine\ODM\MongoDB\DocumentManager::create($conn, $config);

        return $dm;
    }
}