<?php
/**
 * This file contains the CssParserFilterPseudoEven class.
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
 * Class CssParserFilterPseudoEven.
 * 
 * This class represents the first-child pseudo filter.
 * 
 * @category XML_CSS
 * @package  XML_CSS_CssSelector
 * @author   Gonzalo Chumillas <gonzalo@soloproyectos.com>
 * @license  https://raw2.github.com/soloproyectos/php.common-libs/master/LICENSE BSD 2-Clause License
 * @link     https://github.com/soloproyectos/php.common-libs
 */
class CssParserFilterPseudoEven extends CssParserFilterPseudo
{
    
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
        return $position % 2 == 0;
    }
}
