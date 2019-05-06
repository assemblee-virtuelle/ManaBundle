<?php

namespace AssembleeVirtuelle\ManaBundle\Tests\Loader;

use AssembleeVirtuelle\ManaBundle\XRD\Serializer\Serializer;
use AssembleeVirtuelle\ManaBundle\XRD\Loader\LoaderFactory;
use AssembleeVirtuelle\ManaBundle\XRD\Loader\JsonLoader;
use AssembleeVirtuelle\ManaBundle\Exception\LoaderException;
use AssembleeVirtuelle\ManaBundle\XRD\XRD;
use AssembleeVirtuelle\ManaBundle\XRD\Element\Link;

use PHPUnit\Framework\TestCase;

class JsonLoaderTest extends TestCase
{
  const JSON_FILE = '/Library/WebServer/Documents/sf-tylopilus/public/files/xrd.json';

  const JRD_PROFILE = '{
    "subject":"acct:tchevengour@mamot.fr",
    "aliases":["https://mamot.fr/@tchevengour", "https://mamot.fr/users/tchevengour"],
    "links":
    [
      {"rel":"http://webfinger.net/rel/profile-page","type":"text/html","href":"https://mamot.fr/@tchevengour"},
      {"rel":"salmon","href":"https://mamot.fr/api/salmon/8828"}
    ],
    "properties" : {"p1" : "v1","p2": "v2"}
  }';

  protected $loader;

  protected function setUp()
  {
    $xrd = new XRD(new Serializer(), new LoaderFactory());
    $this->loader = new JsonLoader($xrd);
  }

  public function testBuildProperties()
  {
    $jrd = \json_decode(self::JRD_PROFILE);
    $xrd = new XRD(new Serializer(), new LoaderFactory());
    $loader = $this->createMock(JsonLoader::class);
    $caller = function ($x, $j) { return $this->buildProperties($x, $j); };
    $guess = $caller->bindTo($loader, $loader);

    $result = $guess($xrd, $jrd);

    $this->assertCount(2, $xrd->properties, "L’objet JRD devrait contenir 2 éléments Property");
    $this->assertEquals('p1', $xrd->properties[0]->type, "Le type de la propriété devrait être ‘p1’");
    $this->assertEquals('v1', $xrd->properties[0]->value, "La propriété ‘p1’ devrait avoir pour valeur ‘v1’");
    $this->assertEquals('p2', $xrd->properties[1]->type, "Le type de la propriété devrait être ‘p2’");
    $this->assertEquals('v2', $xrd->properties[1]->value, "La propriété ‘p2’ devrait avoir pour valeur ‘v2’");
  }

  /**
   * Tries to build a Link object from
   * @return [type] [description]
   */
  public function testBuildLink()
  {
    $jrd = \json_decode(self::JRD_PROFILE);
    $loader = $this->createMock(JsonLoader::class);
    $caller = function ($l) { return $this->buildLink($l); };
    $guess = $caller->bindTo($loader, $loader);

    $link = $jrd->links[0];
    $result = $guess($link);

    $this->assertInstanceOf(Link::class, $result);
    $this->assertEquals($result->type, 'text/html');
  }

  /**
   * Tries to build a complete XRD/JRD object
   */
  public function testBuild()
  {
    $this->loader->build(\json_decode(JsonLoaderTest::JRD_PROFILE));

    $this->assertEquals($this->loader->xrd->subject, "acct:tchevengour@mamot.fr", "La propriété ‘subject’ de l'objet XRD devrait être ‘cct:tchevengour@mamot.fr’");
  }

  public function testLoadFromString()
  {
    $result = $this->loader->loadFromString(self::JRD_PROFILE);

    $this->assertCount(2, $this->loader->xrd->properties);
  }

  public function testLoadFromFile()
  {
    $result = $this->loader->loadFromFile(self::JSON_FILE);

    $this->assertCount(2, $this->loader->xrd->properties);
  }


}
