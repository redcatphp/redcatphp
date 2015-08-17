<?php
namespace KungFu\TemplixPlugin\Markup;
class Video extends \Wild\Templix\Markup{
	function load(){
		if($this->source){
			$this->prepend('
				<source src="'.$this->source.'.mp4" type="video/mp4" />
				<source src="'.$this->source.'.webm" type="video/webm" />
				<source src="'.$this->source.'.ogv" type="video/ogg" />
			');
			unset($this->source);
		}
	}
}