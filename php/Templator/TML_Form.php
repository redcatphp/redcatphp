<?php namespace Surikat\Templator;
use Surikat\Templator\CDATA;
use Surikat\Model;
use Surikat\Model\R;
class TML_Form extends TML {
	function loadModelRules($model){
		$this->__unset('modelRules');
		if($this->Template->present)
			$model = $this->presentProperty($model);
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
			if(@$_POST[\'type\']!=\''.$type.'\') return;			
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
							$code .= ' $_POST[\''.$n.'\'] = "'.str_replace('"','\\"',$el->value).'"; ';
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
							$code .= ' $_POST[\''.$n.'\'] = Validation\\Filter::'.$b.'($_POST[\''.$n.'\'],'.($v?','.var_export($v,true):'').'); ';
							$el->$k = null;
						break;
						case 'ruler':
							$code .= ' if(($r=Validation\\Ruler::'.$b.'($_POST[\''.$n.'\']'.($v?','.var_export($v,true):'').'))!==true) return $r; ';
							$el->$k = null;
						break;
					}
				}
			}
		}
		$code .= 'return R::create($_POST);};if(!empty($_POST))$form_'.$type.'=$'.$fid.'();?>';
		array_unshift($this->childNodes,$code);
	}
	*/
}