<?php
namespace surikat\view\CssSelector\Parser;
class CssParserHelper{    
    /**
     * Searches a node in a list.
     * 
     * This function may return false, if the node was not found.
     * 
     * @param DOMNode $node   DOMNode object
     * @param array   $items  List of DOMNode objects
     * @param integer $offset Offset (default is 0)
     * 
     * @return false|integer
     */
    public static function searchNode($node, $items, $offset = 0){
        $len = count($items);
        for ($i = $offset; $i < $len; $i++) {
            $item = $items[$i];
            if ($item->isSameNode($node))
                return $i;
        }
        return false;
    }
    
    /**
     * Merges two lists of nodes in a single list.
     * 
     * This function merges two list of nodes in a single list without repeating
     * nodes.
     * 
     * @param array $items1 List of DOMNode objects
     * @param array $items2 List of DOMNode objects
     * 
     * @return array of DOMNode objects
     */
    public static function mergeNodes($items1, $items2)
    {
        $ret = [];
        $items = array_merge($items1, $items2);
        $len = count($items);
        
        for ($i = 0; $i < $len; $i++) {
            $item = $items[$i];
            $position = CssParserHelper::searchNode($item, $items, $i + 1);
            if ($position === false) {
                array_push($ret, $item);
            }
        }
        return $ret;
    }
    
    /**
     * Gets nodes from a CSS expression.
     * 
     * This function filters all nodes that satisfy a CSS expression.
     * 
     * @param DOMNode $node  DOMNode object
     * @param string  $query CSS selector expression.
     * 
     * @return array of DOMElement objects
     */
    public static function select($node, $query){
        $nodes = [];
        $p = new CssParser($node, $query);
        $nodes = $p->parse();
        return $nodes;
    }
}
