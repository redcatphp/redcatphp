<?php namespace Surikat\Crypto;
use Surikat\Crypto\Image;
/*
exemple:

$Image = '1.png';
$Key = (string)round(pi(),10);

//GET
echo Stegano::Decrypt($Image, $Key);


$SecretData = "Hello World !!! That's work great.";

//SETBox
Stegano::EncryptBox($SecretData, $Image, $Key);

//SET
Stegano::Encrypt($SecretData, $Image, $Key, $Image); 


*/
 define('STEGGER_PUB_KEY', (string)round(pi(),10));
 class Stegano {
	public static function EncryptBox($SecretData, $path, $Key){
		file_put_contents($path,self::random_image());
		return self::Encrypt($SecretData, $path, $Key, $path);
	}
	public static function Decrypt($Image, $Key){
		return self::Objet()->Get($Image, $Key);
	}
	public static function Encrypt($SecretData, $ImageIn, $Key, $ImageOut){
		return self::Objet()->Put($SecretData, $ImageIn, $Key, $ImageOut);
	}
	
	public static $objet = false;
	public static function Objet(){
		if(!self::$objet){
			self::$objet = new Stegano();
		}
		return self::$objet;
	}
	public static function random_image(){
		/******************************************************************************/
		/* URL            : http://www.phpsources.org/scripts255-PHP.htm              */
		/* Auteur         : Cam -	Date édition   : 04 Mars 2007                                              */
		// HTTP://WWW.GENERATOR.ZEUBU.COM - CREATED BY CLEMENT VIALETTES
		//*****************************************************************************
		$img = ImageCreate(200,200);
		$colorbackgr = ImageColorAllocate( $img, 0, 0, 0 );
		//nombre de cercles
		$u1 = Rand( 10, 20);
		for( $i=1; $i < $u1; $i++){
		  //couleurs
		  $col1 = Rand( 71, 255);
		  $col2 = Rand( 71, 255);
		  $col3 = Rand( 71, 255);
		  //position des centres x y
		  $a1 = Rand( -5, 105);
		  $a2 = Rand( -5, 105);
		  //largeur et hauteur x y
		  $a3 = Rand( 5, 150);
		  $a4 = Rand( 5, 150);
		  //début du cercle et fin
		  $deg1 = Rand( 0, 360);
		  $deg2 = Rand( 0, 360);
		  //type de cercle
		  $a5 = Rand( 0, 1);
		  if($a5 == 0){
			ImageArc( $img, $a1, $a2, $a3, $a4, $deg1, $deg2, ImageColorAllocate( $img, 
		$col1, $col2, $col3));
			ImageArc( $img, $a1+1, $a2+1, $a3+1, $a4+1, $deg1+1, $deg2-1, 
		ImageColorAllocate( $img, $col1-10, $col2-10, $col3-10));
			ImageArc( $img, $a1-1, $a2-2, $a3-1, $a4-1, $deg1+1, $deg2-1, 
		ImageColorAllocate( $img, $col1-10, $col2-10, $col3-10));
			ImageArc( $img, $a1+2, $a2+2, $a3+2, $a4+2, $deg1+2, $deg2-2, 
		ImageColorAllocate( $img, $col1-20, $col2-20, $col3-20));
			ImageArc( $img, $a1-2, $a2-2, $a3-2, $a4-2, $deg1+2, $deg2-2, 
		ImageColorAllocate( $img, $col1-20, $col2-20, $col3-20));
			ImageArc( $img, $a1+3, $a2+3, $a3+3, $a4+3, $deg1+3, $deg2-3, 
		ImageColorAllocate( $img, $col1-30, $col2-30, $col3-30));
			ImageArc( $img, $a1-3, $a2-3, $a3-3, $a4-3, $deg1+3, $deg2-3, 
		ImageColorAllocate( $img, $col1-30, $col2-30, $col3-30));
			ImageArc( $img, $a1+4, $a2+4, $a3+4, $a4+4, $deg1+4, $deg2-4, 
		ImageColorAllocate( $img, $col1-40, $col2-40, $col3-40));
			ImageArc( $img, $a1-4, $a2-4, $a3-4, $a4-4, $deg1+4, $deg2-4, 
		ImageColorAllocate( $img, $col1-40, $col2-40, $col3-40));
			ImageArc( $img, $a1+5, $a2+5, $a3+5, $a4+5, $deg1+5, $deg2-5, 
		ImageColorAllocate( $img, $col1-50, $col2-50, $col3-50));
			ImageArc( $img, $a1-5, $a2-5, $a3-5, $a4-5, $deg1+5, $deg2-5, 
		ImageColorAllocate( $img, $col1-50, $col2-50, $col3-50));
			ImageArc( $img, $a1+6, $a2+6, $a3+6, $a4+6, $deg1+6, $deg2-6, 
		ImageColorAllocate( $img, $col1-60, $col2-60, $col3-60));
			ImageArc( $img, $a1-6, $a2-6, $a3-6, $a4-6, $deg1+6, $deg2-6, 
		ImageColorAllocate( $img, $col1-60, $col2-60, $col3-60));
			ImageArc( $img, $a1+7, $a2+7, $a3+7, $a4+7, $deg1+7, $deg2-7, 
		ImageColorAllocate( $img, $col1-70, $col2-70, $col3-70));
			ImageArc( $img, $a1-7, $a2-7, $a3-7, $a4-7, $deg1+7, $deg2-7, 
		ImageColorAllocate( $img, $col1-70, $col2-70, $col3-70));
		  }
		  else{
			ImageArc( $img, $a1, $a2, $a3, $a4, $deg1, $deg2, ImageColorAllocate( $img, 
		$col1, $col2, $col3));
			ImageArc( $img, $a1+1*Rand(-1,1), $a2+1*Rand(-1,1), $a3+1*Rand(-1,1), $a4+1*
		Rand(-1,1), $deg1+1, $deg2-1, ImageColorAllocate( $img, $col1-10, $col2-10, 
		$col3-10));
			ImageArc( $img, $a1-1*Rand(-1,1), $a2-2*Rand(-1,1), $a3-1*Rand(-1,1), $a4-1*
		Rand(-1,1), $deg1+1, $deg2-1, ImageColorAllocate( $img, $col1-10, $col2-10, 
		$col3-10));
			ImageArc( $img, $a1+2*Rand(-1,1), $a2+2*Rand(-1,1), $a3+2*Rand(-1,1), $a4+2*
		Rand(-1,1), $deg1+2, $deg2-2, ImageColorAllocate( $img, $col1-20, $col2-20, 
		$col3-20));
			ImageArc( $img, $a1-2*Rand(-1,1), $a2-2*Rand(-1,1), $a3-2*Rand(-1,1), $a4-2*
		Rand(-1,1), $deg1+2, $deg2-2, ImageColorAllocate( $img, $col1-20, $col2-20, 
		$col3-20));
			ImageArc( $img, $a1+3*Rand(-1,1), $a2+3*Rand(-1,1), $a3+3*Rand(-1,1), $a4+3*
		Rand(-1,1), $deg1+3, $deg2-3, ImageColorAllocate( $img, $col1-30, $col2-30, 
		$col3-30));
			ImageArc( $img, $a1-3*Rand(-1,1), $a2-3*Rand(-1,1), $a3-3*Rand(-1,1), $a4-3*
		Rand(-1,1), $deg1+3, $deg2-3, ImageColorAllocate( $img, $col1-30, $col2-30, 
		$col3-30));
			ImageArc( $img, $a1+4*Rand(-1,1), $a2+4*Rand(-1,1), $a3+4*Rand(-1,1), $a4+4*
		Rand(-1,1), $deg1+4, $deg2-4, ImageColorAllocate( $img, $col1-40, $col2-40, 
		$col3-40));
			ImageArc( $img, $a1-4*Rand(-1,1), $a2-4*Rand(-1,1), $a3-4*Rand(-1,1), $a4-4*
		Rand(-1,1), $deg1+4, $deg2-4, ImageColorAllocate( $img, $col1-40, $col2-40, 
		$col3-40));
			ImageArc( $img, $a1+5*Rand(-1,1), $a2+5*Rand(-1,1), $a3+5*Rand(-1,1), $a4+5*
		Rand(-1,1), $deg1+5, $deg2-5, ImageColorAllocate( $img, $col1-50, $col2-50, 
		$col3-50));
			ImageArc( $img, $a1-5*Rand(-1,1), $a2-5*Rand(-1,1), $a3-5*Rand(-1,1), $a4-5*
		Rand(-1,1), $deg1+5, $deg2-5, ImageColorAllocate( $img, $col1-50, $col2-50, 
		$col3-50));
			ImageArc( $img, $a1+6*Rand(-1,1), $a2+6*Rand(-1,1), $a3+6*Rand(-1,1), $a4+6*
		Rand(-1,1), $deg1+6, $deg2-6, ImageColorAllocate( $img, $col1-60, $col2-60, 
		$col3-60));
			ImageArc( $img, $a1-6*Rand(-1,1), $a2-6*Rand(-1,1), $a3-6*Rand(-1,1), $a4-6*
		Rand(-1,1), $deg1+6, $deg2-6, ImageColorAllocate( $img, $col1-60, $col2-60, 
		$col3-60));
			ImageArc( $img, $a1+7*Rand(-1,1), $a2+7*Rand(-1,1), $a3+7*Rand(-1,1), $a4+7*
		Rand(-1,1), $deg1+7, $deg2-7, ImageColorAllocate( $img, $col1-70, $col2-70, 
		$col3-70));
			ImageArc( $img, $a1-7*Rand(-1,1), $a2-7*Rand(-1,1), $a3-7*Rand(-1,1), $a4-7*
		Rand(-1,1), $deg1+7, $deg2-7, ImageColorAllocate( $img, $col1-70, $col2-70, 
		$col3-70));
		  }
		}
		//symétrie
		$x = 0;
		$y = 0;
		while( $x <= 100){
		  for( $y=0; $y<=100; $y++){
			//image de droite
			ImageSetPixel( $img, 200-($x), $y, ImageColorAt( $img, $x ,$y));
			//image dessous gauche
			ImageSetPixel( $img, $x, 200-$y, ImageColorAt( $img, $x, $y));
			//image dessous droite
			ImageSetPixel( $img, 200-$x, 200-$y, ImageColorAt( $img, $x, $y));
		  }
		  $x++;
		}
		//transparent ?
		if(isset($noir)==1){

		}
		elseif(isset($transp)==1){
			ImageColorTransparent( $img, ImageColorClosest( $img, 0, 0, 0 ));

		}
		else{
		  if(Rand(0,1)==1) ImageColorTransparent( $img, ImageColorClosest( $img, 0, 0, 0 ));
		}

		ob_start();
		ImagePng($img);
		ImageDestroy($img);
		return ob_get_clean();

	}
	
	
    var $Verbose = TRUE;
    var $CLI = FALSE;
    var $Image;
    var $BitStream;
    var $BitBoundry;
    var $RawData = [];
    function __construct(){
        // set_time_limit(0);
        $this->SetEnvironment();
        $this->BitStream = new BitStream();
    }
    function Put($secretData, $imageFile, $key = '', $outputFile = '', $show=false){
        $StartTime = microtime(TRUE);
        $this->BitStream->FlushStream();
        $this->Info('Loading image..');
        $this->Image = new Image($imageFile);
        if ($this->Image->EOF()){
            $this->FatalError('Could not load the supplied image');
        }
		else {
            $this->Info('Loading data..');
            if (!$this->Input($secretData)){
                $this->FatalError('Could not load the supplied data');

            }
			else {
                $this->Info('Encrypting data..');
                if (!$this->RawToString($key)){
                    $this->FatalError('Could not encrypt the loaded data');

                }
				else {
                    $this->Info('Encoding data..');
                    if (!$this->StringToStream()){
                        $this->FatalError('Could not encode the loaded data');

                    }
					else {
                        $this->Info('Encoding image..');
                        if (!$this->StreamToPixels()){
                            $this->FatalError('Could not encode the image');
                        }
						else {
                            $this->Info('Saving image..');
                            if($show){
								$this->Image->Output($outputFile);
							}
							else{
								$this->Image->Write($outputFile);
							}
                            $this->Success('Done in '.round(microtime(TRUE) - $StartTime).' seconds');
                        }
                    }
                }
            }
        }
    }
    function Get($imageFile, $key = '', $outputPath = '', $show=false){
        $StartTime = microtime(TRUE);
        $this->BitStream->FlushStream();
        $this->Info('Loading image..');
        $this->Image = new Image($imageFile);
        if ($this->Image->EOF()){
            $this->FatalError('Could not load the supplied image');
        }
		else {
            $this->Info('Reading image..');
            $this->PixelsToStream();
            if ($this->BitStream->EOF()){
                $this->FatalError('No hidden data found in the image');
            }
			else {
                $this->Info('Decoding data..');
                if (!$this->StreamToString()){
                    $this->FatalError('Could not decode the data');
                }
				else {
                    $this->Info('Decrypting data..');
                    if (!$this->StringToRaw($key)){
                        $this->FatalError('Could not decrypt data');
                    }
					else {
						if(!$show){
							return $this->Output_return($outputPath);
						}
                        if (!$this->Output($outputPath)){
                            $this->FatalError('Too many errors to continue');

                        } else {
                            $this->Success('Done in '.round(microtime(TRUE) - $StartTime).' seconds');
                        }
                    }
                }
            }
        }
    }
    function Input($data){
        if (is_array($data) && !isset($data['tmp_name'])){
            foreach ($data as $Element){
                $this->Input($Element);
            }
        }
		else {
            $this->ReadToRaw($data);
        }
        if (is_array($this->RawData) && count($this->RawData > 0)){
            return TRUE;

        }
		else {
            return FALSE;
        }
    }
	function Output_return($path=''){
        if (is_array($this->RawData) && count($this->RawData)){
            if (strlen($path)){
                if (!is_dir($path)){
					$this->Error('The specified output path is not a directory');
                    return FALSE;
                }
                if (!is_writable($path)){
					$this->Error('The specified output path is not writable');
                    return FALSE;
                }
                while (count($this->RawData) > 0){
                    if (!$this->WriteFromRaw($path)){
                        $this->Error('Problem extracting files');
                        return FALSE;
                    }
                }
                return TRUE;
            }
			else{
                if ($this->CommandLineInterface()){
                    $this->Error('You must specify an output path when using this tool from the command line');
                    return FALSE;
                }
				else{
                    $Data = $this->WriteFromRaw('', TRUE);
                    return $Data['message'];
                }
            }
        }
		else {
            $this->Error('No hidden data to extract from image');
            return FALSE;
        }
	}
    function Output($path = ''){
        if (is_array($this->RawData) && count($this->RawData)){
            if (strlen($path)){
                if (!is_dir($path)){
                    $this->Error('The specified output path is not a directory');
                    return FALSE;
                }
                if (!is_writable($path)){
                    $this->Error('The specified output path is not writable');
                    return FALSE;
                }
                while (count($this->RawData) > 0){
                    if (!$this->WriteFromRaw($path)){
                        $this->Error('Problem extracting files');
                        return FALSE;
                    }
                }
                return TRUE;

            }
			else {
                if ($this->CommandLineInterface()){
                    $this->Error('You must specify an output path when using this tool from the command line');
                    return FALSE;

                }
				else {
                    $Data = $this->WriteFromRaw('', TRUE);
                    switch ($Data['type']){
                        case 'message':
                            header('Content-type: text/plain');
                            header('Content-Disposition: attachment; filename=message.txt');
                            echo $Data['message'];
                            exit();
                        break;
                        case 'file':
                            header('Content-Disposition: attachment; filename='.$Data['filename']);
                            echo $Data['file'];
                            exit();
                        break;
                    }
                    return FALSE;
                }
            }

        }
		else {
            $this->Error('No hidden data to extract from image');
            return FALSE;
        }
    }
    function ReadToRaw($data){
        switch ($this->GetArgumentType($data)){
            case 'message':
                if (strlen($data) > 0){
                    array_push($this->RawData, ['type' => 'message', 'message' => base64_encode(gzdeflate($data))]);
                    return TRUE;
                }
            break;
            case 'uploaded':
                $Contents = file_get_contents($data['tmp_name']);
                if (strlen($Contents) > 0){
                    array_push($this->RawData, ['type' => 'file', 'file' => base64_encode(gzdeflate($Contents)), 'filename' => $data['name']]);
                    return TRUE;
                }
            break;
            case 'glob':
                foreach(glob($data) as $File){
                    $Contents = file_get_contents($File);
                    if (strlen($Contents) > 0){
                        array_push($this->RawData, ['type' => 'file', 'file' => base64_encode(gzdeflate($Contents)), 'filename' => $File]);
                    }
                }
                return TRUE;
            break;
            case 'file':
                $Contents = file_get_contents($data);
                if (strlen($Contents) > 0){
                    array_push($this->RawData, ['type' => 'file', 'file' => base64_encode(gzdeflate($Contents)), 'filename' => $data]);
                    return TRUE;
                }
            break;
        }
        return FALSE;
    }
    function WriteFromRaw($path = '', $return = FALSE){
        if (is_array($this->RawData) && count($this->RawData) > 0){
            $Data = array_pop($this->RawData);
            switch ($Data['type']){
                case 'message':
                    if ($return == FALSE){
                        $this->Info('The following message was embedded in the image');
                        $this->Info("\t".gzinflate(base64_decode($Data['message'])));
                        return TRUE;

                    }
					else {
                        $Data['message'] = gzinflate(base64_decode($Data['message']));
                        return $Data;
                    }
                break;
                case 'file':
                    if ($return == FALSE){
                        if (!strlen($path)){
                            return FALSE;

                        }
						else {
                            $Info = pathinfo($Data['filename']);
                            $Path = pathinfo($path);
                            $Pointer = fopen($Path['dirname'].'/'.$Path['basename'].'/'.$Info['basename'], 'w+');
                            if (is_resource($Pointer)){
                                fwrite($Pointer, gzinflate(base64_decode($Data['file'])));
                                fclose($Pointer);
                                return TRUE;

                            }
							else {
                                return FALSE;
                            }
                        }

                    }
					else {
                        $Data['file'] = gzinflate(base64_decode($Data['file']));
                        return $Data;
                    }
                break;
            }

        }
		else {
            return FALSE;
        }
    }
    function RawToString($key = ''){
        if (is_array($this->RawData) && count($this->RawData) > 0){
            $this->DataString = serialize($this->RawData);
            $Secrypt = new Secrypt();
            if ($Secrypt->Encrypt($this->DataString, $key)){
                $this->DataString = $Secrypt->Data;
                $this->RawData = []; unset($Secrypt);
                while (strstr($this->DataString, @$Boundry) || strlen(@$Boundry) <= 0){
                    $Boundry = chr(rand(33, 127)).chr(rand(33, 127)).chr(rand(33, 127));
                }
                $this->BitBoundry = '';
                for ($i = 0; $i < 3; $i++){
                    $this->BitBoundry .= str_pad(decbin(ord($Boundry[$i])), 8, '0', STR_PAD_LEFT);
                }
                return TRUE;

            }
			else {
                $this->DataString = '';
                return FALSE;
            }

        }
		else {
            return FALSE;
        }
    }
    function StringToRaw($key = ''){
        if (is_string($this->DataString) && strlen($this->DataString) > 0){
            $Secrypt = new Secrypt();
            if ($Secrypt->Decrypt($this->DataString, $key)){
                $this->RawData = unserialize($Secrypt->Data);
                if (is_array($this->RawData) && count($this->RawData) > 0){
                    return TRUE;

                }
				else {
                    return FALSE;
                }

            }
			else {
                return FALSE;
            }

        }
		else {
            return FALSE;
        }
    }
    function StreamToString(){
        $this->DataString = '';
        while (!$this->BitStream->EOF()){
            $this->DataString .= chr(bindec($this->BitStream->Read(8)));
        }
        if (strlen($this->DataString) > 0){
            $this->DataString = trim($this->DataString, ' ');
            return TRUE;

        }
		else {
            return FALSE;
        }
    }
    function StringToStream(){
        $this->BitStream->FlushStream();
        if ((strlen($this->DataString) * 8) < (($this->Image->CountPixels() - 6) * 3)){
            while (strlen($this->DataString) % 3 > 0){
                $this->DataString .= ' ';
            }
            while (strlen($this->DataString) > 0){
                $this->BitStream->Write(substr($this->DataString, 0, 1));
                $this->DataString = substr($this->DataString, 1);
            }
            return TRUE;

        }
		else {
            $Capacity = round(($this->Image->CountPixels() * 3) / 8);
            if ($Capacity < 1024){
                $Capacity = $Capacity.' bytes';
            }
			elseif ($Capacity < 1048576){
                $Capacity = round($Capacity / 1024, 2).' KB';
            }
			else {
                $Capacity = round(($Capacity / 1024) / 1024, 2).' MB';
            }
            $this->Error('That image is not large enough to store that much data');
            $this->Error('The image you supplied can only hold '.$Capacity.' of data');
            return FALSE;
        }
    }
    function PixelsToStream(){

        // Make a new bit stream for the image
        $BitStream = new BitStream($this->Image->GetBoundry());

        // Move to the start pixel
        $this->Image->StartPixel();

        // While we have bits and pixels
        while (!$this->Image->EOF() && !$BitStream->EOF()){

            // Get the current pixels RGB value
            $Pixel = $this->Image->GetPixel();

            // Write the pixel data to the bit stream
            $BitStream->Write($Pixel);

            // Move to the next pixel
            $this->Image->NextPixel();
        }

        // If we got to the end of the image
        if ($this->Image->EOF()){

            // Then we never found our secret data
            $BitStream->Stream = '';
        }

        // Overwrite the main bit stream with our new one
        $this->BitStream = $BitStream;
    }

    /*
      +------------------------------------------------------------------+
      | This will write a bit stream to pixels                           |
      |                                                                  |
      | @return void                                                     |
      +------------------------------------------------------------------+
    */

    function StreamToPixels(){

        // Move to the start pixel
        $this->Image->StartPixel();

        // While we have bits and pixels
        while (!$this->Image->EOF() && !$this->BitStream->EOF()){

            // Read the next 3 bits from the bit stream
            $Bits = $this->BitStream->Read(3);

            // Write those 3 bits to the current pixel
            $this->Image->SetPixel($Bits);

            // Move to the next pixel
            $this->Image->NextPixel();
        }

        // Set the end bit boundry
        $this->Image->SetBoundry($this->BitBoundry);

        // Move to the first pixel
        $this->Image->FirstPixel();

        // Set the first bit boundry
        $this->Image->SetBoundry($this->BitBoundry);

        // If we got here we probably succeeded
        return TRUE;
    }

    // Enviromental Methods

    /*
      +------------------------------------------------------------------+
      | This will set properties relating to our run time environment    |
      |                                                                  |
      | @return void                                                     |
      +------------------------------------------------------------------+
    */

    function SetEnvironment(){

        // If we have a REQUEST_METHOD
        if ($_SERVER['REQUEST_METHOD']){

            // Then we are probably being called from the web
            $this->CLI = FALSE;

            // Turn verbose output off
            $this->Verbose = FALSE;

        } else {

            // We are being run as a command line (or possibly compiled) app
            $this->CLI = TRUE;

            // Turn verbose output on
            $this->Verbose = TRUE;

            // Make sure we have implicit flush set to on
            ob_implicit_flush(1);
        }
    }

    /*
      +------------------------------------------------------------------+
      | This will determine if we are using a command line interface     |
      |                                                                  |
      | @return boolean                                                  |
      +------------------------------------------------------------------+
    */

    function CommandLineInterface(){

        // If the command line interface flag is set
        if ($this->CLI){

            // Then we are probably using a command line interface
            return TRUE;

        } else {

            // Not a command line interface
            return FALSE;
        }
    }

    /*
      +------------------------------------------------------------------+
      | This will attempt to figure out what an argument represents      |
      |                                                                  |
      | @return string                                                   |
      +------------------------------------------------------------------+
    */

    function GetArgumentType($argument){

        // If this is looks like an uploaded file
        if (is_array($argument) && isset($argument['tmp_name'])){

            // Then it probably is one
            return 'uploaded';

        // If this looks like a local file
        } elseif (file_exists($argument)){

            // Handle as a file
            return 'file';

        // If this looks like an external resource (TODO: Do this properly)
        } elseif (strstr($argument, '://')){

            // Handle as a file
            return 'file';

        // If the argument contains an asterix (TODO: Check the validity of the path)
        } elseif (strstr($argument, '*') && ($argument[0] == '.' || $argument[0] == '/')){

            // Then I'm guessing it is a glob style string
            return 'glob';

        // Everything else
        } else {

            // Treat it as a normal message
            return 'message';
        }
    }

    // Message Methods

    /*
      +------------------------------------------------------------------+
      | Print out an error message to the user and exit                  |
      |                                                                  |
      | @return void                                                     |
      +------------------------------------------------------------------+
    */

    function FatalError($msg){

        // First we show the error message to the user
        $this->Error('Fatal Error: '.$msg);

        // Now we exit
        exit(-1);
    }

    /*
      +------------------------------------------------------------------+
      | Print out an error message to the user                           |
      |                                                                  |
      | @return void                                                     |
      +------------------------------------------------------------------+
    */

    function Error($msg){

        // If we are running as a command line application
        if ($this->CommandLineInterface()){

            // Just show the message a little formatted for the command line
            echo '[-] '.$msg.".\n";

        } else {

            // Show the error formatted for the web
            echo '<strong>Error:</strong> '.htmlspecialchars($msg).'<br />';
        }
    }

    /*
      +------------------------------------------------------------------+
      | Print out a success message to the user                          |
      |                                                                  |
      | @return void                                                     |
      +------------------------------------------------------------------+
    */

    function Success($msg){

        // If we are in verbose mode
        if ($this->Verbose){

            // If we are running as a command line application
            if ($this->CommandLineInterface()){

                // Just show the message a little formatted for the command line
                echo '[+] '.$msg.".\n";

            } else {

                // Show the message formatted for the web
                echo '<strong>Success:</strong> '.htmlspecialchars($msg).'<br />';
            }
        }
    }

    /*
      +------------------------------------------------------------------+
      | Print out an informative message to the user                     |
      |                                                                  |
      | @return void                                                     |
      +------------------------------------------------------------------+
    */

    function Info($msg){

        // If we are in verbose mode
        if ($this->Verbose){

            // If we are running as a command line application
            if ($this->CommandLineInterface()){

                // Just show the message a little formatted for the command line
                echo '[i] '.$msg.".\n";

            } else {

                // Show the message formatted for the web
                echo '<strong>Info:</strong> '.htmlspecialchars($msg).'<br />';
            }
        }
    }
}
