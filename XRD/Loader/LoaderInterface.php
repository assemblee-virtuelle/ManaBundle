<?php

/**
 * This file is part of the ManaBundle, a WebFinger library for Symfony
 *
 * PHP version 7
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @category XML
 * @package  ManaBundle
 * @author   Michel Cadennes <michel.cadennes@assemblee-virtuelle.org>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL
 * @link
 */

namespace AssembleeVirtuelle\ManaBundle\XRD\Loader;

/**
 * @author Michel Cadennes <michel.cadennes@assemblee-virtuelle.org>
 */
interface LoaderInterface
{
  /**
  * Loads an XRD profile from a source
  *
  * @param string $source Source of information
  * @param int $type type of the source : file or raw string
  *
  * @return void
  */
  public function load(string $source, int $type);

  /**
   * Loads the contents of the given file
   *
   * @param string $file Path to an JRD file
   *
   * @return void
   *
   * @throws LoaderException When the JSON is invalid or cannot be loaded
   */
  public function loadFromFile($file);

  /**
   * Loads the contents of the given string
   *
   * @param string $json JSON string
   *
   * @return void
   *
   * @throws LoaderException When the JSON is invalid or cannot be loaded
   */
  public function loadFromString(string $json);

  /**
  * Buildsan XRD object based on the structure of the argument
  *
  * @param stdClass $object object containing the whole XRD description
  *
  * @return void
  */
  public function build(stdClass $object);

  /**
   * Loads the Property elements
   *
   * @param object $store Data store where the properties get stored
   * @param object $j     JSON element with "properties" variable
   *
   * @return boolean True when all went well
   */
  protected function loadProperties (PropertyAccess $store, stdClass $j);

  /**
   * Create a link element object from XML element
   *
   * @param stdClass $j JSON link object
   *
   * @return Link Created link object
   */
  protected function loadLink (stdClass $j);

}
