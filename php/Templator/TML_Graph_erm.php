<?php namespace Surikat\Templator; 
class TML_Graph_erm extends TML{
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