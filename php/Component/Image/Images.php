<?php namespace Surikat\Image;
use Surikat\FileSystem\FS;
abstract class Images {
	public static $extensions = ['jpg','jpeg','png','gif','bmp','tiff','ico'];
	public static $extensions_resizable = ['jpeg','jpg','png','gif','tiff','bmp'];
	public static function resized($q){
		return preg_match_all ('/((.*))(\\.)(\\d+)(x)(\\d+)(\\.)((?:[a-z][a-z]+))/is', $q, $matches)?$matches:false;
	}
	public static function ext_is($filename){
		return in_array(strtolower(pathinfo($filename,PATHINFO_EXTENSION)),self::$extensions);
	}

	/**
	 * @link http://pl.php.net/manual/en/function.imagecopyresampled.php
	 * replacement to imagecopyresampled that will deliver results that are almost identical except MUCH faster (very typically 30 times faster)
	 *
	 * @static
	 * @access public
	 * @param string $dst_image
	 * @param string $src_image
	 * @param int $dst_x
	 * @param int $dst_y
	 * @param int $src_x
	 * @param int $src_y
	 * @param int $dst_w
	 * @param int $dst_h
	 * @param int $src_w
	 * @param int $src_h
	 * @param int $quality
	 * @return boolean
	 */
	public static function fastImageCopyResampled (&$dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h, $quality = 3){
		if (empty($src_image) || empty($dst_image)) {
			return false;
		}
		if($quality<=1){
			$temp = imagecreatetruecolor ($dst_w + 1, $dst_h + 1);
			imagecopyresized ($temp, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w + 1, $dst_h + 1, $src_w, $src_h);
			imagecopyresized ($dst_image, $temp, 0, 0, 0, 0, $dst_w, $dst_h, $dst_w, $dst_h);
			imagedestroy ($temp);
		}
		elseif($quality < 5 && (($dst_w * $quality) < $src_w || ($dst_h * $quality) < $src_h)){
			$tmp_w = $dst_w * $quality;
			$tmp_h = $dst_h * $quality;
			$temp = imagecreatetruecolor ($tmp_w + 1, $tmp_h + 1);
			imagecopyresized ($temp, $src_image, 0, 0, $src_x, $src_y, $tmp_w + 1, $tmp_h + 1, $src_w, $src_h);
			imagecopyresampled ($dst_image, $temp, $dst_x, $dst_y, 0, 0, $dst_w, $dst_h, $tmp_w, $tmp_h);
			imagedestroy ($temp);
		}
		else{
			imagecopyresampled ($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
		}
		return true;
	}
	/**
	* Source: http://pl.php.net/imagecreate
	* (optimized for speed and memory usage, but yet not very efficient)
	*
	* @static
	* @access public
	* @param string $filename
	* @return resource
	*/
	public static function imageCreateFromBmp($filename){
		//20 seconds seems to be a reasonable value to not kill a server and process images up to 1680x1050
		@set_time_limit(20);

		if (false === ($f1 = fopen($filename, "rb"))) {
			return false;
		}

		$FILE = unpack("vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread($f1, 14));
		if ($FILE['file_type'] != 19778) {
			return false;
		}

		$BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel'.
		'/Vcompression/Vsize_bitmap/Vhoriz_resolution'.
		'/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1, 40));

		$BMP['colors'] = pow(2,$BMP['bits_per_pixel']);

		if ($BMP['size_bitmap'] == 0) {
			$BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
		}

		$BMP['bytes_per_pixel'] = $BMP['bits_per_pixel']/8;
		$BMP['bytes_per_pixel2'] = ceil($BMP['bytes_per_pixel']);
		$BMP['decal'] = ($BMP['width']*$BMP['bytes_per_pixel']/4);
		$BMP['decal'] -= floor($BMP['width']*$BMP['bytes_per_pixel']/4);
		$BMP['decal'] = 4-(4*$BMP['decal']);

		if ($BMP['decal'] == 4) {
			$BMP['decal'] = 0;
		}

		$PALETTE = [];
		if ($BMP['colors'] < 16777216) {
			$PALETTE = unpack('V'.$BMP['colors'], fread($f1, $BMP['colors']*4));
		}

		//2048x1536px@24bit don't even try to process larger files as it will probably fail
		if ($BMP['size_bitmap'] > 3 * 2048 * 1536) {
			return false;
		}

		$IMG = fread($f1, $BMP['size_bitmap']);
		fclose($f1);
		$VIDE = chr(0);

		$res = imagecreatetruecolor($BMP['width'],$BMP['height']);
		$P = 0;
		$Y = $BMP['height']-1;

		$line_length = $BMP['bytes_per_pixel']*$BMP['width'];

		if ($BMP['bits_per_pixel'] == 24) {
			while ($Y >= 0)
			{
				$X=0;
				$temp = unpack( "C*", substr($IMG, $P, $line_length));

				while ($X < $BMP['width'])
				{
					$offset = $X*3;
					imagesetpixel($res, $X++, $Y, ($temp[$offset+3] << 16) + ($temp[$offset+2] << 8) + $temp[$offset+1]);
				}
				$Y--;
				$P += $line_length + $BMP['decal'];
			}
		}
		elseif ($BMP['bits_per_pixel'] == 8)
		{
			while ($Y >= 0)
			{
				$X=0;

				$temp = unpack( "C*", substr($IMG, $P, $line_length));

				while ($X < $BMP['width'])
				{
					imagesetpixel($res, $X++, $Y, $PALETTE[$temp[$X] +1]);
				}
				$Y--;
				$P += $line_length + $BMP['decal'];
			}
		}
		elseif ($BMP['bits_per_pixel'] == 4)
		{
			while ($Y >= 0)
			{
				$X=0;
				$i = 1;
				$low = true;

				$temp = unpack( "C*", substr($IMG, $P, $line_length));

				while ($X < $BMP['width'])
				{
					if ($low) {
						$index = $temp[$i] >> 4;
					}
					else {
						$index = $temp[$i++] & 0x0F;
					}
					$low = !$low;

					imagesetpixel($res, $X++, $Y, $PALETTE[$index +1]);
				}
				$Y--;
				$P += $line_length + $BMP['decal'];
			}
		}
		elseif ($BMP['bits_per_pixel'] == 1)
		{
			$COLOR = unpack("n",$VIDE.substr($IMG,floor($P),1));
			if     (($P*8)%8 == 0) $COLOR[1] =  $COLOR[1]        >>7;
			elseif (($P*8)%8 == 1) $COLOR[1] = ($COLOR[1] & 0x40)>>6;
			elseif (($P*8)%8 == 2) $COLOR[1] = ($COLOR[1] & 0x20)>>5;
			elseif (($P*8)%8 == 3) $COLOR[1] = ($COLOR[1] & 0x10)>>4;
			elseif (($P*8)%8 == 4) $COLOR[1] = ($COLOR[1] & 0x8)>>3;
			elseif (($P*8)%8 == 5) $COLOR[1] = ($COLOR[1] & 0x4)>>2;
			elseif (($P*8)%8 == 6) $COLOR[1] = ($COLOR[1] & 0x2)>>1;
			elseif (($P*8)%8 == 7) $COLOR[1] = ($COLOR[1] & 0x1);
			$COLOR[1] = $PALETTE[$COLOR[1]+1];
		}
		else {
			return false;
		}

		return $res;
	}
	/**
	 * convert shorthand php.ini notation into bytes, much like how the PHP source does it
	 * @link http://pl.php.net/manual/en/function.ini-get.php
	 *
	 * @static
	 * @access public
	 * @param string $val
	 * @return int
	 */
	public static function returnBytes($val) {
		$val = trim($val);
		if (!$val) {
			return 0;
		}
		$last = strtolower($val[strlen($val)-1]);
		switch($last) {
			// The 'G' modifier is available since PHP 5.1.0
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return $val;
	}

	/**
	 * @link http://pl.php.net/manual/pl/function.imagecreatefromjpeg.php
	 * function posted by e dot a dot schultz at gmail dot com
	 *
	 * @static
	 * @access public
	 * @param string $filename
	 * @return boolean
	 */
	public static function setMemoryForImage($imageWidth, $imageHeight, $imageBits, $imageChannels)
	{
		$MB = 1048576;  // number of bytes in 1M
		$K64 = 65536;    // number of bytes in 64K
		$TWEAKFACTOR = 2.4;  // Or whatever works for you
		$memoryNeeded = round( ( $imageWidth * $imageHeight
		* $imageBits
		* $imageChannels / 8
		+ $K64
		) * $TWEAKFACTOR
		) + 3*$MB;

		//ini_get('memory_limit') only works if compiled with "--enable-memory-limit" also
		//Default memory limit is 8MB so well stick with that.
		//To find out what yours is, view your php.ini file.
		$memoryLimit = self::returnBytes(@ini_get('memory_limit'))/$MB;
		if (!$memoryLimit) {
			$memoryLimit = 8;
		}

		$memoryLimitMB = $memoryLimit * $MB;
		if (function_exists('memory_get_usage')) {
			if (memory_get_usage() + $memoryNeeded > $memoryLimitMB) {
				$newLimit = $memoryLimit + ceil( ( memory_get_usage()
				+ $memoryNeeded
				- $memoryLimitMB
				) / $MB
				);
				if (@ini_set( 'memory_limit', $newLimit . 'M' ) === false) {
					return false;
				}
			}
		} else {
			if ($memoryNeeded + 3*$MB > $memoryLimitMB) {
				$newLimit = $memoryLimit + ceil(( 3*$MB
				+ $memoryNeeded
				- $memoryLimitMB
				) / $MB
				);
				if (false === @ini_set( 'memory_limit', $newLimit . 'M' )) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Return aspect ratio size, returns associative array:
	 * <pre>
	 * Array
	 * (
	 *      [Width] => 80
	 *      [Heigth] => 120
	 * )
	 * </pre>
	 *
	 * @param int $maxWidth
	 * @param int $maxHeight
	 * @param int $actualWidth
	 * @param int $actualHeight
	 * @return array
	 * @static
	 * @access public
	 */
	public static function getAspectRatioSize($maxWidth, $maxHeight, $actualWidth, $actualHeight)
	{
		$oSize = ["Width"=>$maxWidth, "Height"=>$maxHeight];

		// Calculates the X and Y resize factors
		$iFactorX = (float)$maxWidth / (float)$actualWidth;
		$iFactorY = (float)$maxHeight / (float)$actualHeight;

		// If some dimension have to be scaled
		if ($iFactorX != 1 || $iFactorY != 1)
		{
			// Uses the lower Factor to scale the oposite size
			if ($iFactorX < $iFactorY) {
				$oSize["Height"] = (int)round($actualHeight * $iFactorX);
			}
			else if ($iFactorX > $iFactorY) {
				$oSize["Width"] = (int)round($actualWidth * $iFactorY);
			}
		}

		if ($oSize["Height"] <= 0) {
			$oSize["Height"] = 1;
		}
		if ($oSize["Width"] <= 0) {
			$oSize["Width"] = 1;
		}

		// Returns the Size
		return $oSize;
	}
	public static function createThumb($sourceFile, $targetFile, $maxWidth, $maxHeight, $quality = 80, $preserverAspectRatio = true, $bmpSupported = false){
		$dir = dirname($targetFile);
		FS::mkdir($dir);
		$sourceImageAttr = @getimagesize($sourceFile);
		if ($sourceImageAttr === false) {
			return false;
		}
		$sourceImageWidth = isset($sourceImageAttr[0]) ? $sourceImageAttr[0] : 0;
		$sourceImageHeight = isset($sourceImageAttr[1]) ? $sourceImageAttr[1] : 0;
		$sourceImageMime = isset($sourceImageAttr["mime"]) ? $sourceImageAttr["mime"] : "";
		$sourceImageBits = isset($sourceImageAttr["bits"]) ? $sourceImageAttr["bits"] : 8;
		$sourceImageChannels = isset($sourceImageAttr["channels"]) ? $sourceImageAttr["channels"] : 3;

		if (!$sourceImageWidth || !$sourceImageHeight || !$sourceImageMime) {
			return false;
		}

		$iFinalWidth = $maxWidth == 0 ? $sourceImageWidth : $maxWidth;
		$iFinalHeight = $maxHeight == 0 ? $sourceImageHeight : $maxHeight;

		if ($sourceImageWidth <= $iFinalWidth && $sourceImageHeight <= $iFinalHeight) {
			if ($sourceFile != $targetFile) {
				copy($sourceFile, $targetFile);
			}
			return true;
		}

		if ($preserverAspectRatio){
			// Gets the best size for aspect ratio resampling
			$oSize = self::getAspectRatioSize($iFinalWidth, $iFinalHeight, $sourceImageWidth, $sourceImageHeight );
		}
		else{
			$oSize = ['Width' => $iFinalWidth, 'Height' => $iFinalHeight];
		}

		self::setMemoryForImage($sourceImageWidth, $sourceImageHeight, $sourceImageBits, $sourceImageChannels);

		switch ($sourceImageAttr['mime']){
			case 'image/gif':
				if(@imagetypes() & IMG_GIF){
					$oImage = @imagecreatefromgif($sourceFile);
				}
				else{
					$ermsg = 'GIF images are not supported';
				}
			break;
			case 'image/jpeg':
				if (@imagetypes() & IMG_JPG) {
					$oImage = @imagecreatefromjpeg($sourceFile) ;
				}
				else{
					$ermsg = 'JPEG images are not supported';
				}
			break;
			case 'image/png':
				if (@imagetypes() & IMG_PNG) {
					$oImage = @imagecreatefrompng($sourceFile) ;
				}
				else{
					$ermsg = 'PNG images are not supported';
				}
			break;
			case 'image/wbmp':
				if (@imagetypes() & IMG_WBMP) {
					$oImage = @imagecreatefromwbmp($sourceFile);
				}
				else{
					$ermsg = 'WBMP images are not supported';
				}
			break;
			case 'image/bmp':
				/*
				* This is sad that PHP doesn't support bitmaps.
				* Anyway, we will use our custom function at least to display thumbnails.
				* We'll not resize images this way (if $sourceFile === $targetFile),
				* because user defined imagecreatefrombmp and imagecreatebmp are horribly slow
				*/
				if ($bmpSupported && (@imagetypes() & IMG_JPG) && $sourceFile != $targetFile) {
					$oImage = self::imageCreateFromBmp($sourceFile);
				} else {
					$ermsg = 'BMP/JPG images are not supported';
				}
			break;
			default:
				$ermsg = $sourceImageAttr['mime'].' images are not supported';
			break;
		}

		if (isset($ermsg) || false === $oImage) {
			// echo $ermsg."\r\n";
			return false;
		}


		$oThumbImage = imagecreatetruecolor($oSize["Width"], $oSize["Height"]);
		if($sourceImageAttr['mime']=='image/png'||$sourceImageAttr['mime']=='image/gif'){
			imagealphablending($oThumbImage, false);
			imagesavealpha($oThumbImage,true);
			$transparent = imagecolorallocatealpha($oThumbImage, 255, 255, 255, 127);
			imagefilledrectangle($oThumbImage, 0, 0, $oSize["Width"], $oSize["Height"], $transparent);
		}
		//imagecopyresampled($oThumbImage, $oImage, 0, 0, 0, 0, $oSize["Width"], $oSize["Height"], $sourceImageWidth, $sourceImageHeight);
		self::fastImageCopyResampled($oThumbImage, $oImage, 0, 0, 0, 0, $oSize["Width"], $oSize["Height"], $sourceImageWidth, $sourceImageHeight, (int)max(floor($quality/20), 1));

		switch ($sourceImageAttr['mime']){
			case 'image/gif':
				imagegif($oThumbImage, $targetFile);
			break;
			case 'image/jpeg':
			case 'image/bmp':
				imagejpeg($oThumbImage, $targetFile, $quality);
			break;
			case 'image/png':
				imagepng($oThumbImage, $targetFile);
			break;
			case 'image/wbmp':
				imagewbmp($oThumbImage, $targetFile);
			break;
		}

		if(file_exists($targetFile)){
			$oldUmask = umask(0);
			chmod($targetFile, 0777);
			umask($oldUmask);
		}

		imageDestroy($oImage);
		imageDestroy($oThumbImage);

		return true;
	}
	public static function data64($file,$format='image/png'){
		return "data:".$format.";base64,".base64_encode(file_get_contents($file));
		/* exemple: <img src="<?=data64($file,'image/'.end(explode(".", $file)))?>" /> */
	}

	public static function couleur_opposee($couleur){
		$r = dechex(255 - hexdec(substr($couleur,0,2)));
		$r = (strlen($r) > 1) ? $r : '0'.$r;
		$g = dechex(255 - hexdec(substr($couleur,2,2)));
		$g = (strlen($g) > 1) ? $g : '0'.$g;
		$b = dechex(255 - hexdec(substr($couleur,4,2)));
		$b = (strlen($b) > 1) ? $b : '0'.$b;
		return $r.$g.$b;
		/* http://www.phpsources.org/scripts316-PHP.htm  Koogar 25 Déc 2007 */
	}
	public static function couleur_sombre(){
		$Frag = range (0,6);
		$frag = range(0,6);
		$Text = "";
		$text = "";
		for($i=1; $i<=6; $i++) {
			for ($i = 1; $i <= 6; $i++) {
				$Text = $text.$text = $text.$Frag[mt_rand(0,count($frag)-1)];
				$frag[mt_rand(0, count($frag)-1)];
			}
		}
		return $text;
	}
	public static function hex2rgb($hex){ 
		if(! mb_ereg("[0-9a-fA-F]{6}", $hex)) { 
			echo "Error : input is not a valid hexadecimal number"; 
			return 0; 
		} 
		for($i=0; $i<3; $i++) { 
			$temp = substr($hex, 2*$i, 2); 
			$rgb[$i] = 16 * hexdec(substr($temp, 0, 1)) + 
			hexdec(substr($temp, 1, 1)); 
		}
		return $rgb; 
	} 
}
