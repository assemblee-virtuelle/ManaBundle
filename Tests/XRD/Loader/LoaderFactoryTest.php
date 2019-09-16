<?php

namespace AssembleeVirtuelle\ManaBundle\Tests\XRD\Loader;

use PHPUnit\Framework\TestCase;

use AssembleeVirtuelle\ManaBundle\Serializer\Serializer;
use AssembleeVirtuelle\ManaBundle\XRD\Loader\LoaderFactory;
use AssembleeVirtuelle\ManaBundle\XRD\Loader\JsonLoader;
use AssembleeVirtuelle\ManaBundle\XRD\Loader\XmlLoader;
use AssembleeVirtuelle\ManaBundle\XRD\Exception\LoaderException;
use AssembleeVirtuelle\ManaBundle\XRD\XRD;

class LoaderFactoryTest extends TestCase
{
    const JSON_FILE = '/Library/WebServer/Documents/sf-tylopilus/public/files/xrd.json';

    const XML_FILE = '/Library/WebServer/Documents/sf-tylopilus/public/files/xrd.xml';

    /*
     * @var LoaderFactory An object to run the tests upon
     */
    protected $loader;

    public function __construct($name = NULL, array $data = array(), $dataName = '')
    {
      // $this->preSetUp();
      parent::__construct($name, $data, $dataName);
    }

    public static function setUpBeforeClass()
    {
      var_dump('LoaderFactory');
    }

    /**
     * Prepares a new factory before each test
     */
    public function setUp()
    {
      $this->loader = new LoaderFactory();
    }

    public function tearDown()
    {
      unset($this->loader);
    }

    /**
     * Return the string 'json' if a JSON string is given as an argument
     */
    public function testJsonFormatFromString()
    {
        $result = $this->loader->guessFormatFromString('{"a":25}');

        $this->assertEquals('json', $result, "Le format devrait être ‘json’");
    }

    /**
     * Return the string 'xml' if an XML string is given as an argument
     */
    public function testXmlFormatFromString()
    {
        $result = $this->loader->guessFormatFromString('<?xml version="2.0"><book></book>');

        $this->assertEquals('xml', $result);
    }

    /**
     * Format non reconnu
     *
     * @expectException LoaderException
     * @expectExceptionMessage Detecting file type failed
     */
    public function testUnknownFormatFromString()
    {
      $this->expectException(LoaderException::class);
      $result = $this->loader->guessFormatFromString('Un chaîne de caractères au hasard');
    }

    /**
     * Return the string 'json' if a JSON file is given as an argument
     */
    public function testJsonFormatFromFile()
    {
        $result = $this->loader->guessFormatFromFile(self::JSON_FILE);

        $this->assertEquals('json', $result);
    }

    /**
     * Return the string 'json' if an XMl file is given as an argument
     */
    public function testXmlFormatFromFile()
    {
        $result = $this->loader->guessFormatFromFile(self::XML_FILE);

        $this->assertEquals('xml', $result);
    }

    /**
     * Return an error when the file isn't JSON nor XMl
     */
    public function testUnsupportedFormatFromFile()
    {
        $this->expectException(LoaderException::class);
        $result = $this->loader->guessFormatFromFile('/Library/WebServer/Documents/sf-tylopilus/public/index.php');
    }

    public function stringProvider()
    {
      var_dump('stringProvider');
      return [
        'chaîne JSON explicite' => ['{"a":25}', XRD::XRD_STRING_SOURCE, 'json', XRD::XRD_STRING_SOURCE],
        'chaîne XML explicite' => ['<?xml version="2.0"><book></book>', XRD::XRD_STRING_SOURCE, 'xml', XRD::XRD_STRING_SOURCE],
        'chaîne JSON implicite' => ['{"a":25}', null, 'json', XRD::XRD_STRING_SOURCE],
        'chaîne XML implicite' => ['<?xml version="2.0"><book></book>', null, 'xml', XRD::XRD_STRING_SOURCE]
      ];
    }

    public function fileProvider()
    {
      return [
        'fichier JSON explicite' => [self::JSON_FILE, XRD::XRD_FILE_SOURCE, 'json', XRD::XRD_FILE_SOURCE],
        'fichier XML explicite' => [self::XML_FILE, XRD::XRD_FILE_SOURCE, 'xml', XRD::XRD_FILE_SOURCE],
        'chaîne JSON implicite' => [self::JSON_FILE, null, 'json', XRD::XRD_FILE_SOURCE],
        'fichier XML implicite' => [self::XML_FILE, null, 'xml', XRD::XRD_FILE_SOURCE]
      ];
    }

    /**
     * Verifies that a string format is correctly detected
     *
     * @dataProvider stringProvider
     * @dataProvider fileProvider
     */
    public function testGuessFormat(string $source, $type, string $expectedFormat, int $expectedType)
    {
      // list($source, $type, $expectedFormat, $expectedType) = ['{"a":25}', null, 'json', XRD::XRD_STRING_SOURCE];

      $loader = $this->getMockForAbstractClass(LoaderFactory::class);
      $caller = function (string $source, $type) {
        return $this->guessDataFormat($source, $type);
      };
      $guess = $caller->bindTo($loader, $loader);

      $result = $guess($source, $type);

      $this->assertInternalType('array', $result);
      $this->assertArrayHasKey('format', $result, "Le tableau n’a pas de clef ‘format’'");
      $this->assertArrayHasKey('type', $result, "Le tableau n’a pas de clef ‘type’'");
      $this->assertEquals($expectedFormat, $result['format'], "Le format détecté devrait être ‘json’");
      $this->assertEquals($expectedType, $result['type'], "La source devrait être une chaîne de caractères");
    }


    public function sampleProvider()
    {
      return [
        'JSON explicite' => ['{"a":25}', 'json', XRD::XRD_STRING_SOURCE, JsonLoader::class],
        'JSON implicite' => ['{"a":25}', null, null, JsonLoader::class]
      ];
    }

    /**
     * Verifies that an instance of the correct Loader class is created
     *
     * @dataProvider sampleProvider
     *
     */
    public function testCreate(string $str, $format, $type, string $class)
    {
      // list($str, $format, $type, $class) = ['{"a":25}', null, null, JsonLoader::class];
      $xrd = new XRD(new Serializer(), new LoaderFactory());
      $instance = $this->loader->create($xrd, $str, $format, $type);

      $this->assertThat($instance, $this->isInstanceOf($class), "Le résultat n'est pas une inatence de $class");
      $this->assertThat($instance->xrd, $this->isInstanceOf(XRD::class), "La propriété ‘xrd’ n'est pas une instance de la classe XRD");
    }
}
