<?php


namespace AssembleeVirtuelle\ManaBundle\XRD\Element;


use AssembleeVirtuelle\ManaBundle\XRD\PropertyAccess;

final class Link extends PropertyAccess
{
    /**
     * Link relation
     *
     * @var string
     */
    private $rel;
    /**
     * Link type (MIME type)
     *
     * @var string
     */
    private $type;
    /**
     * Link URL
     *
     * @var string
     */
    private $href;
    /**
     * Link URL template
     *
     * @var string
     */
    private $template;
    /**
     * Array of key-value pairs: Key is the language, value the title
     *
     * @var array
     */
    public $titles = array();

    /**
     * Create a new instance and load data from the XML element
     *
     * @param string  $rel        string with the relation name/URL
     * @param string  $href       HREF value
     * @param string  $type       Type value
     * @param boolean $isTemplate When set to true, the $href is
     *                            used as template
     */
    public function __construct($rel = null, $href = null, $type = null, $isTemplate = false)
    {
        $this->rel = $rel;
        if ($isTemplate) {
            $this->template = $href;
        } else {
            $this->href = $href;
        }
        $this->type = $type;
    }

    /**
     * Returns the title of the link in the given language.
     * If the language is not available, the first title without the language
     * is returned. If no such one exists, the first title is returned.
     *
     * @param string $lang 2-letter language name
     *
     * @return string|null Link title
     */
    public function getTitle($lang = null)
    {
        if (count($this->titles) == 0) {
            return null;
        }
        if ($lang == null) {
            return reset($this->titles);
        }
        if (isset($this->titles[$lang])) {
            return $this->titles[$lang];
        }
        if (isset($this->titles[''])) {
            return $this->titles[''];
        }
        //return first
        return reset($this->titles);
    }

    public function __get($key)
    {
        if (in_array($key, ['rel', 'type', 'href', 'template'])) {
            return $this->$key;
        }
    }

    public function __set($key, $value)
    {
        if (in_array($key, ['rel', 'type', 'href', 'template'])) {
            $this->$key = $value;
        }
    }
}