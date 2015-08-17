<?php
namespace Wild\DataMap\Helper;
class Pagination{
	var $start;
	var $end;
	var $max;
	var $count;
	var $limit;
	var $offset;
	var $page;
	var $maxCols;
	var $href;
	var $prefix;
	var $pagesTotal;
	function __construct($config=null){
		if($config){
			foreach($config as $k=>$v){
				$this->{'set'.ucfirst($k)}($v);
			}
		}
	}
	function setLimit($limit){
		$this->limit = $limit;
	}
	function setMaxCols($maxCols){
		$this->maxCols = $maxCols;
	}
	function setHref($href){
		$this->href = $href;
	}
	function setPrefix($prefix){
		$this->prefix = $prefix;
	}
	function setCount($count){
		$this->count = $count;
	}
	function setPage($page){
		$this->page = $page;
		$this->offset = $this->page?($this->page-1)*$this->limit:0;
	}
	function resolve(){
		if(!$this->page){
			$this->page = 1;
		}
		elseif(
			!is_integer(filter_var($this->page,FILTER_VALIDATE_INT))
			||($this->page=(int)$this->page)<2
			||$this->count<=$this->offset
		){
			return false;
		}
		$this->pagesTotal = (int)ceil($this->count/$this->limit);
		if($this->maxCols>$this->pagesTotal)
			$this->max = $this->pagesTotal-1;
		else
			$this->max = $this->maxCols-1;
		$this->start = $this->page-(int)floor($this->max/2);
		if($this->start<=0)
			$this->start = 1;
		$this->end = ($this->start+$this->max)>$this->pagesTotal?$this->pagesTotal:$this->start+$this->max;
		if($this->end-$this->start<$this->max)
			$this->start = $this->end-$this->max;
		return true;
	}
}