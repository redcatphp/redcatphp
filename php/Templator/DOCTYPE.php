<?php namespace Surikat\Templator;
use Surikat\Templator\TML;
class DOCTYPE extends CORE{
	protected $selfClosed = true;
	protected $hiddenWrap = true;
	private $contentText = '';
	private $__compat;
	var $nodeName = 'DOCTYPE';
	function __construct($parent,$nodeName,$text,$constructor){
		$this->parent = $parent;
		$this->View = $parent->View;
		$text = self::phpImplode($text,$constructor);
		$this->contentText = $text;
		$this->__compat = substr($this->contentText,10,-1);
		if($this->__compat!='html'&&$this->View)
			$this->View->isXhtml = true;
	}
	function getInner(){
		return $this->contentText;
	}
	function loaded(){
		if($html=$this->closest()->children('html',0)){
			if($this->__compat=='xhtml'){
				$html->metaAttribution[] = '<?php if(stristr(@$_SERVER["HTTP_ACCEPT"],"application/xhtml+xml")) echo \'xmlns="http://www.w3.org/1999/xhtml"\';?>';
			}
			elseif($this->__compat=='xml'||$this->__compat!='html')
				$html->xmlns = "http://www.w3.org/1999/xhtml";
		}
		$xml = 'echo \'<?xml version="1.0" encoding="utf-8"?>\';';
		switch($this->__compat){
			case 'xhtml5':
			case 'xhtml':
				$str = '<?php
				if(stristr(@$_SERVER["HTTP_ACCEPT"],"application/xhtml+xml")){
					header("Content-Type: application/xhtml+xml; charset=utf-8");
					'.$xml.'
				}
				else{
					header("Content-Type: text/html; charset=utf-8");
					?><!DOCTYPE html><?php
				}
					
				?>';
			break;
			case 'html5':
			case 'html':
				$str = '<!DOCTYPE html>';
			break;
			default:
			case 'xml':
				$str = '<?php header("Content-Type: application/xhtml+xml; charset=UTF-8"); '.$xml.'?>';
			break;
		}
		$this->contentText = $str;
	}
}
