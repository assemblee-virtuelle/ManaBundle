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

class JsonSerializer extends AbstractSerializer
{
  const XRD_ATTRIBUTES = ['expires', 'subject', 'aliases', 'properties', 'links'];
  const LINK_ATTRIBUTES = ['rel', 'href', 'type', 'properties', 'titles', 'templates'];

  public function __toString()
  {
    $attrArray = [];
    foreach (XRD_ATTRIBUTES as $attribute) {
      if ($this->xrd->$attribute !== null) {
        $attrValue = $this->xrd->$attribute;
        switch ($attribute) {
          case 'expires':
            $attrArray['expires'] = (new \DateTime($attrValue, new DateTimeZone('Europe/London')))->format('Y-m-d\TH:i:s\Z');
            break;

          case 'subject':
            $attrArray['subject'] = $attrValue;
            break;

          case 'aliases':
            foreach ($attrValue as $alias) {
              $attrArray['aliases'][] = $alias;
            }
            break;

          case 'properties':
            foreach ($attrValue as $property) {
              $attrArray['properties'][$property->type] = $property->value;
            }
            break;

          case 'links':
            foreach ($attrValue as $link) {
              $linkArray = [];
              foreach (LINK_ATTRIBUTES as $linkAttr) {
                if (!\is_null($link->$linkAttr)) {
                  switch ($linkAttr) {
                    case 'rel':
                    case 'type':
                    case 'href':
                      $linkArray[$linkAttr] = $link->$linkAttr;
                      break;

                    case 'template':
                      if (\is_null($link->href)) {
                        $linkArray[$linkAttr] = $link->$linkAttr;
                      }
                      break;

                    case 'titles':
                      foreach ($link->titles as $lang => $value) {
                        if ($lang == null) {
                          $lang = 'default';
                        }
                        $linkArray['titles'][$lang] = $value;
                      }
                      break;

                      case 'properties':
                        foreach ($link->properties as $property) {
                          $linkArray['properties'][$property->type] = $property->$value;
                        }
                        break;

                    default:
                  }
                }
              }
              $attrArray['links'][] = $linkArray;
            }
            break;

          default:
        }
      }
    }

    return json_encode($attrArray);
  }
}
