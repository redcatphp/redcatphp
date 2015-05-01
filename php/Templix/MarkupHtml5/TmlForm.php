<?php namespace Templix\MarkupHtml5;
use Templix\CDATA;
use Database;
use Database\R;
class TmlForm extends \Templix\Tml {
	function loadModelRules($model){
		$this->__unset('modelRules');
		//if($this->Template->present)
			//$model = $this->presentProperty($model);
		$model = trim($model);
		$c = R::getModelClass($model);
		//var_dump($c);exit;
		//$this->children('indput[name="titre"]')->attr('minlenght',$c::getColumnDef('titre','rule','minlenght'))
	}
	/* too much experimental for moment
	function loadAutoCRUD(){
		$this->__unset('autoCRUD'):
		$type = $this('input[name=type]',0);
		if(!$type||($this->previousSibling instanceof CDATA&&strpos("{$this->previousSibling}",'$form_'.$type.'=$crud')===false))
			return;
		$type = $type->value;
		$fid = 'crud'.md5(serialize($this));
		$code = '<?php $'.$fid.' = function(){
			if(@$this->Http_Post()->type]!=\''.$type.'\') return;
		';
		foreach($this('input,select,textarea') as $el){
			if(!$this->name||$this->name=='type')
				continue;
			$n = $el->name;
			if($el->maxlength)
				$el->{'ruler-maxlength'} = $el->maxlength;
			if($el->minlength&&($el->{'ruler-minlength'}=$el->minlength))
				$el->minlength = null;
			if($el->required)
				$el->{'ruler-required'} = true;
			$ruletype = 'ruler-'.$el->type;
			switch($el->nodeName){
				case 'input':
					switch($el->type){
						case 'hidden':
							$code .= ' $this->Http_Post()->'.$n.' = "'.str_replace('"','\\"',$el->value).'"; ';
						break;
						case 'file':
							if($el->multiple){
								
							}
							else{
							
							}
						break;
						case 'text':
							$el->$ruletype = true;;
						break;
						case 'url':
							$el->$ruletype = true;
						break;
						case 'email':
							$el->$ruletype = true;
						break;
						case 'tel':
							$el->$ruletype = true;
						break;
						case 'date':
							$el->$ruletype = true;
							$el->type = 'text';
						break;
						case 'time':
							$el->$ruletype = true;
							$el->type = 'text';
						break;
						case 'datetime':
							$el->$ruletype = true;
						break;
						case 'datetime-local':
							$el->{'datetimeLocal'} = true;
						break;
						case 'month':
							$el->$ruletype = true;
						break;
						case 'week':
							$el->$ruletype = true;
						break;
						case 'time':
							$el->$ruletype = true;
						break;
						case 'number':
							$el->$ruletype = true;
						break;
						case 'checkbox':
							$el->$ruletype = true;
						break;
						case 'radio':
							$el->$ruletype = true;
						break;
						case 'search':
							$el->$ruletype = true;
						break;
						default:
							$el->$ruletype = true;
							$el->type = 'text';
						break;
					}
				break;
				case 'select':
					
				break;
				case 'textarea':
					$el->{'ruler-textarea'} = true;
				break;
			}
			foreach($el->attributes as $k=>$v){
				if(($pos=strpos($k,'-'))!==false){
					$a = substr($k,0,$pos);
					$b = substr($k,$pos+1);
					if(empty($b))
						continue;
					switch($a){
						case 'filter':
							$code .= ' $this->Http_Post()->'.$n.' = Validation\\Filter::'.$b.'($this->Http_Post()->'.$n.','.($v?','.var_export($v,true):'').'); ';
							$el->$k = null;
						break;
						case 'ruler':
							$code .= ' if(($r=Validation\\Ruler::'.$b.'($this->Http_Post()->'.$n.($v?','.var_export($v,true):'').'))!==true) return $r; ';
							$el->$k = null;
						break;
					}
				}
			}
		}
		$code .= 'return R::create($this->Http_Post());};if(count($this->Http_Post()))$form_'.$type.'=$'.$fid.'();?>';
		array_unshift($this->childNodes,$code);
	}
	*/
}