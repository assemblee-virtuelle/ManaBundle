<?php

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

namespace AssembleeVirtuelle\ManaBundle\XRD\Loader;

use AssembleeVirtuelle\ManaBundle\Element\Link;
use AssembleeVirtuelle\ManaBundle\Element\Property;
use AssembleeVirtuelle\ManaBundle\XRD\XRD;
use AssembleeVirtuelle\ManaBundle\Exception\LoaderException;

/*
 * Loaders are in charge of retrieving, validating and parsing XRD resources.
 */
abstract class AbstractLoader
{
  /**
   * @var XRD An XRD object
   */
  protected $xrd;

  /**
   * @var int The type of source : should be 0|1 (file or raw string)
   */
  protected $type;

  /**
   * Init a loader with xrd object
   *
   * @param XRD $xrd Data storage the JSON data get loaded into
   */
  public function __construct(XRD $xrd = null)
  {
    $this->xrd = $xrd;
  }

  /**
  * Returns the value of the requested property
  *
  * @param  string $property
  *
  * @return mixed
  *
  * @throws \Exception
  */
  function __get(string $property)
  {
    switch ($property) {
      case 'xrd':
      return $this->xrd;

      case 'type':
      return $this->type;

      default:
      throw new \Exception(
        'This property doesn’t exist',
        LoaderException::PROPERTY_ERROR
      );
    }
  }

  /**
  * Returns the value of the requested property
  *
  * @param  string $property
  *
  * @return mixed
  *
  * @throws \Exception
  */
  function __set(string $property, $value)
  {
    switch ($property) {
      case 'xrd':
        if ($value instanceof XRD) {
          $this->xrd = $value;
          return;
        } else {
          throw new LoaderException(
            "The type of the value isn't compatible with '$property'",
            LoaderException::TYPE_ERROR
          );
        }

      case 'type':
        if (in_array($value, [XRD::XRD_FILE_SOURCE, XRD::XRD_STRING_SOURCE], true)) {
          $this->type = $value;
          return;
        } else {
          throw new LoaderException(
            "The type of the value isn't compatible with '$property'",
            LoaderException::TYPE_ERROR
          );
        }

      default:
    }

    throw new LoaderException(
      "The property '$property' doesn’t exist",
      LoaderException::PROPERTY_ERROR
    );
  }

  /**
  * Loads an XRD profile from a source dependingon the type of the source.
  * This can be either a file (possibly reched over the network) or a raw string.
  *
  * @param string $source Source of information
  * @param int    $type   Type of the source : file or raw string
  *
  * @return [type]
  */
  public function load(string $source, int $type)
  {
    switch ($type) {
      case XRD_FILE_SOURCE:
      return $this->loadFromFile($source);
      break;

      case XRD_STRING_SOURCE:
      return $this->loadFromString($source);

      default:
    }
  }

}
