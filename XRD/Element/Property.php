<?php

namespace AssembleeVirtuelle\ManaBundle\XRD\Element;

/**
 * This file is part of the ManaBundle, a WebFinger library for Symfony
 *
 * PHP version 7
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  ManaBundle
 * @subpackage XRD
 * @author   Michel Cadennes <michel.cadennes@assemblee-virtuelle.org>
 * @license  https://opensource.org/licenses/GPL-3.0 GNU General Public License v3
 * @link https://github.com/assemblee-virtuelle/ManaBundle/tree/master/XRD/README.md
 * @version 0.1.0
 */

/*
 * A simple class for representing an object consisting of one key and one value.
 * Should fit a ‘property’ element of XRD vocabulary which is not defined in OASIS specification.
 */
final class Property
{
    /**
     * Value of the property.
     *
     * @property string|null
     */
    protected $value;

    /**
     * Type of the propery.
     *
     * @property string
     */
    protected $type;

    /**
     * Create a new instance
     *
     * @param string $type  String representing the property type
     * @param string $value Value of the property, may be NULL
     */
    public function __construct($type = null, $value = null)
    {
        $this->type  = $type;
        $this->value = $value;
    }

    public function __get($property)
    {
      if (\in_array($property, ['type', 'value'])) {
        return $this->$property;
      }

      throw new \Exception('Properties only know ‘type’ and ‘value’ properties');
    }

    public function __set($property, $value)
    {
      if (\in_array($property, ['type', 'value'])) {
        return $this->$property = $value;
      }

      throw new \Exception('Properties only know ‘type’ and ‘value’ properties');
    }
}
