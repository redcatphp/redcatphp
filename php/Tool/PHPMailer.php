<?php namespace Surikat\Tool;
use Surikat\Core\Config;
use Surikat\Tool\PHPMailer as OPHPMailer;
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
        if(isset($config['host'])&&$config['host']){
			$mail->isSMTP();
			if(isset($config['debug'])){
				$mail->SMTPDebug = $config['debug'];
				if($config['debug'])
					$mail->Debugoutput = 'html';
			}
			$mail->Host = $config['host'];
			$mail->Port = isset($config['port'])?$config['port']:25;
			if(isset($config['username'])){
				$mail->SMTPAuth = true;
				if(isset($config['secure']))
					$mail->SMTPSecure = $config['secure']===true?'tls':$config['secure'];
				$mail->Username = $config['username'];
				$mail->Password = $config['password'];
			}
		}
		elseif(isset($config['sendmail'])&&$config['sendmail']){
			$mail->isSendmail();
		}
		if($fromEmail)
			$mail->setFrom($fromEmail, $fromName);
		if($replyEmail)
			$mail->addReplyTo($replyEmail, $replyName);
    }
}