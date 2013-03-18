<?php

class Node
{
    //public static final Pattern VALID = Pattern.compile("[a-zA-Z][\\w]*");
    public static $VALID = "[a-zA-Z][\\w]*";

    private $name;

    public function __construct($_name = null)
    {
        /*Matcher m = VALID.matcher(_name);
          if (!m.find())
          {
              throw new IllegalArgumentException("Invalid character in Node name: " + _name);
          }*/
        $this->name = $_name;
    }

    /**
     * @var array Children of this Node
     */
    private $children = array();

    /**
     * @var array Attributes of this node
     */
    private $attributes = array();

    /**
     * @param $name The name of the attribute
     * @param $value The value of the attribute
     * @return bool Returns true on success
     */
    public function addAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
        return true;
    }

    /**
     * @param String $childName The name of the child
     * @return Node Returns the Node of the child
     */
    public function createChild(String $childName)
    {
        $child = new Node($childName);
        $this->children[] = $child;
        return $child;
    }

    /**
     * @param String $childName Returns the Node for the given childName.  Will create the child if it doesn't exist.
     * @return Node The requested child.
     */
    public function getChild(String $childName) {
        if (!isset($this->children[$childName])) {
            return createChild($childName);
        }
        return $this->children[$childName];
    }

    /**
     * @param int $level Assembles the string value for this node, its attributes, and all child nodes
     * @return string The assembled string value
     */
    public function toString($level = 0)
    {
        $retVal = "";
        if ($this->name != null) {
            for ($a = 0; $a < $level; $a++)
            {
                $retVal .= "\t";
            }
            $retVal .= '<';
            $retVal .= $this->name;

            foreach ($this->attributes as $key => $value)
            {
                $retVal .= ' ';
                $retVal .= $key;
                $retVal .= "=\"";
                $retVal .= $this->cleanUp($value);
                $retVal .= "\"";
            }

            if (sizeof($this->children) == 0) {
                $retVal .= " />\n";
            }
            else
            {
                $retVal .= ">\n";
                foreach ($this->children as $child)
                {
                    $retVal .= $child->toString($level + 1);
                }
                for ($a = 0; $a < $level; $a++)
                {
                    $retVal .= "\t";
                }
                $retVal .= "</";
                $retVal .= $this->name;
                $retVal .= ">\n";
            }
        }
        else
        {
            foreach ($this->children as $child)
            {
                $retVal .= $child->toString($level);
            }
        }
        return $retVal;
    }

    private function cleanUp($attributeValue)
    {
        return $attributeValue;
        /*if (attributeValue == null) return null;
       String value = attributeValue.toString();
       value = value.replaceAll("\"", "\\\"").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll("&", "&amp;");
       return value;*/
    }
}
