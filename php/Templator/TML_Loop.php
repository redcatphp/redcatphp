<?php namespace Surikat\Templator; 
class TML_Loop extends TML{
	protected $hiddenWrap = true;
	protected $loop = [];
	function loaded(){
		foreach($this->metaAttribution as $k=>$v){
			$sp = ($pos=strpos($k,'-'))?substr($k,0,$pos):$k;
			switch($sp){
				case 'key':
					
				break;
				case 'val':
				case 'assign':
					
				break;
				case 'm':
					$this->loop['m']['method'] = substr($k,$pos+1);
					$this->loop['m']['table'] = $v;
				break;
				case 'compoParams':
					$this->loop['m']['compoParams'] = $v;
				break;
				case 'compo':
					$ck = substr($k,$pos+1);
					$this->loop['m']['compo'][$ck] = $v;
				break;
				default:
					$this->loop[$k] = $v;
				break;
			}

		}
		$assign = $this->attrFinder('val','assign');
		$key = $this->attrFinder('key');
		foreach($this->loop as $k=>$params){
			$method = 'method'.ucfirst($k);
			$this->innerHead('<?php
				foreach('.$this->$method($params).' as $'.$key.'=>$'.$assign.'){
					'.($this->extract?'is_array($'.$assign.')&&extract($'.$assign.',EXTR_REFS|EXTR_OVERWRITE|EXTR_PREFIX_ALL,"'.$this->extract.'");':'').'
				?>');
			$this->innerFoot('<?php } ?>');
		}
	}
	private function methodE($e){
		return $e;
	}
	private function methodVar($var){
		if(strpos($var,',')){
			$var = explode(',',$var);
			return (($c=count($var>1))?'array_merge(':'').'($'.implode(',$',$var).($c?')':'');
		}
		else
			return '$'.$var;
	}
	//private function methodModel($params){
		//if(isset($this->cacheSync)&&!trim($this->cacheSync))
			//$this->cacheSync = $params['table'].'.db';
		//return '(array)model::'.$params['method'].'('.self::exportVars($params['table'],@$params['compo'],explode(',',@$params['compoParams'])).')';
	//}
	private function methodFile($file){
		$method = 'methodFile'.(substr($file,-1)==='/'?'Dir':ucfirst(strtolower(pathinfo($file,PATHINFO_EXTENSION))));
		return $this->$method($file);
	}
	private function methodFileDir($file){
		return "glob($file.'*')";
	}
	private function methodFileJson($file){
		return 'Surikat\\K\\JSON::decode(file_get_contents($file),true)';
	}
	private function methodFileSvar($file){
		return "unserialize(file_get_contents('$file'))";
	}
	private function methodFilePhp($file){
		return "include('$file')";
	}
	private function methodFileTxt($file){
		return "file('$file')";
	}
	private function methodFileIni($file){
		return "parse_ini_file('$file',true)";
	}
	private function methodFileTml($file){
		return "new Templator\\TML(file_get_contents('$file'))->childNodes";
	}
	// function onExec(){
		// var_dump($this('a',0)->href);
		// return $this;
	// }
}