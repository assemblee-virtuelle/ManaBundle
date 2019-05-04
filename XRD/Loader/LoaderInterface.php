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

namespace App\Loader;

interface LoaderInterface
{
  /**
  * Loads an XRD profile from a source
  *
  * @param string $source Source of data
  * @param int    $type   Type of the source : file or raw string
  *
  * @return void
  */
  public function load (string $source, int $type);

  /**
   * Loads the contents of the given file
   *
   * @param string $file Path to an JRD file
   *
   * @return void
   *
   * @throws LoaderException When the JSON is invalid or cannot be loaded
   */
  public function loadFromFile (string $file);

  /**
   * Loads the contents of the given string
   *
   * @param string $json JSON string
   *
   * @return void
   *
   * @throws LoaderException When the JSON is invalid or cannot be loaded
   */
  public function loadFromString (string $json);

  /**
  * Buildsan XRD object based on the structure of the argument
  *
  * @param stdClass $object object containing the whole XRD description
  *
  * @return void
  */
  public function build ($object);

  /**
   * Loads the Property elements
   *
   * @param PropertyListAccess $store Data store where the properties get saved
   * @param object             $j     An object with "properties" property
   *
   * @return boolean True when all went well
   */
  protected function loadProperties (PropertyListAccess $store, $j);

  /**
   * Creates a link element object
   *
   * @param object $j
   *
   * @return Link Created link object
   */
  protected function loadLink ($j);

}
