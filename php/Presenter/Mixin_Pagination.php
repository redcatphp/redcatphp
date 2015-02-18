<?php namespace Surikat\Presenter;
use Surikat\Vars\ArrayObject;
use Surikat\Exception\View as ViewException;
trait Mixin_Pagination{
	protected $limit				= 5;
	protected $offset	    		= 0;
	protected function pagination(){
		$this->pagination = new ArrayObject([
			'prefix'			=>'+page:',
			'maxCols'			=>3,
		]);
		if($this->limitation)
			$this->limit = $this->limitation;
		if($this->page===null)
			$this->page = 1;
		elseif(
			!is_integer(filter_var($this->page,FILTER_VALIDATE_INT))
			||($this->page=(int)$this->page)<2
			||$this->count<=($this->offset=($this->page-1)*$this->limit)
		)
			throw new ViewException('404');
		
		if(($this->offset+$this->limit)>$this->count)
			$this->pagination->end = $this->count;
		else
			$this->pagination->end = $this->offset+$this->limit;
		
		$this->pagination->pagesTotal = (int)ceil($this->count/$this->limit);
		
		if($this->pagination->maxCols>$this->pagination->pagesTotal)
			$this->pagination->max = $this->pagination->pagesTotal-1;
		else
			$this->pagination->max = $this->pagination->maxCols-1;
			
		$this->pagination->start = $this->page-(int)floor($this->pagination->max/2);
		if(!$this->pagination->start)
			$this->pagination->start = 1;
		$this->pagination->end = ($this->pagination->start+$this->pagination->max)>$this->pagination->pagesTotal?$this->pagination->pagesTotal:$this->pagination->start+$this->pagination->max;
		if($this->pagination->end-$this->pagination->start<$this->pagination->max)
			$this->pagination->start = $this->pagination->end-$this->pagination->max;
	}
}