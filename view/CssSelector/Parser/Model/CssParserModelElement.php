<?php
/**
 * This file contains the CssParserModelElement class.
 * 
 * PHP Version 5.3
 * 
 * @category XML_CSS
 * @package  XML_CSS_CssSelector
 * @author   Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license  https://raw2.github.com/soloproyectos/php.common-libs/master/LICENSE BSD 2-Clause License
 * @link     https://github.com/soloproyectos/php.common-libs
 */
namespace surikat\view\CssSelector\Parser\Model;

use surikat\view\CssSelector\Parser\Filter\CssParserFilter;

/**
 * Class CssParserModelElement.
 * 
 * This class represents an element in a CSS expression.
 * 
 * @category XML_CSS
 * @package  XML_CSS_CssSelector
 * @author   Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license  https://raw2.github.com/soloproyectos/php.common-libs/master/LICENSE BSD 2-Clause License
 * @link     https://github.com/soloproyectos/php.common-libs
 */
class CssParserModelElement
{
    /**
     * Tagname.
     * @var string
     */
    private $_tagName;
    
    /**
     * List of filters.
     * @var array of CssParserFilter objects
     */
    private $_filters;
    
    /**
     * Constructor.
     * 
     * @param string $tagName Tagname
     */
    public function __construct($tagName)
    {
        $this->_filters = array();
        $this->_tagName = $tagName;
    }
    
    /**
     * Gets the tagname.
     * 
     * @return string
     */
    public function getTagName()
    {
        return $this->_tagName;
    }
    
    /**
     * Gets the filters.
     * 
     * @return array of CssParserFilter
     */
    public function getFilters()
    {
        return $this->_filters;
    }
    
    /**
     * Adds a filter.
     * 
     * @param CssParserFilter $filter Filter object
     * 
     * @return void
     */
    public function addFilter($filter)
    {
        array_push($this->_filters, $filter);
    }
    
    /**
     * Does the node match?
     * 
     * @param DOMElement $node DOMElement object
     * 
     * @return boolean
     */
    public function match($node)
    {
        return $this->_tagName == "*" || $node->nodeName == $this->_tagName;
    }
}
