<?php
namespace KungFu\Cms\Controller;
use Templix\Templix;
use Templix\Toolbox as Templix_Toolbox;
use KungFu\Cms\Controller\Templix as Controller_Templix;
class L10n extends Controller_Templix{
	protected $Dispatcher;
	protected $Route;
	protected $path;
	protected $params;
	protected $Templix;
	function __construct($Dispatcher=null){
		$this->Dispatcher = $Dispatcher;
	}
	function __invoke($params,$path,$Route){
		$this->Route = $Route;
		$this->path = $path;
		$this->params = $params;
		
		$lang = $this->Route->getLang();
		$langMap = $this->Route->getLangMap();
		
		$this->Templix()->setDirCompileSuffix('.'.$lang.'/');
		$this->Templix()->onCompile(function($TML)use($lang,$path,$langMap){
			$Toolbox = new Templix_Toolbox();
			$Toolbox->i18nGettext($TML);
			$Toolbox->i18nRel($TML,$lang,$path,$langMap);
			if($langMap){
				foreach($TML('a[href]') as $a){
					if(strpos($a->href,'://')===false&&strpos($a->href,'javascript:')!==0&&strpos($a->href,'#')!==0){
						if(($k=array_search($a->href,$langMap))!==false)
							$a->href = $k;
					}
				}
			}
		});
		return $this->Templix();
		//return $this->Templix()->display($params[0]);
	}
	function Templix(){
		return $this->Templix?:$this->Templix = new Templix();
	}
}