<?php

namespace AssembleeVirtuelle\ManaBundle\XRD;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

use AssembleeVirtuelle\ManaBundle\XRD\Loader\LoaderFactory;

class XRD {

  /**
   *
   */
  const XRD_STRING_SOURCE = 0;

  /**
  *
  */
  const XRD_FILE_SOURCE = 1;

  /**
   * XRD file/string loading dispatcher
   *
   * @var XML_XRD_Loader
   */
  public $loader;

  /**
   * XRD serializing dispatcher
   *
   * @var Serializer
   */
  public $serializer;

  /**
   * XRD subject
   *
   * @var string
   */
  public $subject;

  /**
   * Array of subject alias strings
   *
   * @var array
   */
  public $aliases = array();

  /**
   * Array of link objects
   *
   * @var array
   */
  public $links = array();

  /**
   * Unix timestamp when the document expires.
   * NULL when no expiry date set.
   *
   * @var integer|null
   */
  public $expires;

  /**
   * xml:id of the XRD document
   *
   * @var string|null
   */
  public $id;


public function __construct(Serializer $serializer, LoaderFactory $loaderFactory)
{
  $encoders = [new XmlEncoder(), new JsonEncoder()];
  $normalizers = [new ObjectNormalizer()];

  $this->serializer = new Serializer($normalizers, $encoders);

  $this->loader = $loaderFactory;
}

public function load(string $source, int $type = XRD_FILE, string $format = 'xml')
{
  $loader = $this->loader->create($format);
  $response = $loader->load($source, $type);
}

/**
 * [describes description]
 * @param  string $uri [description]
 *
 * @return [type]      [description]
 */
public function describes(string $uri)
{
  if ($this->subject == $uri) {
      return true;
  }
  foreach ($this->aliases as $alias) {
      if ($alias == $uri) {
          return true;
      }
  }

  return false;
}

/**
 * [get description]
 *
 * @param  [type]  $rel          [description]
 * @param  [type]  $type         [description]
 * @param  boolean $typeFallback [description]
 * 
 * @return [type]                [description]
 */
public function get($rel, $type = null, $typeFallback = true)
{
  $links = $this->getAll($rel, $type, $typeFallback);
  if (count($links) == 0) {
      return null;
  }

  return $links[0];
}

public function getAll()
{

}

public function getIterator()
{

}

public function to(string $format)
{
  return $this->serializer->serialize($profile, $format);
}

}
