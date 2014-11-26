<?php namespace Surikat\Control\crypto;

class Secrypt {
    var $Keys = ['public' => '', 'private' => '', 'xfactor' => '', 'yfactor' => '', 'zfactor' => ''];
    var $Data = '';
    var $Zip = TRUE;
    var $Errors = [];
    var $Locks = [];
    function Secrypt(){
        if (!function_exists('gzdeflate')){
            $this->Zip = FALSE;
        }
        set_time_limit(0);
        $this->ResetLock();
    }
    function Encrypt($data, $privateKey = '', $publicKey = STEGGER_PUB_KEY){
        $this->InsertKeys($privateKey, $publicKey);
        $this->TurnKey();
        return $this->Lock($data);
    }
    function Decrypt($data, $privateKey = '', $publicKey = STEGGER_PUB_KEY){
        $this->InsertKeys($privateKey, $publicKey);
        $this->TurnKey();
        return $this->Unlock($data);
    }
    function &GetKey($lockType){
        return $this->Keys[$lockType];
    }
    function InsertKeys($private, $public){
        $this->RemoveKey();
        $this->ResetLock();
        foreach ($this->Keys as $KeyType => $Key){
            if (strstr($KeyType, 'factor')){
                $Key = md5(serialize($this->Keys));

            }
			else {
                $Key = $$KeyType;
            }
            $this->InsertKey($Key, $KeyType);
        }
    }
    function InsertKey($key, $lockType){
        if (strlen($key) > 0){
            $this->Keys[$lockType] = $key;
        }
    }
    function TurnKey($lockType = ''){
        if (!$lockType){
            foreach ($this->Locks as $LockType => $Lock){
                $this->TurnKey($LockType);
            }
            return;
        }
        $Key =& $this->GetKey($lockType);
        for ($i = 0; $i < strlen($Key); $i++){
            $Steps = ord($Key[$i]) / ($i + 1);
            if (ord($Key[$i]) % 2 != 0){
                $this->TurnLock($lockType, $Steps, 'left');

            }
			else {
                $this->TurnLock($lockType, $Steps, 'right');
            }
        }
    }
    function RemoveKey($lockType = ''){
        foreach($this->Keys as $KeyName => $Key){
            if ($lockType == $KeyName || strlen($lockType) == 0){
                $this->Keys[$KeyName] = '';
            }
        }
    }
    function &GetLock($lockType){
        return $this->Locks[$lockType];
    }
    function Lock($data){
        $this->Data = '';
        if ($this->Zip == TRUE){
            if (FALSE === ($data = @gzdeflate($data))){
                $this->Error('There was a problem compressing the data');
                return FALSE;
            }
        }
        if (FALSE !== ($data = base64_encode($data))){
            for ($i = 0; $i < strlen($data); $i++){
                $data[$i] = $this->GetChar($data[$i], TRUE);
            }
            $this->Data = $data;
            return $this->Data;

        }
		else {
            $this->Error('There was a problem encoding the data');
            return FALSE;
        }
    }
    function Unlock($data){
        $this->Data = '';
        for ($i = 0; $i < strlen($data); $i++){
            $data[$i] = $this->GetChar($data[$i], FALSE);
        }
        if (FALSE !== ($data = base64_decode($data))){
            if (FALSE !== ($data = @gzinflate($data))){
                $this->Data = $data;
                return $this->Data;

            }
			else {
                $this->Error('There was a problem decompressing the data');
                return FALSE;
            }

        }
		else {
            $this->Error('There was a problem decoding the data');
            return FALSE;
        }
    }
    function TurnLock($lockType, $steps = 5, $direction = 'right'){
        for ($i = 0; $i < $steps; $i++){
            $Lock =& $this->GetLock($lockType);
            if ($direction != 'right') $Lock = strrev($Lock);
            $c = $i;
            if ($c >= strlen($Lock)){
                while ($c >= strlen($Lock)){
                    $c = $c - strlen($Lock);
                }
            }
            $Char = substr($Lock, 0, 1);
            $Lock = substr($Lock, 1);
            if (isset($Lock[$c])&&strlen($Lock[$c]) > 0){
                $Chunks = explode($Lock[$c], $Lock);
                if (is_array($Chunks)){
                    $Lock = $Chunks[0].$Lock[$c].$Char.$Chunks[1];
                }
            }
			else {
                $Lock = $Char.$Lock;
            }
            if ($direction != 'right') $Lock = strrev($Lock);
        }
    }
    function ResetLock($lockType = ''){
        $CharSet = $this->GetCharSet();
        foreach ($this->Keys as $LockType => $Key){
            if ($lockType){
                if ($LockType == $lockType){
                    $this->Locks[$LockType] = $CharSet;
                    return;
                }
            }
			else {
                $this->Locks[$LockType] = $CharSet;
            }
        }
    }
    function GetChar($char, $encrypt = FALSE){
        if (!$encrypt) $this->Locks = array_reverse($this->Locks);
        $i = 0;
        foreach ($this->Locks as $LockType => $Lock){
            if ($i == 0){
                $Position = strpos($Lock, $char);
            }
            if ($i % 2 > 0){
                if ($encrypt){
                    $Position = strpos($Lock, $char);
                }
				else {
                    $char = $Lock[$Position];
                }

            }
			else {
                if ($encrypt){
                    $char = $Lock[$Position];
                }
				else {
                    $Position = strpos($Lock, $char);
                }
            }
            $i++;
        }
        if (!$encrypt) $this->Locks = array_reverse($this->Locks);
        return $char;
    }
    function GetCharSet(){
		$return = '';
        $ForbiddenChars = array_merge(range(44, 46), range(58, 64), range(91, 96));
        for ($i = 43; $i < 123; $i++){
            if (!in_array($i, $ForbiddenChars)){
                $return .= chr($i);
            }
        }
        return $return;
    }
    function Error($msg){
        $this->Errors[] = $msg;
    }
    function ShowErrors($returnVal = FALSE){
		$return .= '';
        foreach ($this->Errors as $Error){
            if (strlen($_SERVER['REQUEST_METHOD']) > 0){
                $return .= '<strong>Error:</strong> '.$Error.'<br />';

            }
			else {
                $return .= '[-] '.$Error."\n";
            }
        }
        $this->Errors = [];
        if ($returnVal){
            return $return;

        }
		else {
            echo $return;
        }
    }
    function Debug($msg){
        ob_implicit_flush(1);
        if (strlen($_SERVER['REQUEST_METHOD'])){
            $msg = '<strong>Debug:</strong> '.$msg.'<br />';

        }
		else {
            $msg = '[i] '.$msg."\n";
        }
        echo $msg;
    }
 }
