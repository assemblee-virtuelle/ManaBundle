<?php

namespace AssembleeVirtuelle\ManaBundle\Tests\Loader;

use AssembleeVirtuelle\ManaBundle\XRD\Loader\AbstractLoader;
use AssembleeVirtuelle\ManaBundle\XRD\Exception\LoaderException;
use AssembleeVirtuelle\ManaBundle\XRD\XRD;
use AssembleeVirtuelle\ManaBundle\XRD\Loader\JsonLoader;

use PHPUnit\Framework\TestCase;

class AbstractLoaderTest extends TestCase
{
  protected $loader;

  /*
   * AbstractLoader is an abstract class, so we need to instanciate
   * one of its subclasses
   */
  public function setUp()
  {
    $this->loader = new JSONLoader();
  }

  public function typeProvider()
  {
    return [
      'Chaîne de caractères' => [XRD::XRD_STRING_SOURCE, XRD::XRD_STRING_SOURCE],
      'Fichier' => [XRD::XRD_FILE_SOURCE, XRD::XRD_FILE_SOURCE]
    ];
  }

  /**
   * @dataProvider typeProvider
   */
  public function testSetType(int $given, string $expected)
  {
    $this->loader->type = $given;

    // $this->assertObjectHasAttribute('type', $expected);
    $this->assertEquals($this->loader->type, $expected,"Le type de source n’a pas été correctement enregistré");
  }

  /**
   * Tries to set the 'type' property to an unsupported value
   */
  public function testSetUnknownType()
  {
    $this->expectException(LoaderException::class);
    $this->loader->type = "Type non reconnu";
  }

  /**
   * Tries to set a value to an invalid property
   */
  public function testSetUnknownProperty()
  {
    $this->expectException(LoaderException::class);
    $this->loader->invalidProperty = "Oups";
  }

  /**
   * Tries to set the 'xrd' property to an instance of class XRD
   */
  public function testSetXrd()
  {
    $this->loader->xrd = $this->createMock(XRD::class);

    $this->assertInstanceOf(XRD::class, $this->loader->xrd, "L’objet de classe XRD n’a pu être affecté à la propriété 'xrd'");
  }

  /**
   * Tries to set the 'xrd' property to an unsupported value
   */
  public function testSetUnknownXrd()
  {
    $this->expectException(LoaderException::class);
    $this->loader->xrd = false;
  }

}
