<?php namespace surikat\control;
class uploader{
	static function image($conf){
		$conf = array_merge(array(
			'dir'=>'',
			'key'=>'image',
			'rename'=>false,
			'width'=>false,
			'height'=>false,
			'multi'=>false,
			'extensions'=>Images::$extensions,
			'conversion'=>null,
		),$conf);
		extract($conf);
		$func = 'file'.($multi?'s':'');
		return self::$func($dir,$key,'image/',function($file)use($width,$height,$rename){
			$ext = strtolower(pathinfo($file,PATHINFO_EXTENSION));
			if($conversion){
				switch(exif_imagetype($file)){
					case IMAGETYPE_GIF :
						$img = imagecreatefromgif($file);
					break;
					case IMAGETYPE_JPEG :
						$img = imagecreatefromjpeg($file);
					break;
					default :
					throw new Exception_Upload('extension conversion');
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
				throw new Exception_Upload('extension');
		});
	}
	protected $extensionRewrite = array(
		'jpeg'=>'jpg',
	);
	static function formatFilename($name){
		$name = filter_var(str_replace(array(' ','_',',','?'),'-',$name),FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$e = strtolower(pathinfo($name,PATHINFO_EXTENSION));
		if(isset(static::$extensionRewrite[$e]))
			$name = substr($name,0,-1*strlen($e)).static::$extensionRewrite[$e];
		return $name;
	}
	static function uploadFile(&$file,$dir='',$mime=null,$callback=null,$precallback=null,$nooverw=null){
		if($file['error']!==UPLOAD_ERR_OK)
			throw new Exception_Upload($file['error']);
		if($mime&&stripos($file['type'],$mime)!==0)
			throw new Exception_Upload('type');
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
			throw new Exception_Upload('move_uploaded_file');
		if($callback)
			$callback($dir.$name);
	}
	static function file($dir,$k,$mime=null,$callback=null){
		if(isset($_FILES[$k])){
			if($_FILES[$k]['name'])
				self::uploadFile($_FILES[$k],$dir,$mime,$callback,false,true);
			return true;
		}
	}
	static function files($dir,$k,$mime=null,$callback=null){
		if(isset($_FILES[$k])){
			$files =& $_FILES[$k];
			for($i=0;count($files['name'])>$i;$i++){
				$file = array();
				foreach(array_keys($files) as $prop)
					$file[$prop] =& $files[$prop][$i];
				if($file['name'])
					self::uploadFile($file,$dir,$mime,$callback,false,true);
			}
			return true;
		}
	}
}