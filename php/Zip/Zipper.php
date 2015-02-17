<?php namespace Surikat\Zip;
class Zipper extends \ZipArchive {   
	public $forbidden_dirs = [];
	public $forbidden_files = [];
	public $liste = [];
	public $total_files = 0;
	public $total_size = 0;
	public $limit_files = 0;
	public $limit_size = 0;
	public $file = '';
	public $DIR = null;
	public static function bigBuilder($DIR,$params){
		$ZIP = new Zipper();
		return $ZIP->bigDir($DIR,$params);
	}
	public function bigDir($DIR,$params){
		@session_start();
		$tmpdir = sys_get_temp_dir();
		$this->DIR = $DIR;
		foreach($params as $k=>$v){
			if(isset($this->$k)){
				$this->$k = $v;
			}
		}
		$this->file = isset($_SESSION['builder_zip'])&&is_file($_SESSION['builder_zip'])?$_SESSION['builder_zip']:$_SESSION['builder_zip']=tempnam($tmpdir, 'surikatbuild');
		$SESSFILE = $tmpdir.'/surikatbuild.'.session_id();
		if($this->open($this->file)!==TRUE){
			die ("Could not open archive");
		}
		if(is_file($SESSFILE)){
			$this->liste = unserialize(file_get_contents($SESSFILE));
		}
		else{
			$this->addDirToList($DIR);
		}
		$r = $this->processList();
		if($r){
			$_SESSION['builder_zip'] = null;
			unset($_SESSION['builder_zip']);
			unlink($SESSFILE);
			return $this->file;
		}
		else{
			file_put_contents($SESSFILE,serialize($this->liste));
			return false;
		}
	}
	public function incremental_size($file){
		$this->total_size += filesize($file);
		return $this->total_size>=$this->limit_size;
	}
	public function processList(){
		foreach(array_keys($this->liste) as $k){
			if(($this->limit_files&&$this->total_files>=$this->limit_files)||($this->limit_size&&$this->incremental_size($this->liste[$k]))){
				$this->close();
				return false;
			}
			$this->addFile($this->liste[$k],$k);
			unset($this->liste[$k]);
			$this->total_files += 1;
		}
		return true;
	}
	public function addDirToList($path){
		foreach(glob(rtrim($path,'/'). '/*') as $node){
			$localname = $this->DIR?mb_substr($node,mb_strlen($this->DIR)):$node;
			if(is_dir($node)&&!is_link($node)){
				if(!in_array($localname,$this->forbidden_dirs)&&!in_array($node,$this->forbidden_dirs)){
					$this->addDirToList($node);
				}
			}
			else if(is_file($node)){
				if(!in_array($localname,$this->forbidden_files)&&!in_array($node,$this->forbidden_files)){
					$this->liste[$localname] = $node;
				}
			}
		}
	}
	
	public function addDir($path){
		$this->addEmptyDir($path);
		$nodes = glob($path . '/*');
		foreach($nodes as $node){
			if(is_dir($node)){
				$this->addDir($node);
			}
			else if(is_file($node)){
				$localname = $this->DIR?mb_substr($node,mb_strlen($this->DIR)):$node;
				$this->addFile($node,$localname);
			}
		}
	}
	
	public function processDir($path){
		foreach(glob(rtrim($path,'/'). '/*') as $node){
			if($this->limit_files&&$this->total_files>=$this->limit_files){
				return false;
			}
			$localname = $this->DIR?mb_substr($node,mb_strlen($this->DIR)):$node;
			if(is_dir($node)){
				if((!$this->DIR||!in_array($localname,$this->forbidden_dirs))&&!in_array($node,$this->forbidden_dirs)){
					if(!$this->processDir($node)){
						return false;
					}
				}
			}
			elseif(is_file($node)){
				if($this->locateName($localname)===false
				&&(!$this->DIR||!in_array($localname,$this->forbidden_files))
				&&!in_array($node,$this->forbidden_files)){
					if($this->limit_size&&$this->incremental_size($node)){
						return false;
					}
					$this->addFile($node,$localname);
					$this->total_files += 1;
				}
			}
		}
		return true;
	}
}
