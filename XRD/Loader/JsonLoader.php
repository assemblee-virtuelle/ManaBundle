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

 use AssembleeVirtuelle\ManaBundle\XRD\Element\Link;
 use AssembleeVirtuelle\ManaBundle\XRD\Element\Property;
 use AssembleeVirtuelle\ManaBundle\XRD\XRD;
 use AssembleeVirtuelle\ManaBundle\XRD\PropertyListAccess;
 use AssembleeVirtuelle\ManaBundle\XRD\Exception\LoaderException;

/**
 * Loads XRD data from a JSON file
 *
 * @category XML
 * @package  XML_XRD
 * @author   Christian Weiske <cweiske@php.net>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL
 * @version  Release: @package_version@
 * @link     http://pear.php.net/package/XML_XRD
 */
class JsonLoader extends AbstractLoader implements LoaderInterface
{
    /**
     * Loads the contents of the given file
     *
     * @param string $file Path to an JRD file
     *
     * @return void
     *
     * @throws LoaderException When the JSON is invalid or cannot be loaded
     */
    public function loadFromFile(string $file)
    {
        $json = file_get_contents($file);
        if ($json === false) {
            throw new LoaderException(
                'Error loading JRD file: ' . $file,
                LoaderException::LOADING_ERROR
            );
        }
        return $this->loadFromString($json);
    }

    /**
     * Loads the contents of the given string
     *
     * @param string $json JSON string
     *
     * @return void
     *
     * @throws LoaderException When the JSON is invalid or cannot be loaded
     */
    public function loadFromString(string $json)
    {
        if ($json == '') {
            throw new LoaderException(
                'Error loading JRD: string empty',
                LoaderException::LOADING_ERROR
            );
        }

        $obj = json_decode($json);
        if (\json_last_error() === 0) {
            return $this->build($obj);
        }

        $constants = get_defined_constants(true);
        $json_errors = [];
        foreach ($constants['json'] as $name => $value) {
            if (!strncmp($name, 'JSON_ERROR_', 11)) {
                $json_errors[$value] = $name;
            }
        }
        throw new LoaderException(
            'Error loading JRD: ' . $json_errors[json_last_error()],
            LoaderException::LOADING_ERROR
        );
    }

    /**
     * Loads the JSON object into the classes' data structures
     *
     * @param stdClass $j JSON object containing the whole JSON document
     *
     * @return void
     */
    public function build($object)
    {
        if (isset($object->subject)) {
            $this->xrd->subject = (string)$object->subject;
        }
        if (isset($object->aliases)) {
            foreach ($object->aliases as $jAlias) {
                $this->xrd->aliases[] = (string)$jAlias;
            }
        }

        if (isset($object->links)) {
            foreach ($object->links as $jLink) {
                $this->xrd->links[] = $this->buildLink($jLink);
            }
        }

        $this->buildProperties($this->xrd, $object);

        if (isset($object->expires)) {
            $this->xrd->expires = strtotime($object->expires);
        }
    }

    /**
     * Loads the Property elements from XML
     *
     * @param PropertyListAccess $store Data store where the properties get stored
     * @param stdClass           $jrd   JRD element
     *
     * @return boolean True when all went well
     */
    protected function buildProperties(PropertyListAccess $store, \stdClass $jrd)
    {
        if (!isset($jrd->properties)) {
            return true;
        }

        foreach ($jrd->properties as $key => $value) {
            $store->properties[] = new Property(
                $key, (string)$value
            );
        }

        return true;
    }

    /**
     * Create a link element object from XML element
     *
     * @param object $jLink JSON link object
     *
     * @return Link Created link object
     *
     * @todo Ajouter le filtrage sur des URN valides
     */
    protected function buildLink(\stdClass $jsonLink)
    {
        $link = new Link();
        foreach (array('rel', 'type', 'href', 'template') as $attr) {
            if (isset($jsonLink->$attr)) {
              //TODO Ajouter le filtrage sur des URN valides
              if (
                $attr == 'href' && false
                  // && ! filter_var($jsonLink->$attr, FILTER_VALIDATE_URL)
              ) {
                throw new \Exception(
                  "La cible du lien doit être un URL valide : ". $jsonLink->$attr
                );
              } else {
                $link->$attr = (string)$jsonLink->$attr;
              }
            }
        }

        if (isset($jsonLink->titles)) {
            foreach ($jsonLink->titles as $lang => $title) {
                if (!isset($link->titles[$lang])) {
                    $link->titles[$lang] = (string)$title;
                }
            }
        }
        $this->buildProperties($link, $jsonLink);

        return $link;
    }

}
