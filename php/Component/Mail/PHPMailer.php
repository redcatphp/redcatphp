<?php namespace Surikat\Mail;
use Surikat\Config\Config;
use Surikat\Mail\PHPMailer\PHPMailer as OPHPMailer;
class PHPMailer extends OPHPMailer{
	static function mail($email, $subject, $message, $html=true){
		$o = self::instance();
		if(is_array($email)){
			foreach($email as $k=>$v){
				if(is_integer($k))
					$o->addAddress($v);
				else
					$o->addAddress($k,$v);
			}
		}
		else{
			$o->addAddress($email);
		}
		$o->Subject = $subject;
		if($html){
			if(is_bool($html)){
				$o->msgHTML($message);
			}
			else{
				$o->msgHTML($html);
				$o->AltBody = $message;
			}
		}
		else{
			$o->Body = $message;
		}
		return $o->send();
	}
	static function instance(){
		return new static();
	}
	function __construct($exceptions = false){
        parent::__construct($exceptions);
        $config = Config::mailer();
		$fromName = isset($config['fromName'])?$config['fromName']:null;
		$fromEmail = isset($config['fromEmail'])?$config['fromEmail']:null;
		$replyName = isset($config['replyName'])?$config['replyName']:null;
		$replyEmail = isset($config['replyEmail'])?$config['replyEmail']:null;
        if(isset($config['host'])&&$config['host']){
			$this->isSMTP();
			if(isset($config['debug'])){
				$this->SMTPDebug = $config['debug'];
				if($config['debug'])
					$this->Debugoutput = 'html';
			}
			$this->Host = $config['host'];
			$this->Port = isset($config['port'])?$config['port']:25;
			if(isset($config['username'])){
				$this->SMTPAuth = true;
				if(isset($config['secure']))
					$this->SMTPSecure = $config['secure']===true?'tls':$config['secure'];
				$this->Username = $config['username'];
				$this->Password = $config['password'];
			}
		}
		elseif(isset($config['sendmail'])&&$config['sendmail']){
			$this->isSendmail();
		}
		if($fromEmail)
			$this->setFrom($fromEmail, $fromName);
		if($replyEmail)
			$this->addReplyTo($replyEmail, $replyName);
    }
}