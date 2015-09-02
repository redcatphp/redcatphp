<?php namespace Wild\Identify;
use Wild\Identify\Auth;
class AuthServer{
	protected $messages;
	protected $lastResult;
	protected $defaultLogoutKey = 'auth-server-logout';
	protected $Auth;
	protected $Session;
	
	protected $baseHref;
	protected $suffixHref;
	protected $server;
	
	function __construct(Auth $Auth=null,$server=null){
		if(!$Auth)
			$Auth = new Auth();
		$this->Auth = $Auth;
		$this->Session = $this->Auth->getSession();
		if(!$server)
			$server = &$_SERVER;
		$this->server = $server;
	}
	function getAuth(){
		return $this->Auth;
	}
	function getSession(){
		return $this->Session;
	}
	function getResultMessage($lg=0,$widget=false){
		if($this->lastResult&&!is_bool($this->lastResult)){
			return $this->getMessage($this->lastResult,$lg,$widget);
		}
	}
	function getResult(){
		return $this->lastResult;
	}
	function action($action=null){
		if(!func_num_args())
			$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
		if(!$action)
			return;
		$r = null;
		if(method_exists($this,$action)){
			$r = $this->$action();
			$ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])&&strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])=='xmlhttprequest';
			if(!is_bool($r)){
				switch($r){
					case Auth::OK_LOGGED_IN:
						if(!$ajax){
							$this->Session->set('Auth','result',$action,$r);
							$this->reloadLocation();
						}
					break;
					case Auth::OK_REGISTER_SUCCESS:
						if(!$ajax){
							$this->Session->set('Auth','result',$action,$r);
							$this->reloadLocation();
						}
					break;
				}
			}
			if(!$r)
				$r = $this->Session->get('Auth','result',$action);
		}
		return $this->lastResult = $r;
	}
	function register(){
		if(isset($_POST['email'])&&isset($_POST['login'])&&isset($_POST['password'])&&isset($_POST['confirm'])){
			$email = $_POST['email'];
			$login = trim($_POST['login'])?$_POST['login']:$email;
			$this->Session->set('Auth','email',$email);
			return $this->Auth->register($email, $login, $_POST['password'], $_POST['confirm']);
		}
	}
	function resendactivate(){
		if($email=$this->Session->get('Auth','email')){
			return $this->Auth->resendActivation($email);
		}
	}
	function activate(){
		if(isset($_GET['key'])){
			return $this->Auth->activate($_GET['key']);
		}
	}
	function loginPersona(){
		if(isset($_POST['email'])&&$_POST['email']&&$_POST['email']==($email=$this->Session->get('email'))){
			$lifetime = 0;
			if(isset($_POST['login'])){
				switch($_POST['lifetime']){
					case 'day':
						$lifetime = 86400;
					break;
					case 'week':
						$lifetime = 604800;
					break;
					case 'month':
						$lifetime = 2592000;
					break;
					case 'year':
						$lifetime = 31536000;
					break;
				}
			}
			return $this->Auth->loginPersona($email, $lifetime);
		}
	}
	function login(){
		if(isset($_POST['login'])&&isset($_POST['password'])){
			$lifetime = 0;
			if(isset($_POST['remember'])&&$_POST['remember']&&isset($_POST['lifetime'])){
				switch($_POST['lifetime']){
					case 'day':
						$lifetime = 86400;
					break;
					case 'week':
						$lifetime = 604800;
					break;
					case 'month':
						$lifetime = 2592000;
					break;
					case 'year':
						$lifetime = 31536000;
					break;
				}
			}
			return $this->Auth->login($_POST['login'], $_POST['password'], $lifetime);
		}
		elseif(isset($_POST['email'])&&$_POST['email']){
			return $this->loginPersona();
		}
	}
	function resetreq(){
		if(isset($_POST['email'])){
			return $this->Auth->requestReset($_POST['email']);
		}
	}
	function resetpass(){
		if(isset($_GET['key'])&&isset($_POST['password'])&&isset($_POST['confirm'])){
			return $this->Auth->resetPass($_GET['key'], $_POST['password'], $_POST['confirm']);
		}
	}
	function lougoutAPI($key=null){
		if(!$key)
			$key = $this->defaultLogoutKey;
		if(isset($_POST[$key])){
			$this->logout();
			return true;
		}
	}
	function reloadLocation(){
		header('Location: '.$this->getLocation(),false,302);
	}
	function lougoutBTN($key=null,$ret=false){
		if(!$key)
			$key = $this->defaultLogoutKey;
		if($this->lougoutAPI()){
			$this->reloadLocation();
		}
		else{
			$html = '
			<link href="'.$this->getBaseHref().'css/font/fontawesome.css" rel="stylesheet" type="text/css">
			<style type="text/css">
				a.auth-logout{
					background: none repeat scroll 0 0 #fff;
					border: 1px solid #000;
					border-radius: 3px;
					color: #000;
					padding: 1px 3px 0;
					position: absolute;
					right: 0;
					top: 0;
					z-index: 1000;
				}
				a,
				a:focus,
				a:hover,
				a:link:hover,
				a:visited:hover{
					color:#000;
					text-decoration:none;
				}
				a.auth-logout::before{
					font-family: FontAwesome;
					font-style: normal;
					font-size: 16px;
					font-weight: normal;
					line-height: normal;
					-webkit-font-smoothing: antialiased;
					speak: none;
					content: "\f011";
				}
			</style>
			<script type="text/javascript" src="'.$this->getBaseHref().'js/post.js"></script>
			<script type="text/javascript">
				authServerLogoutCaller = function(){
					post("'.$this->getLocation().'",{"'.$key.'":1});
					return false;
				};
			</script>
			';
			$html .= '<a class="auth-logout" onclick="return authServerLogoutCaller();" href="#"></a>';
			if($ret)
				return $html;
			else
				echo $html;
		}
	}
	function logout(){
		return $this->Auth->logout();
	}
	
	function htmlLock($r,$redirect=true){
		$action = $this->getLocation();
		if(isset($_POST['__login__'])&&isset($_POST['__password__'])){
			$lifetime = 0;
			if(isset($_POST['remember'])&&$_POST['remember']&&isset($_POST['lifetime'])){
				switch($_POST['lifetime']){
					case 'day':
						$lifetime = 86400;
					break;
					case 'week':
						$lifetime = 604800;
					break;
					case 'month':
						$lifetime = 2592000;
					break;
					case 'year':
						$lifetime = 31536000;
					break;
				}
			}
			if($this->Auth->login($_POST['__login__'],$_POST['__password__'],$lifetime)===Auth::OK_LOGGED_IN){
				header('Location: '.$action,false,302);
				exit;
			}
		}
		if($this->Auth->allowed($r))
			return;
		if($this->Auth->connected()){
			if($redirect)
				header('Location: '.$this->Auth->siteUrl.'403',false,302);
			else
				http_response_code(403);
			exit;
		}
		echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Authentication</title>
		<style type="text/css">
			form{
				width:350px;
				margin:0 auto;
				display:block;
			}
			label,input{
				position:relative;
				float:left;
			}
			label{
				width:100px;
				font-size:1em;
				display:block;
			}
			input[type="submit"]{
				left:100px;
			}
		</style>
		</head><body>';
		if($seconds=$this->Session->isBlocked()){
			echo $this->Auth->getMessage([Auth::ERROR_USER_BLOCKED,$seconds],true);
		}
		echo '<form id="form" action="'.$action.'" method="POST">
			<label for="__login__">Login</label><input type="text" id="__login__" name="__login__" placeholder="Login"><br>
			<label id="password" for="__password__">Password</label><input type="password" id="__password__" name="__password__" placeholder="Password"><br>
			<fieldset>
				<label for="remember">Remember me</label>
				<input type="checkbox" name="remember" value="1">
				<select name="lifetime">
					<option value="day">One Day</option>
					<option value="week" selected>One Week</option>
					<option value="month">One Month</option>
					<option value="year">One Year</option>
				</select>
			</fieldset>
			<input id="submit" value="Connection" type="submit">
		</form>
		</body></html>';
		exit;
	}
	
	
	function getMessage($code,$lg=0,$widget=false){
		if(!isset($this->messages)){
			$this->messages = [
				Auth::ERROR_USER_BLOCKED => "Too many failed attempts, try again in %d seconds",
				Auth::ERROR_USER_BLOCKED_2 => "Too many failed attempts, try again in %d minutes and %d seconds",
				Auth::ERROR_USER_BLOCKED_3 => "Too many failed attempts, try again in :",
				Auth::ERROR_LOGIN_SHORT => "Login is too short",
				Auth::ERROR_LOGIN_LONG => "Login is too long",
				Auth::ERROR_LOGIN_INCORRECT => "Login is incorrect",
				Auth::ERROR_LOGIN_INVALID => "Login is invalid",
				Auth::ERROR_NAME_INVALID => "Name is invalid",
				Auth::ERROR_PASSWORD_SHORT => "Password is too short",
				Auth::ERROR_PASSWORD_LONG => "Password is too long",
				Auth::ERROR_PASSWORD_INVALID => "Password must contain at least one uppercase and lowercase character, and at least one digit",
				Auth::ERROR_PASSWORD_NOMATCH => "Passwords do not match",
				Auth::ERROR_PASSWORD_INCORRECT => "Current password is incorrect",
				Auth::ERROR_PASSWORD_NOTVALID => "Password is invalid",
				Auth::ERROR_NEWPASSWORD_SHORT => "New password is too short",
				Auth::ERROR_NEWPASSWORD_LONG => "New password is too long",
				Auth::ERROR_NEWPASSWORD_INVALID => "New password must contain at least one uppercase and lowercase character, and at least one digit",
				Auth::ERROR_NEWPASSWORD_NOMATCH => "New passwords do not match",
				Auth::ERROR_LOGIN_PASSWORD_INVALID => "Login / Password are invalid",
				Auth::ERROR_LOGIN_PASSWORD_INCORRECT => "Login / Password are incorrect",
				Auth::ERROR_EMAIL_INVALID => "Email address is invalid",
				Auth::ERROR_EMAIL_INCORRECT => "Email address is incorrect",
				Auth::ERROR_NEWEMAIL_MATCH => "New email matches previous email",
				Auth::ERROR_ACCOUNT_INACTIVE => "Account has not yet been activated",
				Auth::ERROR_SYSTEM_ERROR => "A system error has been encountered. Please try again",
				Auth::ERROR_LOGIN_TAKEN => "The login is already taken",
				Auth::ERROR_EMAIL_TAKEN => "The email address is already in use",
				Auth::ERROR_AUTHENTICATION_REQUIRED => "Authentication required",
				Auth::ERROR_ALREADY_AUTHENTICATED => "You are already authenticated",
				Auth::ERROR_RESETKEY_INVALID => "Reset key is invalid",
				Auth::ERROR_RESETKEY_INCORRECT => "Reset key is incorrect",
				Auth::ERROR_RESETKEY_EXPIRED => "Reset key has expired",
				Auth::ERROR_ACTIVEKEY_INVALID => "Activation key is invalid",
				Auth::ERROR_ACTIVEKEY_INCORRECT => "Activation key is incorrect",
				Auth::ERROR_ACTIVEKEY_EXPIRED => "Activation key has expired",
				Auth::ERROR_RESET_EXISTS => "A reset request already exists",
				Auth::ERROR_ALREADY_ACTIVATED => "Account is already activated",
				Auth::ERROR_ACTIVATION_EXISTS => "An activation email has already been sent",
				
				Auth::OK_PASSWORD_CHANGED => "Password changed successfully",
				Auth::OK_EMAIL_CHANGED => "Email address changed successfully",
				Auth::OK_ACCOUNT_ACTIVATED => "Account has been activated. You can now log in",
				Auth::OK_ACCOUNT_DELETED => "Account has been deleted",
				Auth::OK_LOGGED_IN => "You are now logged in",
				Auth::OK_LOGGED_OUT => "You are now logged out",
				Auth::OK_REGISTER_SUCCESS => "Account created. Activation email sent to email",
				Auth::OK_PASSWORD_RESET => "Password reset successfully",
				Auth::OK_RESET_REQUESTED => "Password reset request sent to email address",
				Auth::OK_ACTIVATION_SENT => "Activation email has been sent",
			];
		}
		if(is_array($code)){
			$c = array_shift($code);
			switch($c){
				case Auth::ERROR_USER_BLOCKED:
					$t = array_shift($code);
					if($t>60){
						$c = Auth::ERROR_USER_BLOCKED_2;
						$code[] = floor($t/60);
						$code[] = $t%60;
					}
					else{
						$code[] = $t;
					}
					if($widget){
						if($t>60){
							$minutes = floor($t/60);
							$t = $t%60;
						}
						else{
							$minutes = 0;
						}
						$r = '<div id="msgcountdown">'.$this->getMessage([Auth::ERROR_USER_BLOCKED,$t]).'</div>';
						$r .= '<div id="countdown"></div>';
						$r .= '<script>
							var interval;
							var minutes = '.$minutes.';
							var seconds = '.$t.';
							window.onload = function(){
								var countdown = document.getElementById("countdown");
								var msgcountdown = document.getElementById("msgcountdown");
								var showCountDown = function(){
									if(seconds == 0) {
										if(minutes == 0) {
											countdown.innerHTML = "";
											msgcountdown.innerHTML = "";
											clearInterval(interval);
											return;
										} else {
											minutes--;
											seconds = 60;
										}
									}
									if(minutes > 0) {
										var minute_text = minutes + (minutes > 1 ? "minutes" : "minute");
									} else {
										var minute_text = "";
									}
									var second_text = seconds > 1 ? "seconds" : "second";
									countdown.innerHTML = minute_text + " " + seconds + " " + second_text;
									seconds--;
								};
								msgcountdown.innerHTML = "'.$this->getMessage(Auth::ERROR_USER_BLOCKED_3).'";
								showCountDown();
								var interval = setInterval(showCountDown,1000);
							}
						</script>';
						return $r;
					}
				break;
			}
			array_unshift($code,$this->messages[$c]);
			$message = call_user_func_array('sprintf',$code);
		}
		else{
			$message = $this->messages[$code];
		}
		if(function_exists('__')){
			$message = __($message);
		}
		return $message;
	}
	
	
	function setBaseHref($href){
		$this->baseHref = $href;
	}
	function getProtocolHref(){
		return 'http'.(@$this->server["HTTPS"]=="on"?'s':'').'://';
	}
	function getServerHref(){
		return $this->server['SERVER_NAME'];
	}
	function getPortHref(){
		$ssl = @$this->server["HTTPS"]=="on";
		return @$this->server['SERVER_PORT']&&((!$ssl&&(int)$this->server['SERVER_PORT']!=80)||($ssl&&(int)$this->server['SERVER_PORT']!=443))?':'.$this->server['SERVER_PORT']:'';
	}
	function getBaseHref(){
		if(!isset($this->baseHref)){
			$this->setBaseHref($this->getProtocolHref().$this->getServerHref().$this->getPortHref().'/');
		}
		return $this->baseHref.$this->getSuffixHref();
	}
	function setSuffixHref($href){
		$this->suffixHref = $href;
	}
	function getSuffixHref(){
		if(!isset($this->suffixHref)){
			if(isset($this->server['SURIKAT_URI'])){
				$this->suffixHref = ltrim($this->server['SURIKAT_URI'],'/');				
			}
			else{
				$docRoot = $this->server['DOCUMENT_ROOT'].'/';
				if(defined('SURIKAT_CWD'))
					$cwd = SURIKAT_CWD;
				else
					$cwd = getcwd();
				if($docRoot!=$cwd&&strpos($cwd,$docRoot)===0)
					$this->suffixHref = substr($cwd,strlen($docRoot));
			}
		}
		return $this->suffixHref;
	}
	function getSubdomainHref($sub=''){
		$lg = $this->getSubdomainLang();
		$server = $this->getServerHref();
		if($lg)
			$server = substr($server,strlen($lg)+1);
		if($sub)
			$sub .= '.';
		return $this->getProtocolHref().$sub.$server.$this->getPortHref().'/'.$this->getSuffixHref();
	}
	function getSubdomainLang($domain=null){
		if(!isset($domain))
			$domain = $this->getServerHref();
		$urlParts = explode('.', $domain);
		if(count($urlParts)>2&&strlen($urlParts[0])==2)
			return $urlParts[0];
		else
			return null;
	}
	function getLocation(){
		//return $this->getBaseHref().ltrim($this->server['REQUEST_URI'],'/');
		$get = http_build_query($_GET);
		if(!empty($get))
			$get = '?'.$get;
		return $this->getBaseHref().ltrim($this->server['PATH_INFO'],'/').$get;
	}
}