<?php namespace Surikat\Crypto;
class Image {
    var $Canvas;
    var $Name = '';
    var $Width = 0;
    var $Height = 0;
    var $PixelPointer = ['x' => 0, 'y' => 0];
    var $EOF = TRUE;
    function Image($image){
        if ($image){
            $this->Load($image);

        }
		else {
            return FALSE;
        }
    }
    function Load($image){
        if (is_array($image) && isset($image['tmp_name'])){
            $this->SetName($image['name']);
            $this->CreateCanvas($image['tmp_name'], $image['name']);

        }
		else {
            $this->SetName($image);
            $this->CreateCanvas($image);
        }
        if (is_resource($this->Canvas)){
            $this->EOF = FALSE;
            $this->ClearCanvas();
            $this->FirstPixel();
            return TRUE;
        }
		else {
            return FALSE;
        }
    }
    function CreateCanvas($image, $name = ''){
        if (!$name) $name = $image;
        switch ($this->GetImageType($name)){
            case 'JPG':
                $this->Canvas = imagecreatefromjpeg($image); break;
            case 'PNG':
                $this->Canvas = imagecreatefrompng($image); break;
            case 'GIF':
                $this->Canvas = imagecreatefromgif($image); break;
            default:
                return;
        }
        if (is_resource($this->Canvas)){
            $this->Width = imagesx($this->Canvas);
            $this->Height = imagesy($this->Canvas);
            $this->EOF = FALSE;

        }
		else {
            $this->EOF = TRUE;
        }
    }
    function ClearCanvas(){
        $Canvas = imagecreatetruecolor($this->Width, $this->Height);
        if (is_resource($Canvas) && is_resource($this->Canvas)){
            imagealphablending($Canvas, FALSE);
            imagecopy($Canvas, $this->Canvas, 0, 0, 0, 0, $this->Width, $this->Height);
            $this->Canvas = $Canvas;
        }
    }
    function Write($outputFile = ''){
		imagepng($this->Canvas,$outputFile);
	}
    function Output($outputFile = ''){
        if ($outputFile){
            $this->SetName($outputFile);
        }
        if ($_SERVER['REQUEST_METHOD']){
            header('Content-type: image/png');
            header('Content-Disposition: attachment; filename='.$this->Name);
            imagepng($this->Canvas);

        }
		else {
            $Info = pathinfo($outputFile);
            imagepng($this->Canvas, $Info['dirname'].'/'.$this->Name);
        }
        imagedestroy($this->Canvas);
    }
    function GetImageType($image){
        $Info = pathinfo($image);
        switch (strtolower($Info['extension'])){
            case 'jpg':
            case 'jpeg':
                return 'JPG';
            case 'gif':
                return 'GIF';
            case 'png':
                return 'PNG';
            default:
                return '';
        }
    }
    function GetPixel(){
        $RGB = imagecolorat($this->Canvas, $this->PixelPointer['x'], $this->PixelPointer['y']);
        $R = ($RGB >> 16) & 0xFF;
        $G = ($RGB >>  8) & 0xFF;
        $B = ($RGB >>  0) & 0xFF;
        return [$R, $G, $B];
    }
    function SetPixel($rgb){
        if (is_string($rgb) && strlen($rgb) == 3){
            $RGB = $this->GetPixel();
            for ($i = 0; $i < 3; $i++){
                if ($rgb[$i] == '1'){
                    if ($RGB[$i] % 2 != 1){
                        $RGB[$i]++;
                    }

                }
				else {
                    if ($RGB[$i] % 2 != 0){
                        $RGB[$i]--;
                    }
                }
            }
            $this->SetPixel($RGB);
            return TRUE;
        }
        if (is_array($rgb) && count($rgb) == 3){
            $Colour = imagecolorallocate($this->Canvas, $rgb[0], $rgb[1], $rgb[2]);
            imagesetpixel($this->Canvas, $this->PixelPointer['x'], $this->PixelPointer['y'], $Colour);
            return TRUE;
        }
        return FALSE;
    }
    function CountPixels(){
        return round($this->Height * $this->Width);
    }
    function FirstPixel(){
        $this->PixelPointer['x'] = ($this->Width - 1);
        $this->PixelPointer['y'] = ($this->Height - 1);
    }
    function StartPixel(){
        $this->PixelPointer['x'] = ($this->Width - 1) - 8;
        $this->PixelPointer['y'] = ($this->Height - 1);
    }
    function NextPixel(){
        if ($this->PixelPointer['x'] <= 0){
            if ($this->PixelPointer['y'] <= 0){
                $this->EOF = TRUE;
                return $this->EOF;

            }
			else {
                $this->PixelPointer['y']--;
                $this->PixelPointer['x'] = ($this->Width - 1);
            }
        }
		else {
            $this->PixelPointer['x']--;
        }
    }
    function PrevPixel(){
        if ($this->PixelPointer['x'] >= ($this->Width - 1)){
            if ($this->PixelPointer['y'] >= ($this->Height - 1)){
                return;
            }
			else {
                $this->PixelPointer['y']++;
                $this->PixelPointer['x'] = 0;
            }
        }
		else {
            $this->PixelPointer['x']++;
        }
    }
    function GetBoundry(){
		$return = '';
        $PixelPointer = $this->PixelPointer;
        $this->FirstPixel();
        for ($i = 0; $i < 8; $i++){
            $Pixel = $this->GetPixel();
            foreach ($Pixel as $PrimaryColour){
                $return .= (int) $PrimaryColour % 2;
            }
            $this->NextPixel();
        }
        $this->PixelPointer = $PixelPointer;
        return $return;
    }
    function SetBoundry($boundry){
        if (strlen($boundry) >= 24){
            $b = 0;
            for ($i = 0; $i < 8; $i++){
                $RGB = $this->GetPixel();
                for ($j = 0; $j < 3; $j++){
                    $Bit = $boundry[$b];
                    switch ($Bit){
                        case '1':
                            if ($RGB[$j] % 2 != 1){
                                $RGB[$j]++;
                            }
                        break;
                        case '0':
                            if ($RGB[$j] % 2 != 0){
                                $RGB[$j]--;
                            }
                        break;
                    }
                    $b++;
                }
                $this->SetPixel($RGB);
                $this->NextPixel();
            }
        }
    }
    function SetName($image){
        $Info = pathinfo($image);
        if (strlen($Info['extension']) > 0){
            if (strtolower($Info['extension']) != 'png'){
                $Info['basename'] = str_replace('.'.$Info['extension'], '.png', $Info['basename']);
            }

        }
		else {
            if (strlen($Info['basename']) > 0){
                $Info['basename'] .= '.png';

            }
			else {
                $Info['basename'] = 'encoded.png';
            }
        }
        $this->Name = $Info['basename'];
    }
    function EOF(){
        return $this->EOF;
    }
}
