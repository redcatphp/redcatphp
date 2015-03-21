<?php namespace Surikat\Crypto;
class BitStream {
    var $Stream = '';
    var $Boundry = '';
    var $Fresh = TRUE;
    function __construct($bitBoundry = ''){
        if ($bitBoundry) $this->Boundry = $bitBoundry;
    }
    function Read($number = 8){
        if (strlen($this->Stream) > 0){
            $return = substr($this->Stream, 0, $number);
            $this->Stream = substr($this->Stream, $number);
            return $return;

        }
		else {
            return FALSE;
        }
    }
    function Write($data, $binary = FALSE){
        if ($binary){
            $this->Stream .= $data;

        }
		else {
            switch (gettype($data)){
                case 'string':
                    for ($i = 0; $i < strlen($data); $i++){
                        $this->Stream .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
                    }
                break;
                case 'integer':
                    $this->Stream .= str_pad(decbin($data), 8, '0', STR_PAD_LEFT);
                break;
                case 'boolean':
                    if ($data == TRUE){
                        $this->Stream .= '1';

                    }
					else {
                        $this->Stream .= '0';
                    }
                break;
                case 'array':
                    foreach ($data as $PrimaryColour){
                        $this->Stream .= (int) $PrimaryColour % 2;
                    }
                break;
            }
        }
    }
    function EOF(){
        if ($this->Fresh){
            $this->Fresh = FALSE;
            return FALSE;
        }
        if (strlen($this->Stream) > 0){
            if (strlen($this->Boundry)){
                if (substr($this->Stream, -24) == $this->Boundry){
                    $this->Stream = substr($this->Stream, 0, -24);
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
            return TRUE;
        }
    }
    function FlushStream(){
        $this->Stream = '';
    }
}
