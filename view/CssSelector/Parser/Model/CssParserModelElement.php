<?php
namespace surikat\view\CssSelector\Parser\Model;
use surikat\view\CssSelector\Parser\Filter\CssParserFilter;
class CssParserModelElement{
    private $_tagName;
    private $_filters;
    public function __construct($tagName){
        $this->_filters = [];
        $this->_tagName = $tagName;
    }
    public function getTagName(){
        return $this->_tagName;
    }
    public function getFilters(){
        return $this->_filters;
    }
    public function addFilter($filter){
        array_push($this->_filters, $filter);
    }
    public function match($node){
        return $this->_tagName == "*" || $node->nodeName == $this->_tagName;
    }
}