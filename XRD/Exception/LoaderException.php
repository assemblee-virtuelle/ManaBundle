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

namespace AssembleeVirtuelle\ManaBundle\XRD\Exception;

/**
 * Exception that's thrown when loading the XRD fails.
 */
class LoaderException extends \Exception implements ExceptionInterface
{
  /**
   * The document namespace is not the XRD 1.0 namespace
   */
  const DOCUMENT_NAMESPACE = 100;

  /**
   * The document root element is not XRD
   */
  const DOCUMENT_ROOT = 101;

  /**
   * Error loading the XML|JSON file|string
   */
  const LOADING_ERROR = 102;

  /**
   * Unsupported XRD file/string type (no loader)
   */
  const UNSUPPORTED_LOADER = 103;

  /**
   * Error opening file
   */
  const OPENING_FILE_ERROR = 104;

  /**
   * Detecting the file type failed
   */
  const UNKNOWN_TYPE = 105;

  /*
   * An inexistant property is requested
   */
  const PROPERTY_ERROR = 106;

  /*
   * Type mismatch for a given property
   */
  const TYPE_ERROR = 107;
}
