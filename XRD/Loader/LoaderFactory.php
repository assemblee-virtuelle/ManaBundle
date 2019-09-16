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

use LoaderInterface as Loader;
use AssembleeVirtuelle\ManaBundle\XRD\Loader\JsonLoader;
use AssembleeVirtuelle\ManaBundle\XRD\Loader\XmlLoader;
use AssembleeVirtuelle\ManaBundle\XRD\XRD;
use AssembleeVirtuelle\ManaBundle\XRD\Exception\LoaderException;

class LoaderFactory
{
    /**
     * Create new instance
     */
    public function __construct()
    {
    }

    /**
     * Creates a XRD loader object for the given type
     *
     * @param XRD $xrd An XRD object
     * @param string $str A string to be analyzed
     * @param string $format File type: xml or json
     * @param int $type source of data : file or raw string
     *
     * @return LoaderInterface
     */
     public function create(XRD $xrd, string $str, string $format = null, int $type = null) : LoaderInterface
     {
       if ($format === null) {
         list('format' => $format, 'type' => $type) = $this->guessDataFormat($str, $type);
       }

       $class = 'AssembleeVirtuelle\ManaBundle\\XRD\\Loader\\' . ucfirst($format) .'Loader';

       if (class_exists($class)) {
         $loader = new $class($xrd);
         $loader->type = $type;
       } else {
         throw new LoaderException(
           'No loader for XRD class "' . $class . '"',
           LoaderException::UNSUPPORTED_LOADER
         );
       }

       return $loader;
     }

     /**
      * Tries to guess the format of data (namely XML or JSON)
      * given a source which can be a file or a raw string.
      *
      * @param  string $source THe data to be tested
      * @param  int $type      The type of the source : file or raw string
      *
      * @return array         A couple of values format + type
      *
      * @throws LoaderException when detection fails.
      */
    protected function guessDataFormat(string $source, int $type = null) : array
    {
      if (!\is_null($type)) {
        switch ($type) {
          case XRD::XRD_FILE_SOURCE:
            return [
              'format' => $this->guessFormatFromFile($source),
              'type' => XRD::XRD_FILE_SOURCE
              ];
            break;

          case XRD::XRD_STRING_SOURCE:
            return [
              'format' => $this->guessFormatFromString($source),
              'type' => XRD::XRD_STRING_SOURCE
              ];
            break;

          default:
            throw new Exception(
                'Wrong source type : ',
                LoaderException::UNKNOWN_TYPE
            );
        }
      } else {
        try {
          return [
            'format' => $this->guessFormatFromString($source),
            'type' => XRD::XRD_STRING_SOURCE
            ];
        } catch (\Exception $e) {
          try {
            return [
              'format' => $this->guessFormatFromFile($source),
              'type' => XRD::XRD_FILE_SOURCE
              ];
          } catch (\Exception $e) {
            throw new LoaderException(
              'Wrong source type',
              LoaderException::UNKNOWN_TYPE,
              $e
            );
          }
        }
      }
    }

    /**
     * Tries to detect the file type (xml or json) from the file content
     *
     * @param string $file File name to check
     *
     * @return string File type ('xml' or 'json')
     *
     * @throws LoaderException When opening the file fails.
     */
    public function guessFormatFromFile($file)
    {
        if (!file_exists($file)) {
            throw new LoaderException(
                'Error loading XRD file: File does not exist',
                LoaderException::OPENING_FILE_ERROR
            );
        }
        $handle = fopen($file, 'r');
        if (!$handle) {
            throw new LoaderException(
                'Cannot open file to determine type',
                LoaderException::OPENING_FILE_ERROR
            );
        }

        $str = (string)fgets($handle, 10);
        fclose($handle);
        return $this->guessFormatFromString($str);
    }

    /**
     * Tries to detect the file type from the content of the file
     *
     * @param string $str Content of XRD file
     *
     * @return string File type ('xml' or 'json')
     *
     * @throws LoaderException When the type cannot be detected
     */
    public function guessFormatFromString(string $str) : string
    {
        if ($str[0] === "{") {
          return 'json';
        } else if (substr($str, 0, 5) == '<?xml') {
          return 'xml';
        }

        throw new LoaderException(
            'Detecting file type failed',
            LoaderException::UNKNOWN_TYPE
        );
    }


}
