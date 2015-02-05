<?php namespace Surikat\Core;
use Surikat\Core\FS;
use Surikat\Core\Images;
use Surikat\Core\ExceptionUpload;
abstract class Uploader{
	static function image($conf){
		$conf = array_merge([
			'dir'=>'',
			'key'=>'image',
			'rename'=>false,
			'width'=>false,
			'height'=>false,
			'multi'=>false,
			'extensions'=>Images::$extensions,
			'conversion'=>null,
		],$conf);
		extract($conf);
		$func = 'file'.($multi?'s':'');
		return self::$func($dir,$key,'image/',function($file)use($width,$height,$rename,$conversion){
			$ext = strtolower(pathinfo($file,PATHINFO_EXTENSION));
			if($conversion&&$ext!=$conversion&&($imgFormat=exif_imagetype($file))!=constant('IMAGETYPE_'.strtoupper($conversion))){
				switch($imgFormat){
					case IMAGETYPE_GIF :
						$img = imagecreatefromgif($file);
					break;
					case IMAGETYPE_JPEG :
						$img = imagecreatefromjpeg($file);
					break;
					case IMAGETYPE_PNG :
						$img = imagecreatefrompng($file);
					break;
					default:
						throw new ExceptionUpload('image format conversion not supported');
					break;
				}
				$file = substr($file,0,-1*strlen($ext)).$conversion;
				$ext = $conversion;
				$convertF = 'image'.$conversion;
				$convertF($img, $file);
			}
			if($rename){
				if($rename===true)
					$rename = 'image';
				$rename = self::formatFilename($rename);
				rename($file,dirname($file).'/'.$rename.'.'.$ext);
			}
			if(($width||$height)&&in_array($ext,Images::$extensions_resizable)){
				$thumb = dirname($file).'/'.pathinfo($file,PATHINFO_FILENAME).'.'.$width.'x'.$height.'.'.$ext;
				Images::createThumb($file,$thumb,$width,$height,100,true);
			}
		},function($file){
			$ext = strtolower(pathinfo($file,PATHINFO_EXTENSION));
			if(!in_array($ext,(array)$extensions))
				throw new ExceptionUpload('extension');
		});
	}
	protected static $extensionRewrite = [
		'jpeg'=>'jpg',
	];
	static function formatFilename($name){
		$name = filter_var(str_replace([' ','_',',','?'],'-',$name),FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$e = strtolower(pathinfo($name,PATHINFO_EXTENSION));
		if(isset(static::$extensionRewrite[$e]))
			$name = substr($name,0,-1*strlen($e)).static::$extensionRewrite[$e];
		return $name;
	}
	static function uploadFile(&$file,$dir='',$mime=null,$callback=null,$precallback=null,$nooverw=null,$maxFileSize=null){
		if($file['error']!==UPLOAD_ERR_OK)
			throw new ExceptionUpload($file['error']);
		if($mime&&stripos($file['type'],$mime)!==0)
			throw new ExceptionUpload('type');
		if($maxFileSize&&filesize($file['tmp_name'])>$maxFileSize)
			throw new ExceptionUpload(UPLOAD_ERR_FORM_SIZE);
		FS::mkdir($dir);
		$name = self::formatFilename($file['name']);
		if($nooverw){
			$i = 2;
			while(is_file($dir.$name))
				$name = pathinfo($name,PATHINFO_FILENAME).'-'.$i.'.'.pathinfo($name,PATHINFO_EXTENSION);
			$i++;
		}
		if($precallback)
			$precallback($dir.$name);
		if(!move_uploaded_file($file['tmp_name'],$dir.$name))
			throw new ExceptionUpload('move_uploaded_file');
		if($callback)
			$callback($dir.$name);
	}
	static function file($dir,$k,$mime=null,$callback=null,$maxFileSize=null){
		if(isset($_FILES[$k])){
			if($_FILES[$k]['name'])
				self::uploadFile($_FILES[$k],$dir,$mime,$callback,false,true,$maxFileSize);
			return true;
		}
	}
	static function files($dir,$k,$mime=null,$callback=null,$maxFileSize=null){
		if(isset($_FILES[$k])){
			$files =& $_FILES[$k];
			for($i=0;count($files['name'])>$i;$i++){
				$file = [];
				foreach(array_keys($files) as $prop)
					$file[$prop] =& $files[$prop][$i];
				if($file['name'])
					self::uploadFile($file,$dir,$mime,$callback,false,true,$maxFileSize);
			}
			return true;
		}
	}
	
	static function file_upload_max_size() {
		static $max_size = -1;
		if ($max_size < 0) {
			// Start with post_max_size.
			$max_size = self::parse_size(ini_get('post_max_size'));

			// If upload_max_size is less, then reduce. Except if upload_max_size is
			// zero, which indicates no limit.
			$upload_max = self::parse_size(ini_get('upload_max_filesize'));
			if ($upload_max > 0 && $upload_max < $max_size) {
				$max_size = $upload_max;
			}
		}
		return $max_size;
	}
	static function parse_size($size) {
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
		$size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
		if ($unit) {
			// Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
			return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
		}
		else {
			return round($size);
		}
	}
}