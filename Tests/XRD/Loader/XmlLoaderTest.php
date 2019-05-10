<?php

namespace AssembleeVirtuelle\ManaBundle\XRD\Tests\Loader;

use PHPUnit\Framework\TestCase;

use AssembleeVirtuelle\ManaBundle\XRD\Serializer\Serializer;
use AssembleeVirtuelle\ManaBundle\XRD\Loader\LoaderFactory;
use AssembleeVirtuelle\ManaBundle\XRD\Loader\XmlLoader;
use AssembleeVirtuelle\ManaBundle\XRD\XRD\XRD;
use AssembleeVirtuelle\ManaBundle\XRD\Element\Link;
use AssembleeVirtuelle\ManaBundle\XRD\Element\Property;
use AssembleeVirtuelle\ManaBundle\Exception\LoaderException;

class XmlLoaderTest extends TestCase
{
  const XRD_PROFILE = "";

  /**
  * An loader accepting XML resources
  * @var XMLLoader
  */
  protected $loader;

  public static function setUpBeforeClass()
  {
  }

  protected function setUp()
  {
    // $xrd = new XRD(new Serializer(), new LoaderFactory());
    $this->loader = new XmlLoader($this->createMock(XRD::class));
  }

  /**
  * Provides a XML element
  *
  * @return \DOMElement
  *
  * @example <Title xml:lang="fr">ActivityPub</Title>
  */
  public function titleProvider()
  {
    $doc = new \DOMDocument();
    $xTitle = new \DOMElement('Title', 'ActitityPub');
    $doc->appendChild($xTitle);
    $xTitle->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:lang', 'fr');

    return ["Title XRD Element" => [$xTitle]];
  }

  /**
  * Tries to transform a 'Title' XRD element into PHP array
  *
  * @param  DOMElement $xTitle A XRD Title
  *
  * @dataProvider titleProvider
  */
  public function testBuildTitle(\DOMElement $xTitle)
  {
    $loader = $this->createMock(XmlLoader::class);
    $caller = function (\DOMElement $xTitle) {
      return $this->buildTitle($xTitle);
    };
    $guess = $caller->bindTo($loader, $loader);

    $result = $guess($xTitle);

    $this->assertInternalType('array', $result, 'The ‘buildTitle’ method should return an array');
    $this->assertArrayHasKey('fr', $result, 'The array should have a ‘fr’ key');
  }

  /**
  * Provides a XML element
  *
  * @return \DOMElement
  *
  * @example <Title xml:lang="fr">ActivityPub</Title>
  */
  public function propertyProvider()
  {
    $doc = new \DOMDocument();
    $xProperty = new \DOMElement('Property', 'Hello');
    $doc->appendChild($xProperty);
    $xProperty->setAttribute('name', 'test-property');

    return ["Property XRD Element" => [$xProperty]];
  }

  /**
  * Tries to transform a 'Title' XRD element into PHP array
  *
  * @param  DOMElement $xTitle A XRD Title
  *
  * @dataProvider propertyProvider
  */
  public function testBuildProperty(\DOMElement $xProperty)
  {
    $result = ($this->accessProtectedMethod(XmlLoader::class, 'buildProperty'))($xProperty);

    $this->assertInstanceOf(Property::class, $result, 'The ‘buildTitle’ method should return an instance of class ‘Property’');
    $this->assertObjectHasAttribute('type', $result, 'The object should have a ‘type’ property');
    $this->assertObjectHasAttribute('value', $result, 'The object should have a value’ property');
    $this->assertEquals('test-property', $result->type, 'The value of the name should be ’Hello');
    $this->assertEquals('Hello', $result->value, 'The value of the name should be ’Hello');
  }

  /**
  * Provides a XML element
  *
  * @return \DOMElement
  *
  * @example <Title xml:lang="fr">ActivityPub</Title>
  */
  public function flawedPropertyProvider()
  {
    $xProperty = new \DOMElement('Property', 'Hello');

    return ["Property XRDElement without name — Incorrect" => [$xProperty]];
  }

  /**
  * Tries to transform a 'Title' XRD element into PHP array
  *
  * @param  DOMElement $xTitle A XRD Title
  *
  * @dataProvider flawedPropertyProvider
  */
  public function testBuildFlawedProperty(\DOMElement $xProperty)
  {
    $this->expectException(\Exception::class, 'The XML Element doesn‘t havea ‘name’ attribute');

    $result = ($this->accessProtectedMethod(XmlLoader::class, 'buildProperty'))($xProperty);
  }

