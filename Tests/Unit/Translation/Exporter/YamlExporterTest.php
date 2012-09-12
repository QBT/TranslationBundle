<?php

namespace QBT\TranslationBundle\Tests\Unit\Translation\Exporter;

use QBT\TranslationBundle\Translation\Exporter\YamlExporter;

/**
 * YamlExporter tests.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class YamlExporterTest extends \PHPUnit_Framework_TestCase
{
    private $outFileName = '/file.out';

    public function tearDown()
    {
        $outFile = __DIR__.$this->outFileName;

        if (file_exists(__DIR__.$this->outFileName)) {
            unlink(__DIR__.$this->outFileName);
        }
    }

    /**
     * @group exporter
     */
    public function testExport()
    {
        $outFile = __DIR__.$this->outFileName;

        $exporter = new YamlExporter();

        // export empty array
        $exporter->export($outFile, array());
        $expectedContent = '{  }';
        $this->assertEquals($expectedContent, file_get_contents($outFile));

        // export array with values
        $exporter->export($outFile, array(
            'key.a' => 'aaa',
            'key.b' => 'bbb',
            'key.c' => 'ccc',
        ));
        $expectedContent = <<<C
key.a: aaa
key.b: bbb
key.c: ccc

C;
        $this->assertEquals($expectedContent, file_get_contents($outFile));
    }
}