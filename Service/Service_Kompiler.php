<?php namespace Surikat\Service; 
use Surikat\Control;
use Surikat\Control\FS;
use Surikat\Control\JSON;
use Surikat\Control\Min\PHP as minPHP;
use Surikat\Control\Min\JS as minJS;
use Surikat\Control\Min\CSS as minCSS;
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
abstract class Service_Kompiler{
	static $httpCache = 4000; //in second
	static $PATH = 'Surikat';
	static $surikat = 'index.php';
	static function method(){
		$class = new ReflectionClass(__CLASS__);
		$methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
		foreach($methods as $method)
			if($method->name!=__FUNCTION__)
				echo '<button onclick="document.location=\''.@$_SERVER['PATH_INFO'].'/'.$method->name.'\';">'.str_replace('_',' ',$method->name).'</button><br>';
	}
	static function Set_DEV_Mode(){
		echo "<pre>surikat mapped to sources by '".getcwd().'/'.self::$surikat."':\r\n";
		file_put_contents(self::$surikat,"<?php
if(!@include(__DIR__.'/".self::$PATH."/Control.php'))
	symlink('../".self::$PATH."','Surikat')&&include('".self::$PATH."/Control.php');
Dev::level(Dev::STD);
View::getInstance()->index();");
	}
	static function Set_PROD_Mode($target=null){
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
		$p->setStub('<?php error_reporting(-1);ini_set("display_startup_errors",true);ini_set("display_errors","stdout");ini_set("html_errors",false);include \'phar://\'.__FILE__.\'/Control.php\'; Control::$SURIKAT=Control::$CWD.\'Surikat/\'; View::getInstance()->index(); __HALT_COMPILER(); ?>');
		echo "<h1>Surikat Compilation to '".$target."':</h1><pre>\r\n";
		$tt = 0;
		$stt = 0;
		FS::recurse($directory,function($file)use($directory,$p,$minifyPHP,&$tt,&$stt){
			$bs = basename($file);
			$ext = strtolower(pathinfo($file,PATHINFO_EXTENSION));
			if(is_dir($file)||($ext!='php'&&($bf=basename($file))!='LICENSE'&&$bf!='README.md')||(strpos($file,Control::$TMP)===0)||($file==$directory.'/index.php.phar')||($file==$directory.'/index.php'))
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
		FS::recurse(Control::$TMP,function($file){
			if(is_file($file))
				@unlink($file);
			elseif(is_dir($file))
				@rmdir($file);
		});
			
	}
	static function Make_Ninja(){
		echo '<form method="POST" action="./Make_Ninja"><input name="dir" type="text" value="'.(isset($_POST['dir'])?$_POST['dir']:realpath(Control::$CWD.'../').'/new-ninja/').'"><input type="submit" value="New Ninja"></form>';
		if(isset($_POST['dir'])){
			$dir = rtrim($_POST['dir'],'/').'/';
			echo "<h1>Making Ninja '".$dir."':</h1><pre>\r\n";
			//if(is_dir($dir))
				//throw new \Exception($dir.' allready exit');
			FS::mkdir($dir);
			self::Set_PROD_Mode($dir.'index.php');
			$sl = strlen(Control::$SURIKAT);
			$callback = function($file)use($sl,$dir){
				if(is_dir($file))
					return;
				$f = $dir.($b=substr($file,$sl));
				FS::mkdir($f,true);
				copy($file,$f);
				echo "$b\r\n";
			};
			echo '<pre>';
			FS::recurse(Control::$SURIKAT.'css',$callback);
			FS::recurse(Control::$SURIKAT.'js',$callback);
			FS::recurse(Control::$SURIKAT.'img',$callback);
			FS::recurse(Control::$SURIKAT.'x-dom',$callback);
			FS::mkdir($dir.'view');
			copy(Control::$SURIKAT.'view/layout.tml',$dir.'view/TML');
			echo "view/TML\r\n";
			copy(Control::$SURIKAT.'view/index.tml',$dir.'view/.tml');
			echo "view/.tml\r\n";
			copy(Control::$SURIKAT.'view/404.tml',$dir.'view/404.tml');
			echo "view/404.tml\r\n";
			file_put_contents($dir.'css/style.scss','');
			echo "css/style.scss\r\n";
			file_put_contents($dir.'js/script.js','');
			echo "js/script.js\r\n";
			copy(Control::$SURIKAT.'htaccess',$dir.'.htaccess');
			echo ".htaccess\r\n";
			echo '</pre>';
			echo '<script type="text/javascript">window.scrollTo(0,document.body.scrollHeight);</script>';
			FS::mkdir($dir.'.tmp');
		}
	}
	protected static function cachedHTTP($file){
		return is_file($file)&&filesize($file)&&filemtime($file)>time()-self::$httpCache;
	}
	protected static function getZIP($url){
		$zip = new ZipArchive;
		$dir = Control::$TMP.'kompiler_cache/'.sha1($url);
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