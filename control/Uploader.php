<?php namespace surikat\control;
class Uploader{
	static function image($dir,$k='image',$width,$height){
		return self::file($dir,$k,'image/',function($file)use($width,$height){
			$ext = strtolower(pathinfo($file,PATHINFO_EXTENSION));
			if(!in_array($ext,Images::$extensions))
				throw new Exception_Uploader('extension');
			if(in_array($ext,Images::$extensions_resizable)){
				$thumb = dirname($file).'/'.pathinfo($file,PATHINFO_FILENAME).'.'.$width.'x'.$height.'.'.$ext;
				Images::createThumb($file,$thumb,$width,$height,100,true);
			}
		});
	}
	static function file($dir,$k,$mime=null,$callback=null){
		if(isset($_FILES[$k])){
			foreach($i=0;count($_FILES[$k]['name'])>$i;$i++){
				$file = &$_FILES[$k][$i];
				if($file['error']!==UPLOAD_ERR_OK)
					throw new Exception_Uploader($file['error']);
				if(stripos($mime,$mime)!==0)
					throw new Exception_Uploader('type');
				FS::mkdir($dir);
				if(!move_uploaded_file($file['tmp_name'],$dir.$file['name']))
					throw new Exception_Uploader('move_uploaded_file');
				if($callback)
					$callback($dir.$file['name']);
			}
			return true;
		}
	}
}
