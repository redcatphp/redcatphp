<?php
/**
 * This file contains the CssParserFilterPseudoLt class.
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

use surikat\view\CssSelector\Parser\Filter\CssParserFilterPseudo;

/**
 * Class CssParserFilterPseudoLt.
 * 
 * This class represents the first-child pseudo filter.
 * 
 * @category XML_CSS
 * @package  XML_CSS_CssSelector
 * @author   Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license  https://raw2.github.com/soloproyectos/php.common-libs/master/LICENSE BSD 2-Clause License
 * @link     https://github.com/soloproyectos/php.common-libs
 */
class CssParserFilterPseudoLt extends CssParserFilterPseudo
{
    
    /**
     * Sibling position.
     * @var integer
     */
    private $_position;
    
    /**
     * Constructor.
     * 
     * @param string $input String input
     */
    public function __construct($input)
    {
        $this->_position = intval($input);
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
        return $position < $this->_position;
    }
}
