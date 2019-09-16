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

use AssembleeVirtuelle\ManaBundle\Exception\LoaderException;
use AssembleeVirtuelle\ManaBundle\XRD\Element\Link;
use AssembleeVirtuelle\ManaBundle\XRD\Element\Property;

class XmlLoader extends AbstractLoader implements LoaderInterface
{
    /**
     * XRD 1.0 namespace
     */
    const NS_XRD = 'http://docs.oasis-open.org/ns/xri/xrd-1.0';

    /*
     * XSD scheam to validate the file
     */
    const SCHEMA_URL = 'http://docs.oasis-open.org/xri/xrd/v1.0/os/xrd-1.0-os.xsd';

    /**
     * Loads the contents of the given file
     *
     * @param string $file Path to an XRD file
     *
     * @return void
     *
     * @throws LoaderException When the XML is invalid or cannot be
     *                                   loaded
     */
    public function loadFromFile(string $file)
    {
        libxml_use_internal_errors(true);
        $xmlDoc = new \DOMDocument();
        $xmlDoc->load($file);
        if ($xmlDoc === false) {
          // libxml_use_internal_errors($old);
            throw new LoaderException(
                'Error loading XML file: ' . libxml_get_last_error()->message,
                LoaderException::LOADING_ERROR
            );
        }

        if (!$xmlDoc->schemaValidate(SCHEMA_URL)) {
          throw new LoaderException(
              'Error loading XML file: ' . libxml_get_last_error()->message,
              LoaderException::LOADING_ERROR
          );
        }
        return $this->build($xmlDoc);
    }

    /**
     * Loads the contents of the given string
     *
     * @param string $xml XML string
     *
     * @return void
     *
     * @throws LoaderException When the XML is invalid or cannot be loaded
     */
    public function loadFromString(string $xml)
    {
        if ($xml == '') {
            throw new LoaderException(
                'Error loading XML string: string empty',
                LoaderException::LOADING_ERROR
            );
        }

        libxml_use_internal_errors(true);
        $xmlDoc = new \DOMDocument();
        $xmlDoc->loadXML($xml);
        if ($xmlDoc === false) {
          libxml_use_internal_errors($old);
            throw new LoaderException(
                'Error loading XML string: ' . libxml_get_last_error()->message,
                LoaderException::LOADING_ERROR
            );
        }

        if (!$xmlDoc->schemaValidate(SCHEMA_URL)) {
          throw new LoaderException(
              'Error loading XML file: ' . libxml_get_last_error()->message,
              LoaderException::LOADING_ERROR
          );
        }

        return $this->build($xmlDoc);
    }

    /**
     * Loads the XML element into the classes' data structures
     *
     * @param DOMDocument $xmlDoc XML element containing the whole XRD document
     *
     * @return void
     *
     * @throws LoaderException When the XML is invalid
     */
    public function build($xmlDoc)
    {
      $root = $root = $xmlDoc->getElementsByTagName('XRD')->item(0);
      $namespace = $root->namespaceURI;

      foreach ($root->childNodes as $element) {
        switch ($element->nodeName) {
          case 'Subject':
          case 'Alias':
            $this->xrd->subject = $element->textContent;
            break;

          case 'Expires':
            $this->xrd->subject = new \Datetime($element->textContent);
            break;

          case 'Link':
            $this->xrd->links[] = $this->buildLink($element);
            break;

          case 'Property':
            try {
              $store->properties[] = $this->buildProperty($element);
            } catch (\Exception $e) {
              throw new \Exception(
                'Couldn’t build the Property object due to wrong format'
              );
            }
            break;

          default:
            // code...
        }
      }

      $this->xrd->id = (string)$root->getAttributeNS('http://www.w3.org/XML/1998/namespace', 'id');
    }

    /**
     * Loads the Property elements from XML
     *
     * @param PropertyListAccess $store Data store where the properties get stored
     * @param DOMDocument $x     XML element
     *
     * @return boolean True when all went well
     */
    protected function buildProperties(PropertyListAccess $store, \DOMElement $xmlDoc)
    {
        foreach ($xmlDoc->getElementsByTagName('Property') as $xProperty) {
        }
    }

    /**
     * Create a link element object from XML element
     *
     * @param DOMElement $x XML link element
     *
     * @return Link Created link object
     */
    protected function buildLink(\DOMElement $xLink)
    {
        $link = new Link();

        foreach (array('rel', 'type', 'href', 'template') as $attr) {
            if (!empty($xLink->getAttribute($attr))) {
                $link->$attr = (string)$xLink->getAttribute($attr);
            }
        }

        foreach ($xLink->childNodes as $element) {
          switch ($element->nodeName) {
            case 'Title':
              $link->titles = array_merge($link->titles, $this->buildTitle($element));
              break;

            case 'Property':
              try {
                $link->properties[] = $this->buildProperty($element);
              } catch (\Exception $e) {

              }
              break;

            default:
              // code...
          }
        }

        return $link;
    }

    /**
     * Create a property element object from XRD Property element
     * The absence of 'name' attribute throws an exception
     *
     * @param DOMElement $x XML property element
     *
     * @return Property Created link object
     */
    protected function buildProperty(\DOMElement $xProperty)
    {
      $name = $xProperty->getAttribute('name');
      if ($name) {
        return new Property($name, $xProperty->textContent);
      } else {
        throw new \Exception(
          'The name is a required attribute of a Title element in XRD specification',
          1001
        );
      }
    }

    /**
     * Builds an associative array for a ttile given a specified language
     * The XML code of the language is the key of the array
     * When the language is not specified, the key is 'none'
     *
     * @param  DOMElement $xTitle An XRD Title element
     * @return array
     */
    protected function buildTitle(\DOMElement $xTitle)
    {
      $lang = $xTitle->getAttributeNS('http://www.w3.org/XML/1998/namespace', 'lang');
      $title = $xTitle->textContent;

      return [($lang ?: 'none') => $title];
  }
}
