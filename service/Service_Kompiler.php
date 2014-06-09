<?php namespace surikat\service; 
use surikat\control;
use surikat\control\FS;
use surikat\control\JSON;
use surikat\control\Min\PHP as minPHP;
use surikat\control\Min\JS as minJS;
use surikat\control\Min\CSS as minCSS;
//register_shutdown_function(function(){if($error=error_get_last()) static $errorType = array(E_ERROR =>'ERROR',E_WARNING=> 'WARNING',E_PARSE=>'PARSING ERROR',E_NOTICE=>'NOTICE',E_CORE_ERROR     => 'CORE ERROR',E_CORE_WARNING   => 'CORE WARNING',E_COMPILE_ERROR=>'COMPILE ERROR',E_COMPILE_WARNING=>'COMPILE WARNING',E_USER_ERROR=>'USER ERROR',E_USER_WARNING=>'USER WARNING',E_USER_NOTICE=>'USER NOTICE',E_STRICT=>'STRICT NOTICE',E_RECOVERABLE_ERROR =>'RECOVERABLE ERROR'); if(in_array($error['type'],array(E_PARSE,E_ERROR,E_CORE_ERROR,E_COMPILE_ERROR,E_USER_ERROR))) echo @$errorType[$error['type']].': '.$error['message'].' in '.$error['file'].' at line '.$error['line']; });
//require __DIR__.'/../control/S_Kompiler.php';
//S_Kompiler::method();
use ReflectionClass;
use ReflectionMethod;
use ZipArchive;
/* preload dependencies for manual compilation use */
foreach(array(
	'Min/PHP',
	'Min/JS',
	'Min/CSS',
	'FS',
	'JSON'
) as $inc)
	require dirname(__FILE__).'/../control/'.$inc.'.php';
