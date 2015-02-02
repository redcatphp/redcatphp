<?php namespace Surikat\Tool;
abstract class Streaming{
	static function flv($file,$seekat=0){
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		header("Content-Type: video/x-flv");
		if($seekat != 0) {
		   print("FLV");
		   print(pack('C', 1 ));
		   print(pack('C', 1 ));
		   print(pack('N', 9 ));
		   print(pack('N', 9 ));
		}
		$fh = fopen($file, "rb");
		fseek($fh, $seekat);
		while (!feof($fh)) {
		   print (fread($fh, 16384));
		}
		fclose($fh);
	}
}