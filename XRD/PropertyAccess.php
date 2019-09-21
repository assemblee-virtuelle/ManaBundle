<?php


namespace AssembleeVirtuelle\ManaBundle\XRD;


use AssembleeVirtuelle\ManaBundle\XRD\Exception\LogicException;

class PropertyAccess implements \ArrayAccess
{
    /**
     * Array of property objects
     *
     * @var array
     */
    private $properties = array();

    /**
     * Check if the property with the given type exists
     *
     * Part of the ArrayAccess interface
     *
     * @param mixed $offset Property type to check for
     *
     * @return boolean True if it exists
     */
    public function offsetExists($offset) : bool
    {
        foreach ($this->properties as $prop) {
            if ($prop->type == $type) {
                return true;
            }
        }
        return false;
    }
    /**
     * Return the highest ranked property with the given type
     *
     * Part of the ArrayAccess interface
     *
     * @param mixed $offset Property type to check for
     *
     * @return mixed Property value or NULL if empty
     */
    public function offsetGet($offset)
    {
        foreach ($this->properties as $prop) {
            if ($prop->type == $type) {
                return $prop->value;
            }
        }
        return null;
    }
    /**
     * Not implemented.
     *
     * Part of the ArrayAccess interface
     *
     * @param mixed $offset  Property type to check for
     * @param mixed $value New property value
     *
     * @return void
     *
     * @throws LogicException Always
     */
    public function offsetSet($offset, $value) : void
    {
        throw new LogicException('Changing properties not implemented');
    }

    /**
     * Not implemented.
     *
     * Part of the ArrayAccess interface
     *
     * @param mixed $offset Property type to check for
     *
     * @return void
     *
     * @throws LogicException Always
     */
    public function offsetUnset($offset)
    {
        throw new LogicException('Changing properties not implemented');
    }

    /**
     * Get all properties with the given type
     *
     * @param mixed $type Property type to filter by
     *
     * @return array Array of XML_XRD_Element_Property objects
     */
    public function getProperties($type = null)
    {
        if ($type === null) {
            return $this->properties;
        }
        $properties = array();
        foreach ($this->properties as $prop) {
            if ($prop->type == $type) {
                $properties[] = $prop;
            }
        }
        return $properties;
//        return array_filter($this->properties, function ($item) use ($type) { return $item->type == $type; });
    }
}