<?php
namespace surikat\view\CssSelector\Parser\Filter;
use surikat\view\CssSelector\Parser\Filter\CssParserFilter;
class CssParserFilterId extends CssParserFilter{
    private $_id;
    public function __construct($id){
        $this->_id = $id;
    }
    public function match($node, $position, $items){
        return trim($node->getAttribute("id")) == $this->_id;
    }
}