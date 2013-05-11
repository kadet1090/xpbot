<?php
namespace XPBot\System\Utils;

class XmlBranch
{
    const XML = '<?xml version="1.0" encoding="utf-8"?>';

    public $tag;
    public $attributes = array();
    public $content = array();

    public function __construct($tag)
    {
        $this->tag = $tag;
    }

    public function addAttribute($name, $value)
    {
        $this->attributes[$name] = htmlspecialchars(trim($value));

        return $this;
    }

    public function addChild(xmlBranch $child)
    {
        if (!isset($this->content[$child->tag])) $this->content[$child->tag] = array();

        $this->content[$child->tag][] = $child;

        return $child;
    }

    public function setContent($content)
    {
        $this->content = htmlspecialchars(trim($content));
    }

    public function asXML()
    {
        $xml = '<' . $this->tag . '';
        foreach ($this->attributes as $argument => $value)
            $xml .= ' ' . htmlspecialchars($argument) . '="' . htmlspecialchars($value) . '"';
        if (empty($this->content)) {
            $xml .= '/>';
        } else {
            $xml .= '>';
            if (is_array($this->content))
                foreach ($this->content as $branches)
                    foreach ($branches as $branch)
                        $xml .= $branch;
            else
                $xml .= $this->content;

            $xml .= '</' . $this->tag . '>';
        }

        return $xml;
    }

    /**
     * @param string $name
     * @return XmlBranch|null
     */
    public function __get($name)
    {
        if (is_array($this->content))
            return $this->content[$name];
        else return null;
    }

    public function __toString()
    {
        return $this->asXML();
    }
}

?>
