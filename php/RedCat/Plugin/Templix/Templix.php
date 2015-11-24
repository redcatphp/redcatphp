<?php
namespace RedCat\Plugin\Templix;
use RedCat\Wire\Di;
class Templix extends \RedCat\Templix\Templix{
	private $di;
	
	public $httpMtime;
	public $httpExpireTime;
	public $httpEtag;
	public $cleanDir = '.tmp/min/';
	
	protected $toolbox;
	
	function __construct($file=null,$vars=null,
		$devTemplate=true,$devJs=true,$devCss=true,$devImg=false,
		Di $di,
		$httpMtime=false,$httpEtag=false,$httpExpireTime=false
	){
		parent::__construct($file,$vars,$devTemplate,$devJs,$devCss,$devImg);
		$this->di = $di;
		
		$this->addDirCwd([
			'template/',
			'redcat/template/',
		]);
		
		$this->setDirCompile('.tmp/templix/compile/');
		$this->setDirCache('.tmp/templix/cache/');
		$this->setDirSync('.tmp/sync/');
		
		$this->httpMtime = $httpMtime;
		$this->httpEtag = $httpEtag;
		$this->httpExpireTime = $httpExpireTime;
		
		$this->toolbox = $this->di->create(__NAMESPACE__.'\Toolbox');
		
		$this->onCompile(function($tml){
			if($tml->templix->getParent())
				return;
			$this->toolbox->is($tml);
			if(!$tml->devTemplate)
				$this->toolbox->autoMIN($tml);
		});
		$this->setCleanCallback([$this,'cleanMin']);
	}
	function cleanMin(){
		self::rmdir($this->cleanDir);
	}
	function setHttpMtime($mtime){
		$this->httpMtime = $mtime;
	}
	function setHttpExpireTime($time){
		$this->httpExpireTime = $time;
	}
	function setHttpEtag($etag){
		$this->httpEtag = $etag;
	}
	function query($path=null,$vars=[]){
		$this['URI'] = $path;
		
		if(isset($this->di['versioning'])){
			$this['VERSIONING'] = (string)$this->di->objectify($this->di['versioning']);
			if($this['VERSIONING'])
				$this['VERSIONING'] = '.up'.$this['VERSIONING'];
		}
		else{
			$this['VERSIONING'] = '';
		}
		
		if(!in_array(pathinfo($path,PATHINFO_EXTENSION),['tml','atml']))
			$path .= '.tml';
		
		if($this->setPath($path)||$this->setPath('404.tml')){
			$this->displayC($vars);
		}
		else{
			http_response_code(404);
		}
	}
	function displayC($vars){
		if($this->httpMtime){
			if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])&&@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])>=$this->httpMtime){
				http_response_code(304);
				header('Connection: close');
				exit;
			}
			header('Last-Modified: '.gmdate('D, d M Y H:i:s', $this->httpMtime).' GMT');
		}
		if($this->httpEtag){
			if($this->httpEtag===true){
				ob_start();
				$this->display(null,$vars);
				$buffer = ob_get_clean();
				$etag = sha1($buffer);
			}
			else{
				$this->display(null,$vars);
			}
			if(isset($_SERVER['HTTP_IF_NONE_MATCH'])){
				$etagH = trim($_SERVER['HTTP_IF_NONE_MATCH'],'"');
				if(substr($etagH,-5)=='-gzip')
					$etagH = substr($etagH,0,-5);
				if($etagH==$etag){
					http_response_code(304);
					header('Connection: close');
					exit;
				}
			}
			header('Etag: "'.$etag.'"');
		}
		else{
			$this->display(null,$vars);
		}
		if(is_integer($this->httpExpireTime)){
			header('Cache-Control: max-age=' . $this->httpExpireTime);
			header('Expires: '.gmdate('D, d M Y H:i:s', time()+$this->httpExpireTime).' GMT');
		}
		if(isset($buffer))
			print $buffer;			
	}
	function __invoke($file){
		if(is_array($file)){
			list($hook,$file) = (array)$file;
			if(substr($hook,0,8)=='redcat/')
				$hook = substr($hook,8);
			$this->setDirCwd([$hook.'/','redcat/'.$hook.'/']);
		}
		return $this->query($file);
	}
}