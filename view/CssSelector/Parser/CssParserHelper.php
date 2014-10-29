<?php
namespace surikat\view\CssSelector\Parser;
class CssParserHelper{    
    public static function select($node, $query){
        $p = new CssParser($node, $query);
        return $p->parse();
    }
}