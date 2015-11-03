<?php namespace RedCat\Identify;
use RedCat\Identify\PHPMailer\PHPMailer as OPHPMailer;
class PHPMailer extends OPHPMailer{
	function mail($email, $subject, $message, $html=true){
		if(is_array($email)){
			foreach($email as $k=>$v){
				if(is_integer($k))
					$this->addAddress($v);
				else
					$this->addAddress($k,$v);
			}
		}
		else{
			$this->addAddress($email);
		}
		$this->Subject = $subject;
		if($html){
			if(is_bool($html)){
				$this->msgHTML($message);
			}
			else{
				$this->msgHTML($html);
				$this->AltBody = $message;
			}
		}
		else{
			$this->Body = $message;
		}
		return $this->send();
	}
	function __construct(
		$fromEmail=null,$fromName=null,
		$replyEmail=null,$replyName=null,
		$host=null,$port=25,$username=null,$passowrd=null,$secure=null,
		$sendmail=null,
		$debug=false,$exceptions=false
	){
        parent::__construct($exceptions);
		if($host){
			$this->isSMTP();
			if(isset($debug)){
				$this->SMTPDebug = $debug;
				if($debug)
					$this->Debugoutput = 'html';
			}
			$this->Host = $host;
			$this->Port = $port;
			if(isset($username)){
				$this->SMTPAuth = true;
				if(isset($secure))
					$this->SMTPSecure = $secure===true?'tls':$secure;
				$this->Username = $username;
				$this->Password = $password;
			}
		}
		elseif($sendmail){
			$this->isSendmail();
		}
		if($fromEmail)
			$this->setFrom($fromEmail, $fromName);
		if($replyEmail)
			$this->addReplyTo($replyEmail, $replyName);
    }
}