abstract class Service_Kompiler{
	static $httpCache = 4000; //in second
	static $PATH = 'surikat';
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
		file_put_contents(self::$surikat,"<?php include('".self::$PATH."/control.php');control::dev();view::index();");
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
		$p->setStub('<?php error_reporting(-1);ini_set("display_startup_errors",true);ini_set("display_errors","stdout");ini_set("html_errors",false); Phar::mapPhar(\'surikat\'); include \'phar://\'.__FILE__.\'/control.php\'; control::$SURIKAT=control::$CWD.\'surikat/\'; view::index(); __HALT_COMPILER(); ?>');
		echo "<h1>surikat Compilation to '".$target."':</h1><pre>\r\n";
		$tt = 0;
		$stt = 0;
		FS::recurse($directory,function($file)use($directory,$p,$minifyPHP,&$tt,&$stt){
			$bs = basename($file);
			$ext = strtolower(pathinfo($file,PATHINFO_EXTENSION));
			if(is_dir($file)||($ext!='php'&&($bf=basename($file))!='LICENSE'&&$bf!='README.md')||(strpos($file,control::$TMP)===0)||($file==$directory.'/index.php.phar')||($file==$directory.'/index.php'))
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
		FS::recurse(control::$TMP,function($file){
			if(is_file($file))
				unlink($file);
			elseif(is_dir($file))
				rmdir($file);
		});
			
	}
	static function Make_Ninja(){
		echo '<form method="POST" action="./Make_Ninja"><input name="dir" type="text" value="'.(isset($_POST['dir'])?$_POST['dir']:realpath(control::$CWD.'../').'/new-ninja/').'"><input type="submit" value="New Ninja"></form>';
		if(isset($_POST['dir'])){
			$dir = rtrim($_POST['dir'],'/').'/';
			echo "<h1>Making Ninja '".$dir."':</h1><pre>\r\n";
			//if(is_dir($dir))
				//throw new \Exception($dir.' allready exit');
			FS::mkdir($dir);
			self::Set_PROD_Mode($dir.'index.php');
			$sl = strlen(control::$SURIKAT);
			$callback = function($file)use($sl,$dir){
				if(is_dir($file))
					return;
				$f = $dir.($b=substr($file,$sl));
				FS::mkdir($f,true);
				copy($file,$f);
				echo "$b\r\n";
			};
			echo '<pre>';
			FS::recurse(control::$SURIKAT.'css',$callback);
			FS::recurse(control::$SURIKAT.'js',$callback);
			FS::recurse(control::$SURIKAT.'img',$callback);
			FS::recurse(control::$SURIKAT.'x-dom',$callback);
			FS::mkdir($dir.'view');
			copy(control::$SURIKAT.'view/layout.tml',$dir.'view/TML');
			echo "view/TML\r\n";
			copy(control::$SURIKAT.'view/index.tml',$dir.'view/.tml');
			echo "view/.tml\r\n";
			copy(control::$SURIKAT.'view/404.tml',$dir.'view/404.tml');
			echo "view/404.tml\r\n";
			file_put_contents($dir.'css/style.scss','');
			echo "css/style.scss\r\n";
			file_put_contents($dir.'js/script.js','');
			echo "js/script.js\r\n";
			copy(control::$SURIKAT.'htaccess',$dir.'.htaccess');
			echo ".htaccess\r\n";
			echo '</pre>';
			echo '<script type="text/javascript">window.scrollTo(0,document.body.scrollHeight);</script>';
			FS::mkdir($dir.'.tmp');
		}
	}
	static function Update_RedBean4(){
		ob_implicit_flush(true);
		ob_end_flush();
		print '<pre>';

		$tgDir = control::$SURIKAT.'model/RedBeanPHP';
		print "Cleaning (with backup if is able to) $tgDir\r\n";
		$bak = control::$TMP.'kompiler_cache/RedBean'.time();
		FS::mkdir($bak);
		FS::recurse($tgDir,function($file)use($bak){
			if(is_file($file)&&!(rename($file,$tg=$bak.'/'.basename($file))||unlink($file)))
				throw new \Exception('Unable to rename or remove "'.$file.'"');
		});
		$url = 'https://github.com/gabordemooij/redbean/archive/master.zip';
		print "Downloading $url\r\n";
		$dir = self::getZIP($url);
		$dir .= '/redbean-master/RedBeanPHP';

		print "Namespace Rewrite and Store in \"$tgDir\" :\r\n";
		$dir = realpath($dir);
		if(!$dir)
			throw new \Exception('Directory not noud: "'.$dir.'"');
		error_reporting(-1);
		ini_set('display_errors','stdout');
		$ons = 'RedBeanPHP';
		$ns = 'surikat\\model';
		$namespace = $ns.'\\RedBeanPHP';
		$_ons = str_replace('\\','\\\\',$ons);
		$_namespace = str_replace('\\','\\\\',$namespace);
		$_ns = str_replace('\\','\\\\',$ns);
		$rep = array(
			'\\\\'.$_ons=>'\\\\'.$_namespace,
			'\\'.$ons=>'\\'.$namespace,
			'\\\\'.$_ns.'\\\\'.$namespace=>'\\\\'.$_namespace,
			'namespace '.$ons=>'namespace '.$namespace,
			'use '.$ons=>'use '.$namespace,
			$_namespace.'\\\\BeanHelper\\\\SimpleFacadeBeanHelper'=>$_ns.'\\\\SimpleFacadeBeanHelper',
			$namespace.'\\BeanHelper\\SimpleFacadeBeanHelper'=>$ns.'\\SimpleFacadeBeanHelper',
		);
		FS::recurse($dir,function($file)use($ons,$namespace,$dir,$tgDir,$rep){
				if(is_file($file)&&pathinfo($file,PATHINFO_EXTENSION)=='php'&&strpos(pathinfo($file,PATHINFO_FILENAME),'.')===false){
					$code = file_get_contents($file);
					$code = str_replace(array_keys($rep),array_values($rep),$code);
					$tgFile=$tgDir.'/'.substr($file,($l=strlen($dir))+1);
					FS::mkdir($tgFile,true);
					if(file_put_contents($tgFile,$code))
						print "$tgFile\r\n";
					else
						throw new \Exception('Unable to write: "'.$tgFile.'"');
				}
					
		});
		
		print 'OK';
		print '</pre>';
	}
	/*
	static function Update_CssSelector(){
		ob_implicit_flush(true);
		ob_end_flush();
		print '<pre>';
		$url = 'https://github.com/soloproyectos/php.css-selector/archive/master.zip';
		print "Downloading $url\r\n";
		$dir = self::getZIP($url);
		$dir .= '/php.css-selector-master/classes';
		$replace = array(
			'\\arr'=>'\\Arr',
			'\\css'=>'\\Css',
			'\\text'=>'\\Text',
			'\\exception'=>'\\Exception',
			'\\parser'=>'\\Parser',
			'\\combinator'=>'\\Combinator',
			'\\filter'=>'\\Filter',
			'\\model'=>'\\Model',
			'\\tokenizer'=>'\\Tokenizer',
			'require_once'=>'#require_once',
			'com\\soloproyectos\\core'=>'surikat\\view\\CssSelector',
			'com\\\\soloproyectos\\\\core'=>'surikat\\\\view\\\\CssSelector',
			'surikat\\view\\CssSelector\\Css'=>'surikat\\view\\CssSelector',
			'surikat\\\\view\\\\CssSelector\\Css'=>'surikat\\\\view\\\\CssSelector',
			"surikat\\\\view\\\\CssSelector\\\\Css"=>"surikat\\\\view\\\\CssSelector",
			"instanceof DOMElement"=>'instanceof \\surikat\\view\\CORE',
			"instanceof DOMNode"=>'instanceof \\surikat\\view\\CORE',
			"use DOMDocument;"=>'',
			"use DOMElement;"=>'',
			"use DOMNode;"=>'',
			"iso-8859-1"=>'utf-8',
			//"instanceof DOMDocument"=>'instanceof \\surikat\\view\\FILE',
			//"ownerDocument"=>'vFile',
		);
		FS::recurse($dir,function($file)use($dir,$replace){
			$rel = substr($file,strlen($dir));
			if(!is_file($file)||pathinfo($file,PATHINFO_EXTENSION)!='php'||basename($file)=='autoload.php'||stripos($rel,'/sys/')===0)
				return;
			$x = explode('-',$rel);
			foreach(array_keys($x) as $i)
				$x[$i] = ucfirst($x[$i]);
			$rel = implode('',$x);
			$x = explode('/',$rel);
			foreach(array_keys($x) as $i)
				$x[$i] = ucfirst($x[$i]);
			$rel = implode('/',$x);
			if(stripos($rel,'/Css/')===0)
				$rel = '/'.substr($rel,5);
			$path = control::$SURIKAT.'view/CssSelector'.$rel;
			print $path."\r\n";
			FS::mkdir($path,true);
			if(!file_put_contents($path,str_replace(array_keys($replace),array_values($replace),file_get_contents($file))))
				throw new \Exception('Unable to write '.$path);
		});
		print '</pre>';
	}
	static function Synaptic_Format_humanReadable(){
		print "<pre>";
		C\FS::recurse($dir=control::$CWD.'apt',function($file)use($dir){
			if(substr($file,strlen($seek='synaptic.json')*-1)!=$seek)
				return;
			$json = C\JSON::humanReadable(C\JSON::decode(file_get_contents($file)));
			print "$file\r\n";
			if($json&&file_put_contents($file,$json))
				print $json."\r\n\r\n";
		});
		print "</pre>";
	}
	*/
	protected static function cachedHTTP($file){
		return is_file($file)&&filesize($file)&&filemtime($file)>time()-self::$httpCache;
	}
	protected static function getZIP($url){
		$zip = new ZipArchive;
		$dir = control::$TMP.'kompiler_cache/'.sha1($url);
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
