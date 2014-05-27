<?php
/**
 * This file contains the CssParserFilterId class.
 * 
 * PHP Version 5.3
 * 
 * @category XML_CSS
 * @package  XML_CSS_CssSelector
 * @author   Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license  https://raw2.github.com/soloproyectos/php.common-libs/master/LICENSE BSD 2-Clause License
 * @link     https://github.com/soloproyectos/php.common-libs
 */
namespace surikat\view\CssSelector\Parser\Filter;

use surikat\view\CssSelector\Parser\Filter\CssParserFilter;

/**
 * Class CssParserFilterId.
 * 
 * This class represents the id filter.
 * 
 * @category XML_CSS
 * @package  XML_CSS_CssSelector
 * @author   Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license  https://raw2.github.com/soloproyectos/php.common-libs/master/LICENSE BSD 2-Clause License
 * @link     https://github.com/soloproyectos/php.common-libs
 */
class CssParserFilterId extends CssParserFilter
{
    /**
     * Identifier.
     * @var string
     */
    private $_id;
    
    /**
     * Constructor.
     * 
     * @param string $id CSS Identifier
     */
    public function __construct($id)
    {
        $this->_id = $id;
    }
    
    /**
     * Does the node match?
     * 
     * @param DOMElement $node     DOMElement object
     * @param integer    $position Node position
     * @param array      $items    List of nodes
     * 
     * @return boolean
     */
    public function match($node, $position, $items)
    {
        return trim($node->getAttribute("id")) == $this->_id;
    }
}
