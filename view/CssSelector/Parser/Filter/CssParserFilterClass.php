<?php
namespace surikat\view\CssSelector\Parser\Filter;
use surikat\view\CssSelector\Parser\Filter\CssParserFilter;
class CssParserFilterClass extends CssParserFilter{
    private $_className;
    public function __construct($className){
        $this->_className = $className;
    }
    private function _isClassInList($class, $classes){
        $items = explode(" ", trim($classes));
        if (count($items) > 0) {
            foreach ($items as $item) {
                if (strcasecmp($class, trim($item)) == 0) {
                    return true;
                }
            }
        }
        return false;
    }
    public function match($node, $position, $items){
        return $this->_isClassInList($this->_className,$node->getAttribute("class"));
    }
}