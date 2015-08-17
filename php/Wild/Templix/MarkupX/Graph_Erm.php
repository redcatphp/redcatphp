<?php namespace Wild\Templix\MarkupX; 
class Graph_Erm extends \Wild\Templix\Markup{
	function load(){
		foreach($this->children('table') as $t){
			$t->remapAttr('name');
			foreach($t->children('col') as $c)
				$c->remapAttr('name');
		}
		foreach($this->children('link') as $l){
			$l->remapAttr('from');
			$l->remapAttr('to',1);
			$l->remapAttr('relation',2);
		}
	}	
}