  /**
  * Provides a XML element
  *
  * @return \DOMElement
  *
  * @example <Title xml:lang="fr">ActivityPub</Title>
  */
  public function LinkProvider()
  {
    $doc = new \DOMDocument();
    $xLink = new \DOMElement('Link');
    $doc->appendChild($xLink);
    $xLink->setAttribute('href', 'http://example.com');
    $xLink->setAttribute('rel', 'lrdd');
    $xLink->setAttribute('type', 'application/xrd+xml');
    $xLink->setAttribute('template', 'https://mamot.fr/.well-known/webfinger?resource={uri}');
    $xProperty = new \DOMElement('Property', 'Hello');
    $xLink->appendChild($xProperty);
    $xProperty->setAttribute('name', 'test-property');
    $xTitle = new \DOMElement('Title', 'ActivityPub');
    $xLink->appendChild($xTitle);
    $xTitle->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:lang', 'fr');

    return ["Link XRD Element" => [$xLink]];
  }

  /**
  * Tries to transform a 'Title' XRD element into PHP array
  *
  * @param  DOMElement $xTitle A XRD Link
  *
  * @dataProvider linkProvider
  */
  public function testBuildLink(\DOMElement $xLink)
  {
    $result = ($this->accessProtectedMethod(XmlLoader::class, 'buildLink'))($xLink);

    $this->assertInstanceOf(Link::class, $result, 'The ‘buildLink’ method should return an instance of class Link');
    $this->assertEquals('lrdd', $result->rel, 'The value of the name should be ’lrdd');
    $this->assertEquals('application/xrd+xml', $result->type, 'The value of the name should be ’application/xrd+xml');
    $this->assertEquals('http://example.com', $result->href, 'The value of the ‘href’ property should be ’http://example.com');
    $this->assertInternalType('array', $result->titles, 'The ‘titles’ property should be an array');
    $this->assertcount(1, $result->titles, 'The ‘titles’ property should contain one element');
    $this->assertInternalType('array', $result->properties, 'The ‘properties’ property should be an array');
    $this->assertcount(1, $result->properties, 'The ‘properties’ property should contain one (1) element');
  }

  /**
  * Provides a complete XRD resource element
  *
  * @return \DOMDocument
  *
  * @example <Title xml:lang="fr">ActivityPub</Title>
  */
  public function documentProvider()
  {
    $doc = new \DOMDocument();
    $root = new \DOMElement('XRD');
    $doc->appendChild($root);
    $root->appendChild(new \DOMElement('Subject', 'XRD profile'));
    $root->appendChild(new \DOMElement('Expires', '2019-05-31 00:00:00'));
    $root->appendChild(new \DOMElement('Alias', 'https://example.com/@example'));
    $root->appendChild(new \DOMElement('Alias', 'https://virtual-assembly.com/zorglub'));
    // $p1 = new \DOMElement('Property', 'v1');
    // $root->appendChild($p1);
    // $p1->setAttribute('name', 'test-p1');
    // $p2 = new \DOMElement('Property', 'v2');
    // $root->appendChild($p2);
    // $p1->setAttribute('name', 'test-p2');
    $xLink = new \DOMElement('Link');
    $root->appendChild($xLink);
    $xLink->setAttribute('href', 'http://example.com');
    $xLink->setAttribute('rel', 'lrdd');
    $xLink->setAttribute('type', 'application/xrd+xml');
    $xLink->setAttribute('template', 'https://mamot.fr/.well-known/webfinger?resource={uri}');

    return ["XRD document" => [$doc]];
  }

  /**
   * Tries to build an XRD object from a DOMDocument
   *
   * @dataProvider documentProvider
   */
  public function testBuild(\DOMDocument $document)
  {
    $this->loader->build($document);

    // $this->assertCount(2, $result->properties, "L’objet JRD devrait contenir 2 éléments Property");
    // $this->assertEquals('p1', $result->properties[0]->type, "Le type de la propriété devrait être ‘p1’");
    // $this->assertEquals('v1', $result->properties[0]->value, "La propriété ‘p1’ devrait avoir pour valeur ‘v1’");
    // $this->assertEquals('p2', $result->properties[1]->type, "Le type de la propriété devrait être ‘p2’");
    // $this->assertEquals('v2', $result->properties[1]->value, "La propriété ‘p2’ devrait avoir pour valeur ‘v2’");
    $this->assertEquals('XRD profile', $this->loader->xrd->subject);
    $this->assertInternalType('array', $this->loader->xrd->alias);
    $this->assertCount(2, $this->loader->xrd->alias, "L’objet XRD devrait contenir 2 Alias");
  }

  /**
   * Utility method to test protected methods
   *
   * @param  string $class  Name of the tested class
   * @param  string $method Name of thetested method
   * @return Function
   */
  protected function accessProtectedMethod(string $class, string $method)
  {
    $loader = $this->createMock($class);
    $caller = function (\DOMNode $xElement) use ($method) {
      return $this->$method($xElement);
    };
    $guess = $caller->bindTo($loader, $loader);

    return $guess;
  }


}
