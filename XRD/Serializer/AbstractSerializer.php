<?php

/**
* This file is part of the ManaBundle, a WebFinger library for Symfony
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

namespace AssembleeVirtuelle\ManaBundle\XRD\Serializer;

use AssembleeVirtuelle\XRD\XRD;
use AssembleeVirtuelle\XRD\Loader\LoaderInterface;
use AssembleeVirtuelle\XRD\Exception\SerializerException;
use AssembleeVirtuelle\XRD\Exception\LoaderException;

abstract class AbstractSerializer
{
  /**
   * Liste des attributs à la racine des ressources XRD
   *
   * @var array
   */
  const XRD_ATTRIBUTES = ['expires', 'subject', 'aliases', 'properties', 'links'];

  /**
   * Liste des attributs pour les éléments ‘link’ des ressources XRD
   * 
   * @var array
   */
  const LINK_ATTRIBUTES = ['rel', 'href', 'type', 'properties', 'titles', 'templates'];

  /**
  * XRD data storage
  *
  * @var XRD
  */
  protected $xrd;

  /**
  * Init object with xrd object
  *
  * @param XRD $xrd Data storage the data are fetched from
  */
  public function __construct(XRD $xrd)
  {
    $this->xrd = $xrd;
  }

  /**
  * Convert the XRD data into a string of the given type
  *
  * @param string $type File type: xml or json
  *
  * @return string Serialized data
  */
  public function to(string $type)
  {
    return (string)$this->getSerializer($type);
  }

  /**
  * Creates a XRD loader object for the given type
  *
  * @param string $type File type: xml or json
  *
  * @return LoaderInterface
  */
  protected function getSerializer($type)
  {
    $class = ucfirst($type).'Serializer';
    if (class_exists($class)) {
      return new $class($this->xrd);
    }

    throw new SerializerException(
      'No serializer for type "' . $type . '"',
      LoaderException::NO_LOADER
    );
  }
}
