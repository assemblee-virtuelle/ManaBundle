<?php

namespace AssembleeVirtuelle\ManaBundle\Tests\XRD\Element;

use PHPUnit\Framework\TestCase;

use AssembleeVirtuelle\ManaBundle\XRD\Element\Property;

final class PropertyTest extends TestCase
{

  /*
   * @var Property The instance to be tested
   */
  protected $property = null;

  public function setUp()
  {
    $this->property = new Property('property-type', 'property-value');
  }

  public function typeProvider()
  {
    return [
      'Correct type' => ['type', 'property-type', true],
      'Incorrect type' => ['type', 'property-another', false],
      'Correct value' => ['value', 'property-value', true],
      'Incorrect value' => ['value', 'property-another', false]
    ];
  }

  /**
   * Verifies that the Property constructor built a correct object
   *
   * @dataProvider typeProvider
   */
  public function testGetProperty(string $property, string $expected, bool $truth)
  {
      $result = $this->property->$property;
      if ($truth) {
        $this->assertEquals($result, $expected);
      } else {
        $this->assertNotEquals($result, $expected);
      }
  }

  /**
   * The ‘unknown’ property should rise an Exception
   */
  public function testGetUnknownProperty()
  {
    $this->expectException(\Exception::class);
    $result = $this->property->unknown;
  }
}
