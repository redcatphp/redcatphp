<?php namespace surikat\control;
class uploader{
	static function image($conf){
		$conf = array_merge(array(
			'dir'=>'',
			'key'=>'image',
			'rename'=>false,
			'width'=>false,
			'height'=>false,
			'multi'=>false
		),$conf);
		extract($conf);
		$func = 'file'.($multi?'s':'');
		return self::$func($dir,$key,'image/',function($file)use($width,$height,$rename){
			$ext = strtolower(pathinfo($file,PATHINFO_EXTENSION));
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
			if(!in_array($ext,Images::$extensions))
				throw new Exception_Upload('extension');
		});
	}
	static function formatFilename($name){
		return filter_var(str_replace(array(' ','_',',','?','.'),'-',$name),FILTER_SANITIZE_FULL_SPECIAL_CHARS);
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
