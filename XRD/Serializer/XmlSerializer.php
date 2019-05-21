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
use AssembleeVirtuelle\XRD\Element\Property;

class XmlSerializer extends AbstractSerializer
{

  protected $xmlOutput;

  public function __construct(XRD $xrd)
  {
      parent::__construct($xrd);
      $this->xmlOutput = new \XMLWriter();
  }

  public function __toString()
  {
    $hasXsi = false;
    $x = new \XMLWriter();
    $this->xmlOutput->openMemory();
    $this->WxmlHeader();

    $attrArray = [];
    foreach (XRD_ATTRIBUTES as $attribute) {
      if ($this->xrd->$attribute !== null) {
        $attrValue = $this->xrd->$attribute;
        switch ($attrValue) {
          case 'expires':
            $this->xmlOutput->writeElement(
              'Expires',
              (new \DateTime($attrValue, new DateTimeZone('Europe/London')))->format('Y-m-d\TH:i:s\Z')
            );
            break;

          case 'subject':
            $this->xmlOutput->writeElement('Subject', $attrValue);
            break;

          case 'aliases':
            foreach ($attrValue as $alias) {
              $this->xmlOutput->writeElement('Alias', $alias);
            }
            break;

          case 'properties':
            foreach ($attrValue as $property) {
              $this->writeProperty($property, $hasXsi);
            }
            break;

          case 'links':
            foreach ($attrValue as $link) {
              $this->xmlOutput->startElement('Link');
              foreach (LINK_ATTRIBUTES as $linkAttr) {
                if (!\is_null($link->$linkAttr)) {
                  switch ($linkAttr) {
                    case 'rel':
                    case 'type':
                    case 'href':
                      $this->xmlOutput->writeAttribute($linkAttr, $link->$linkAttr);
                      break;

                    case 'template':
                      if (\is_null($link->href)) {
                        $this->xmlOutput->writeAttribute($linkAttr, $link->$linkAttr);
                      }
                      break;

                    case 'titles':
                      foreach ($link->titles as $lang => $value) {
                        $this->xmlOutput->startElement('Title');
                        if (!\is_null($lang)) {
                          $this->xmlOutput->writeAttribute('xml:lang', $lang);
                        }
                        $this->xmlOutput->text($value);
                        $this->xmlOutput->endElement();
                      }
                      break;

                    case 'properties':
                      foreach ($link->properties as $property) {
                        $this->writeProperty($property, $hasXsi);
                      }
                    break;
                  }
                }
              }

              $this->xmlOutput->endElement();
            }
            break;
        }
      }
    }

    $this->xmlClose();

    return $this->flush();
  }

  protected function xmlHeader(\XMLWriter $x)
  {
    //no encoding means UTF-8
    //http://www.w3.org/TR/2008/REC-xml-20081126/#sec-guessing-no-ext-info
    $this->xmlOutput->startDocument('1.0', 'UTF-8');
    $this->xmlOutput->setIndent(true);
    $this->xmlOutput->startElement('XRD');
    $this->xmlOutput->writeAttribute('xmlns', 'http://docs.oasis-open.org/ns/xri/xrd-1.0');
    $this->xmlOutput->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    if ($this->xrd->id) {
      $this->xmlOutput->writeAttribute('xml:id', $this->xrd->id);
    }
  }

protected function flush()
{
  $s = $this->xmlOutput->flush();
  if (!$hasXsi) {
    $s = str_replace(
      ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"', '', $s
    );
  }

  return $s;
}

protected function xmlClose(\XMLWriter $x)
  {
    $this->xmlOutput->endElement();
    $this->xmlOutput->endDocument();
  }

  /**
   * Write a property in the XMLWriter stream output
   *
   * @param XMLWriter $x        Writer object to write to
   * @param Property  $property Property to write
   * @param boolean   &$hasXsi  If an xsi: attribute is used
   *
   * @return void
   */
  protected function writeProperty(Property $property, &$hasXsi)
  {
      $this->xmlOutput->startElement('Property');
      $this->xmlOutput->writeAttribute('type', $property->type);
      if ($property->value === null) {
          $this->xmlOutput->writeAttribute('xsi:nil', 'true');
          $hasXsi = true;
      } else {
          $this->xmlOutput->text($property->value);
      }
      $this->xmlOutput->endElement();
  }

}
