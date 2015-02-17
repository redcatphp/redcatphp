<?php namespace Surikat\Service; 
use Suriakt\User\Auth;
use Surikat\FileSystem\FS;
use Surikat\Vars\JSON;
use Surikat\Minify\PHP as minPHP;
use Surikat\Minify\JS as minJS;
use Surikat\Minify\CSS as minCSS;
use ReflectionClass;
use ReflectionMethod;
use ZipArchive;
/* preload dependencies for manual compilation use */
foreach([
	'Min/PHP',
	'Min/JS',
	'Min/CSS',
	'Min/HTML',
	'FS',
	'JSON'
] as $inc)
	require dirname(__FILE__).'/../control/'.$inc.'.php';
abstract class ServiceKompiler{
	static $httpCache = 4000; //in second
	static $PATH = 'Surikat';
	static $surikat = 'index.php';
	static function method(){
		Auth::lockServer(Auth::RIGHT_MANAGE);
		$class = new ReflectionClass(__CLASS__);
		$methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
		foreach($methods as $method)
			if($method->name!=__FUNCTION__)
				echo '<button onclick="document.location=\''.@$_SERVER['PATH_INFO'].'/'.$method->name.'\';">'.str_replace('_',' ',$method->name).'</button><br>';
	}
	static function Set_DEV_Mode(){
		echo "<pre>surikat mapped to sources by '".getcwd().'/'.self::$surikat."':\r\n";
		file_put_contents(self::$surikat,"<?php
if(!@include(__DIR__.'/".self::$PATH."/Bootstrap.php'))
	symlink('../".self::$PATH."','Surikat')&&include('".self::$PATH."/Bootstrap.php');");
	}
	static function Set_PROD_Mode($target=null){
		Auth::lockServer(Auth::RIGHT_MANAGE);
		set_time_limit(0);
		ob_implicit_flush(true);
		ob_end_flush();
		$target=$target?$target:self::$surikat;
		$minifyPHP=true;
		error_reporting(-1);
		ini_set('display_errors','stdout');
		if($minifyPHP===null&&isset($_GET['min']))
			$minifyPHP = $_GET['min'];
		if(is_file($target.'.phar'))
			unlink($target.'.phar');
		$p = new \Phar($target.'.phar',0,'surikat');
		$directory = getcwd().'/'.self::$PATH;
		$p->setStub('<?php error_reporting(-1);ini_set("display_startup_errors",true);ini_set("display_errors","stdout");ini_set("html_errors",false);include \'phar://\'.__FILE__.\'/Bootstrap.php\'; __HALT_COMPILER(); ?>');
		echo "<h1>Surikat Compilation to '".$target."':</h1><pre>\r\n";
		$tt = 0;
		$stt = 0;
		FS::recurse($directory,function($file)use($directory,$p,$minifyPHP,&$tt,&$stt){
			$bs = basename($file);
			$ext = strtolower(pathinfo($file,PATHINFO_EXTENSION));
			if(is_dir($file)||($ext!='php'&&($bf=basename($file))!='LICENSE'&&$bf!='README.md')||(strpos($file,SURIKAT_TMP)===0)||($file==$directory.'/index.php.phar')||($file==$directory.'/index.php'))
				return;
			$tg = substr($file,strlen($directory)+1);
			$content = file_get_contents($file);
			$stt += ($siz=strlen($content));
			if($ext=='php'&&$minifyPHP)
				$content = minPHP::minify($content);
			$p->addFromString($tg,$content);
			echo "$tg\r\n";
			$tt += 1;
		});
		echo "</pre>";
		echo "</p>$tt files: ".FS::humanSize($stt)."</p><p>index.php: ".FS::humanSize($_stt=filesize($target.'.phar'))."</p>";
		echo '<p>compression: '.round(100-(($_stt/$stt)*100)).'%</p>';
		echo '<script type="text/javascript">window.scrollTo(0,document.body.scrollHeight);</script>';
		if(is_file($target))
			unlink($target);
		rename($target.'.phar',$target);
		FS::recurse(SURIKAT_TMP,function($file){
			if(is_file($file))
				@unlink($file);
			elseif(is_dir($file))
				@rmdir($file);
		});
			
	}
	protected static function cachedHTTP($file){
		return is_file($file)&&filesize($file)&&filemtime($file)>time()-self::$httpCache;
	}
	protected static function getZIP($url){
		$zip = new ZipArchive;
		$dir = SURIKAT_TMP.'kompiler_cache/'.sha1($url);
		FS::mkdir($dir);
		if($cached = self::cachedHTTP($dir.'.zip'))
			print "from cache \r\n";
		if(!$cached&&$tmp=file_get_contents($url))
			file_put_contents($dir.'.zip',$tmp);
		$res = $zip->open($dir.'.zip');
		if($res===true){
			if(!$zip->extractTo($dir))
				throw new \Exception("Error during extracting  $url to $dir");
			$zip->close();
			clearstatcache();
			return $dir;
		}
		else{
			@unlink($dir.'.zip');
			switch($res){
				case ZipArchive::ER_EXISTS:
					$ErrMsg = "File already exists";
				break;
				case ZipArchive::ER_INCONS:
					$ErrMsg = "Zip archive inconsistent";
				break;
				case ZipArchive::ER_MEMORY:
					$ErrMsg = "Malloc failure";
				break;
				case ZipArchive::ER_NOENT:
					$ErrMsg = "No such file";
				break;
				case ZipArchive::ER_NOZIP:
					$ErrMsg = "Not a zip archive";
				break;
				case ZipArchive::ER_OPEN:
					$ErrMsg = "Can't open file";
				break;
				case ZipArchive::ER_READ:
					$ErrMsg = "Read error";
				break;
				case ZipArchive::ER_SEEK:
					$ErrMsg = "Seek error";
				break;
				default:
					$ErrMsg = "Unknow (Code $rOpen)";
				break;			   
			}
			throw new \Exception( 'ZipArchive Error: ' . $ErrMsg.': '.$url);
		}
	}
}