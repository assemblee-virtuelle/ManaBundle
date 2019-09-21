<?php


namespace AssembleeVirtuelle\ManaBundle\XRD\Serializer;


use AssembleeVirtuelle\ManaBundle\XRD\XRD;

class SerializerFactory
{
    /**
     * Create new instance
     */
    public function __construct()
    {
    }


    public function create(XRD $xrd, string $str, string $format = null, int $type = null) : SerializerInterface
    {

    }
}