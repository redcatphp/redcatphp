<?php namespace surikat\view; 
class TML_Graph_erm extends TML{
	function load(){
		foreach($this->find('table') as $t){
			$t->remapAttr('name');
			foreach($t->find('col') as $c)
				$c->remapAttr('name');
		}
		foreach($this->find('link') as $l){
			$l->remapAttr('from');
			$l->remapAttr('to',1);
		}
	}
	
}